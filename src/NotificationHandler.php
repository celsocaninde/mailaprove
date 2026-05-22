<?php

namespace GlpiPlugin\Mailaprove;

use NotificationTarget;
use NotificationTargetTicket;
use Ticket;
use TicketValidation;
use TicketSatisfaction;
use CommonITILValidation;

class NotificationHandler
{
    /**
     * Hook callback for ITEM_GET_DATA on NotificationTargetTicket.
     * Called when GLPI assembles notification data for ticket-related events.
     *
     * @param NotificationTargetTicket $target
     */
    public static function handleNotificationData(NotificationTargetTicket $target): void
    {
        $config = Config::getConfig();

        // Register all custom tags (so they appear in the template editor)
        self::registerTags($target, $config);

        // Populate tag values based on the current event
        self::populateTagData($target, $config);
    }

    /**
     * Register custom tags in the notification template system.
     */
    private static function registerTags(NotificationTargetTicket $target, array $config): void
    {
        if (!empty($config['enable_validation'])) {
            $target->addTagToList([
                'tag'    => 'ticket.validation.accepturl',
                'label'  => __('URL para aprovar validação', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
            $target->addTagToList([
                'tag'    => 'ticket.validation.rejecturl',
                'label'  => __('URL para recusar validação', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
            $target->addTagToList([
                'tag'    => 'ticket.validation.buttons',
                'label'  => __('Botões de aprovação da validação', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
        }

        if (!empty($config['enable_solution'])) {
            $target->addTagToList([
                'tag'    => 'ticket.solution.accepturl',
                'label'  => __('URL para aceitar solução', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
            $target->addTagToList([
                'tag'    => 'ticket.solution.rejecturl',
                'label'  => __('URL para recusar solução', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
            $target->addTagToList([
                'tag'    => 'ticket.solution.buttons',
                'label'  => __('Botões de aceite da solução', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
        }

        if (!empty($config['enable_satisfaction'])) {
            $target->addTagToList([
                'tag'    => 'ticket.satisfaction.url',
                'label'  => __('URL da pesquisa de satisfação', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
            $target->addTagToList([
                'tag'    => 'ticket.satisfaction.button',
                'label'  => __('Botão da pesquisa de satisfação', 'mailaprove'),
                'value'  => true,
                'events' => NotificationTarget::TAG_FOR_ALL_EVENTS,
            ]);
        }
    }

    /**
     * Populate tag values with generated token URLs.
     */
    private static function populateTagData(NotificationTargetTicket $target, array $config): void
    {
        // Get the ticket object
        $ticket = $target->obj ?? null;
        if (!($ticket instanceof Ticket) || empty($ticket->fields['id'])) {
            return;
        }

        $ticketId = (int)$ticket->fields['id'];
        $event = $target->raiseevent ?? '';

        // Determine the recipient user ID
        // In GLPI notifications, we look at who the notification is being sent to
        $recipientUserId = self::getRecipientUserId($target);

        // Handle validation events
        if (!empty($config['enable_validation'])
            && in_array($event, ['validation', 'validation_answer', 'new', 'update'], true)
        ) {
            self::populateValidationUrls($target, $ticketId, $recipientUserId);
        }

        // Handle solution events
        if (!empty($config['enable_solution'])
            && in_array($event, ['solution', 'add_followup', 'update', 'new'], true)
        ) {
            self::populateSolutionUrls($target, $ticketId, $recipientUserId);
        }

        // Handle satisfaction events
        if (!empty($config['enable_satisfaction'])
            && in_array($event, ['satisfaction', 'replysatisfaction'], true)
        ) {
            self::populateSatisfactionUrls($target, $ticketId, $recipientUserId);
        }
    }

    /**
     * Get the recipient user ID from the notification target.
     */
    private static function getRecipientUserId(NotificationTargetTicket $target): int
    {
        // Try to get from current recipient data
        if (!empty($target->data['##lang.validation.validator##'])
            || !empty($target->data['##validation.validator##'])
        ) {
            // For validation events, the recipient is the validator
            // Try to find the users_id_validate from the ticket validations
        }

        // Try options
        if (isset($target->options['users_id'])) {
            return (int)$target->options['users_id'];
        }

        // Try to get from the notification recipient
        if (isset($target->data['##user.id##'])) {
            return (int)$target->data['##user.id##'];
        }

        return 0;
    }

    /**
     * Generate and populate validation approval/rejection URLs.
     */
    private static function populateValidationUrls(
        NotificationTargetTicket $target,
        int $ticketId,
        int $recipientUserId
    ): void {
        global $DB;

        $where = [
            'tickets_id' => $ticketId,
            'status'     => CommonITILValidation::WAITING,
        ];
        if ($recipientUserId > 0) {
            $where['users_id_validate'] = $recipientUserId;
        }

        // Find a pending validation for the current recipient.
        $iterator = $DB->request([
            'FROM'  => 'glpi_ticketvalidations',
            'WHERE' => $where,
            'ORDER' => 'submission_date DESC',
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            self::clearValidationTags($target);
            return;
        }

        $validation = $iterator->current();
        $validationId = (int)$validation['id'];
        $validatorId = (int)$validation['users_id_validate'];

        // Use the actual validator as the authorized user
        $userId = $validatorId > 0 ? $validatorId : $recipientUserId;

        if ($userId <= 0) {
            self::clearValidationTags($target);
            return;
        }

        // Generate approve token
        $approveToken = Token::generateToken(
            Token::ACTION_VALIDATION_APPROVE,
            $ticketId,
            $validationId,
            $userId
        );
        $target->data['##ticket.validation.accepturl##'] = $approveToken
            ? Token::buildActionUrl($approveToken, Token::ACTION_VALIDATION_APPROVE)
            : '';

        // Generate reject token
        $rejectToken = Token::generateToken(
            Token::ACTION_VALIDATION_REJECT,
            $ticketId,
            $validationId,
            $userId
        );
        $target->data['##ticket.validation.rejecturl##'] = $rejectToken
            ? Token::buildActionUrl($rejectToken, Token::ACTION_VALIDATION_REJECT)
            : '';

        $target->data['##ticket.validation.buttons##'] = self::renderDualButtonBlock(
            $target,
            'template_validation',
            __('Revise a validação solicitada para este chamado.', 'mailaprove'),
            $target->data['##ticket.validation.accepturl##'],
            __('Aprovar', 'mailaprove'),
            '#0f766e',
            $target->data['##ticket.validation.rejecturl##'],
            __('Recusar', 'mailaprove'),
            '#b91c1c'
        );
    }

    /**
     * Generate and populate solution accept/reject URLs.
     */
    private static function populateSolutionUrls(
        NotificationTargetTicket $target,
        int $ticketId,
        int $recipientUserId
    ): void {
        global $DB;

        // Find the most recent solution for this ticket
        $iterator = $DB->request([
            'FROM'  => 'glpi_itilsolutions',
            'WHERE' => [
                'items_id' => $ticketId,
                'itemtype' => 'Ticket',
                'status'   => CommonITILValidation::WAITING,
            ],
            'ORDER' => 'date_creation DESC',
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            self::clearSolutionTags($target);
            return;
        }

        $solution = $iterator->current();
        $solutionId = (int)$solution['id'];

        $userId = $recipientUserId;
        if (!PublicAction::isTicketRequester($ticketId, $userId)) {
            self::clearSolutionTags($target);
            return;
        }

        // Generate accept token
        $acceptToken = Token::generateToken(
            Token::ACTION_SOLUTION_APPROVE,
            $ticketId,
            $solutionId,
            $userId
        );
        $target->data['##ticket.solution.accepturl##'] = $acceptToken
            ? Token::buildActionUrl($acceptToken, Token::ACTION_SOLUTION_APPROVE)
            : '';

        // Generate reject token
        $rejectToken = Token::generateToken(
            Token::ACTION_SOLUTION_REJECT,
            $ticketId,
            $solutionId,
            $userId
        );
        $target->data['##ticket.solution.rejecturl##'] = $rejectToken
            ? Token::buildActionUrl($rejectToken, Token::ACTION_SOLUTION_REJECT)
            : '';

        $target->data['##ticket.solution.buttons##'] = self::renderDualButtonBlock(
            $target,
            'template_solution',
            __('Uma solução foi registrada. Confirme se ela resolveu sua solicitação.', 'mailaprove'),
            $target->data['##ticket.solution.accepturl##'],
            __('Aceitar solução', 'mailaprove'),
            '#2563eb',
            $target->data['##ticket.solution.rejecturl##'],
            __('Recusar solução', 'mailaprove'),
            '#b45309'
        );
    }

    /**
     * Generate and populate satisfaction survey URL.
     */
    private static function populateSatisfactionUrls(
        NotificationTargetTicket $target,
        int $ticketId,
        int $recipientUserId
    ): void {
        global $DB;

        // Find satisfaction record for this ticket
        $iterator = $DB->request([
            'FROM'  => 'glpi_ticketsatisfactions',
            'WHERE' => [
                'tickets_id'      => $ticketId,
                'date_answered'   => null,
            ],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            self::clearSatisfactionTags($target);
            return;
        }

        $satisfaction = $iterator->current();
        $satisfactionId = (int)$satisfaction['id'];

        $userId = $recipientUserId;
        if (!PublicAction::isTicketRequester($ticketId, $userId)) {
            self::clearSatisfactionTags($target);
            return;
        }

        $token = Token::generateToken(
            Token::ACTION_SATISFACTION,
            $ticketId,
            $satisfactionId,
            $userId
        );
        $target->data['##ticket.satisfaction.url##'] = $token
            ? Token::buildActionUrl($token, Token::ACTION_SATISFACTION)
            : '';

        $target->data['##ticket.satisfaction.button##'] = self::renderSingleButtonBlock(
            $target,
            'template_satisfaction',
            __('Sua opinião ajuda a melhorar a experiência de atendimento.', 'mailaprove'),
            $target->data['##ticket.satisfaction.url##'],
            __('Responder pesquisa', 'mailaprove'),
            '#2563eb'
        );
    }

    private static function renderDualButtonBlock(
        NotificationTargetTicket $target,
        string $configKey,
        string $message,
        string $primaryUrl,
        string $primaryLabel,
        string $primaryColor,
        string $secondaryUrl,
        string $secondaryLabel,
        string $secondaryColor
    ): string {
        if ($primaryUrl === '' || $secondaryUrl === '') {
            return '';
        }

        $custom = self::renderCustomTemplate($configKey, $target, [
            'approve_url'     => $primaryUrl,
            'accept_url'      => $primaryUrl,
            'primary_url'     => $primaryUrl,
            'reject_url'      => $secondaryUrl,
            'secondary_url'   => $secondaryUrl,
            'approve_label'   => $primaryLabel,
            'accept_label'    => $primaryLabel,
            'primary_label'   => $primaryLabel,
            'reject_label'    => $secondaryLabel,
            'secondary_label' => $secondaryLabel,
        ]);
        if ($custom !== '') {
            return $custom;
        }

        return self::renderButtonShell(
            $message,
            self::renderButton($primaryUrl, $primaryLabel, $primaryColor)
            . ' '
            . self::renderButton($secondaryUrl, $secondaryLabel, $secondaryColor)
        );
    }

    private static function clearValidationTags(NotificationTargetTicket $target): void
    {
        $target->data['##ticket.validation.accepturl##'] = '';
        $target->data['##ticket.validation.rejecturl##'] = '';
        $target->data['##ticket.validation.buttons##'] = '';
    }

    private static function clearSolutionTags(NotificationTargetTicket $target): void
    {
        $target->data['##ticket.solution.accepturl##'] = '';
        $target->data['##ticket.solution.rejecturl##'] = '';
        $target->data['##ticket.solution.buttons##'] = '';
    }

    private static function clearSatisfactionTags(NotificationTargetTicket $target): void
    {
        $target->data['##ticket.satisfaction.url##'] = '';
        $target->data['##ticket.satisfaction.button##'] = '';
    }

    private static function renderSingleButtonBlock(
        NotificationTargetTicket $target,
        string $configKey,
        string $message,
        string $url,
        string $label,
        string $color
    ): string
    {
        if ($url === '') {
            return '';
        }

        $custom = self::renderCustomTemplate($configKey, $target, [
            'survey_url'     => $url,
            'url'            => $url,
            'primary_url'    => $url,
            'survey_label'   => $label,
            'label'          => $label,
            'primary_label'  => $label,
        ]);
        if ($custom !== '') {
            return $custom;
        }

        return self::renderButtonShell($message, self::renderButton($url, $label, $color));
    }

    private static function renderCustomTemplate(string $configKey, NotificationTargetTicket $target, array $values): string
    {
        $config = Config::getConfig();
        $template = trim((string) ($config[$configKey] ?? ''));
        if ($template === '') {
            return '';
        }

        $ticket = $target->obj ?? null;
        $values += [
            'ticket_id'    => $ticket instanceof Ticket ? (string) ($ticket->fields['id'] ?? '') : '',
            'ticket_name'  => $ticket instanceof Ticket ? (string) ($ticket->fields['name'] ?? '') : '',
        ];

        $replace = [];
        foreach ($values as $key => $value) {
            $replace['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($template, $replace);
    }

    private static function renderButtonShell(string $message, string $buttons): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:18px 0;">'
            . '<tr><td style="padding:16px; border:1px solid #d9e1ec; border-radius:8px; background:#f8fafc;">'
            . '<p style="margin:0 0 12px; color:#344054; font-size:14px;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . $buttons
            . '</td></tr></table>';
    }

    private static function renderButton(string $url, string $label, string $color): string
    {
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; padding:10px 16px; margin:0 8px 8px 0; border-radius:6px; background:'
            . htmlspecialchars($color, ENT_QUOTES, 'UTF-8')
            . '; color:#ffffff; text-decoration:none; font-weight:700;">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</a>';
    }
}
