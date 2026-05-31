<?php

namespace GlpiPlugin\Mailaprove;

use CommonITILValidation;
use ITILSolution;
use Ticket;
use TicketSatisfaction;
use TicketValidation;
use User;

class PublicAction
{
    /**
     * Render the public mail pages in the recipient's own language.
     *
     * These endpoints are stateless (no GLPI session), so without this the
     * pages always fall back to the instance default language. The token is
     * tied to a user, so we honour that user's language preference instead.
     *
     * @param array|null $tokenData Token row (must contain users_id) or null.
     */
    public static function applyRecipientLocale($tokenData): void
    {
        if (!is_array($tokenData)) {
            return;
        }

        $userId = (int) ($tokenData['users_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $user = new User();
        if (!$user->getFromDB($userId)) {
            return;
        }

        $lang = trim((string) ($user->fields['language'] ?? ''));
        if ($lang === '') {
            return;
        }

        if (method_exists(\Session::class, 'loadLanguage')) {
            \Session::loadLanguage($lang, true);
        }
    }

    public static function tokenErrorContent(?string $error): array
    {
        return match ($error) {
            'used' => [
                'title'   => __('Ação já processada', 'mailaprove'),
                'message' => __('Este link já foi utilizado ou outra ação relacionada já foi concluída.', 'mailaprove'),
            ],
            'expired' => [
                'title'   => __('Link expirado', 'mailaprove'),
                'message' => __('Este link expirou. Acesse o GLPI para continuar o atendimento.', 'mailaprove'),
            ],
            'invalid_action' => [
                'title'   => __('Ação inválida', 'mailaprove'),
                'message' => __('Este link não corresponde à ação solicitada.', 'mailaprove'),
            ],
            default => [
                'title'   => __('Link inválido', 'mailaprove'),
                'message' => __('Não foi possível validar este link. Use o link original recebido por e-mail.', 'mailaprove'),
            ],
        };
    }

    public static function ticketSummary(int $ticketId): array
    {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($ticketId)) {
            return [
                'id'       => $ticketId,
                'name'     => '',
                'subtitle' => __('Chamado não encontrado', 'mailaprove'),
            ];
        }

        $meta = [];
        if (!empty($ticket->fields['date_creation'])) {
            $meta[] = sprintf(__('aberto em %s', 'mailaprove'), (string) $ticket->fields['date_creation']);
        }

        $requesterId = (int) ($ticket->fields['users_id_recipient'] ?? 0);
        if ($requesterId > 0) {
            $user = new User();
            if ($user->getFromDB($requesterId)) {
                $meta[] = sprintf(__('solicitante: %s', 'mailaprove'), self::formatUserName($user));
            }
        }

        return [
            'id'       => $ticketId,
            'name'     => (string) ($ticket->fields['name'] ?? ''),
            'subtitle' => sprintf(__('Chamado #%d', 'mailaprove'), $ticketId),
            'meta'     => implode(' · ', $meta),
            'preview'  => self::textPreview((string) ($ticket->fields['content'] ?? ''), 220),
        ];
    }

    public static function validationContext(array $tokenData, bool $invalidateProcessedTokens = false): array
    {
        $validation = new TicketValidation();
        if (!$validation->getFromDB((int) $tokenData['items_id'])) {
            return self::errorContext(
                __('Validação não encontrada', 'mailaprove'),
                __('A solicitação de validação não foi encontrada. Ela pode ter sido removida no GLPI.', 'mailaprove')
            );
        }

        if ((int) ($validation->fields['tickets_id'] ?? 0) !== (int) $tokenData['tickets_id']) {
            AuditLog::record('validation_context_mismatch', 'error', AuditLog::contextFromTokenRow($tokenData));
            return self::errorContext(
                __('Link inconsistente', 'mailaprove'),
                __('Este link não corresponde mais à validação do chamado informado.', 'mailaprove')
            );
        }

        $validatorId = (int) ($validation->fields['users_id_validate'] ?? 0);
        if ($validatorId > 0 && $validatorId !== (int) $tokenData['users_id']) {
            AuditLog::record('validation_user_mismatch', 'error', AuditLog::contextFromTokenRow($tokenData, [
                'payload' => ['validator_id' => $validatorId],
            ]));
            return self::errorContext(
                __('Ação não autorizada', 'mailaprove'),
                __('Este link pertence a outro aprovador.', 'mailaprove')
            );
        }

        if ((int) ($validation->fields['status'] ?? 0) !== CommonITILValidation::WAITING) {
            if ($invalidateProcessedTokens) {
                Token::markRelatedAsUsed([
                    Token::ACTION_VALIDATION_APPROVE,
                    Token::ACTION_VALIDATION_REJECT,
                ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
            }
            return self::errorContext(
                __('Validação já processada', 'mailaprove'),
                __('Esta validação já foi aprovada ou recusada no GLPI.', 'mailaprove'),
                'processed'
            );
        }

        return [
            'ok'         => true,
            'item'       => $validation,
            'ticket'     => self::ticketSummary((int) $tokenData['tickets_id']),
            'error_code' => null,
        ];
    }

    public static function solutionContext(array $tokenData, bool $invalidateProcessedTokens = false): array
    {
        $solution = new ITILSolution();
        if (!$solution->getFromDB((int) $tokenData['items_id'])) {
            return self::errorContext(
                __('Solução não encontrada', 'mailaprove'),
                __('A solução informada neste link não foi encontrada.', 'mailaprove')
            );
        }

        if (
            (string) ($solution->fields['itemtype'] ?? '') !== 'Ticket'
            || (int) ($solution->fields['items_id'] ?? 0) !== (int) $tokenData['tickets_id']
        ) {
            AuditLog::record('solution_context_mismatch', 'error', AuditLog::contextFromTokenRow($tokenData));
            return self::errorContext(
                __('Link inconsistente', 'mailaprove'),
                __('Este link não corresponde mais à solução do chamado informado.', 'mailaprove')
            );
        }

        if (!self::isTicketRequester((int) $tokenData['tickets_id'], (int) $tokenData['users_id'])) {
            AuditLog::record('solution_user_not_requester', 'error', AuditLog::contextFromTokenRow($tokenData));
            return self::errorContext(
                __('Ação não autorizada', 'mailaprove'),
                __('Somente o solicitante do chamado pode aceitar ou recusar a solução por e-mail.', 'mailaprove')
            );
        }

        if ((int) ($solution->fields['status'] ?? 0) !== CommonITILValidation::WAITING) {
            if ($invalidateProcessedTokens) {
                Token::markRelatedAsUsed([
                    Token::ACTION_SOLUTION_APPROVE,
                    Token::ACTION_SOLUTION_REJECT,
                ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
            }
            return self::errorContext(
                __('Solução já processada', 'mailaprove'),
                __('Esta solução já foi aceita ou recusada no GLPI.', 'mailaprove'),
                'processed'
            );
        }

        return [
            'ok'         => true,
            'item'       => $solution,
            'ticket'     => self::ticketSummary((int) $tokenData['tickets_id']),
            'error_code' => null,
        ];
    }

    public static function satisfactionContext(array $tokenData, bool $invalidateProcessedTokens = false): array
    {
        $satisfaction = new TicketSatisfaction();
        if (!$satisfaction->getFromDB((int) $tokenData['items_id'])) {
            return self::errorContext(
                __('Pesquisa não encontrada', 'mailaprove'),
                __('A pesquisa de satisfação informada neste link não foi encontrada.', 'mailaprove')
            );
        }

        if ((int) ($satisfaction->fields['tickets_id'] ?? 0) !== (int) $tokenData['tickets_id']) {
            AuditLog::record('satisfaction_context_mismatch', 'error', AuditLog::contextFromTokenRow($tokenData));
            return self::errorContext(
                __('Link inconsistente', 'mailaprove'),
                __('Este link não corresponde mais à pesquisa do chamado informado.', 'mailaprove')
            );
        }

        if (!self::isTicketRequester((int) $tokenData['tickets_id'], (int) $tokenData['users_id'])) {
            AuditLog::record('satisfaction_user_not_requester', 'error', AuditLog::contextFromTokenRow($tokenData));
            return self::errorContext(
                __('Ação não autorizada', 'mailaprove'),
                __('Somente o solicitante do chamado pode responder esta pesquisa por e-mail.', 'mailaprove')
            );
        }

        if (!empty($satisfaction->fields['date_answered'])) {
            if ($invalidateProcessedTokens) {
                Token::markRelatedAsUsed([
                    Token::ACTION_SATISFACTION,
                ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
            }
            return self::errorContext(
                __('Pesquisa já respondida', 'mailaprove'),
                __('Esta pesquisa de satisfação já foi respondida.', 'mailaprove'),
                'processed'
            );
        }

        return [
            'ok'         => true,
            'item'       => $satisfaction,
            'ticket'     => self::ticketSummary((int) $tokenData['tickets_id']),
            'error_code' => null,
        ];
    }

    public static function isTicketRequester(int $ticketId, int $userId): bool
    {
        global $DB;

        if ($ticketId <= 0 || $userId <= 0) {
            return false;
        }

        $ticket = new Ticket();
        if ($ticket->getFromDB($ticketId) && (int) ($ticket->fields['users_id_recipient'] ?? 0) === $userId) {
            return true;
        }

        $requesterType = class_exists('\\CommonITILActor')
            ? \CommonITILActor::REQUESTER
            : 1;

        $iterator = $DB->request([
            'FROM'  => 'glpi_tickets_users',
            'WHERE' => [
                'tickets_id' => $ticketId,
                'users_id'   => $userId,
                'type'       => $requesterType,
            ],
            'LIMIT' => 1,
        ]);

        return count($iterator) > 0;
    }

    public static function renderError(array $context): void
    {
        $errorTitle = $context['title'] ?? __('Não foi possível processar', 'mailaprove');
        $errorMessage = $context['message'] ?? __('A ação não pôde ser concluída.', 'mailaprove');
        include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    }

    private static function errorContext(string $title, string $message, string $code = 'invalid'): array
    {
        return [
            'ok'         => false,
            'title'      => $title,
            'message'    => $message,
            'error_code' => $code,
        ];
    }

    private static function formatUserName(User $user): string
    {
        $parts = array_filter([
            trim((string) ($user->fields['firstname'] ?? '')),
            trim((string) ($user->fields['realname'] ?? '')),
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return (string) ($user->fields['name'] ?? __('usuário', 'mailaprove'));
    }

    private static function textPreview(string $html, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) ?? '');
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $maxLength
                ? rtrim(mb_substr($text, 0, $maxLength - 1)) . '...'
                : $text;
        }

        return strlen($text) > $maxLength
            ? rtrim(substr($text, 0, $maxLength - 1)) . '...'
            : $text;
    }
}
