<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Token;
use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\AuditLog;

$rawToken = $_POST['token'] ?? $_GET['token'] ?? '';

if (empty($rawToken)) {
    $errorTitle = __('Token ausente', 'mailaprove');
    $errorMessage = __('Nenhum token de aprovação foi informado. Use o link original recebido por e-mail.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$result = $isPost
    ? Token::claimTokenWithStatus($rawToken, Token::ACTION_VALIDATION_APPROVE)
    : Token::validateTokenWithStatus($rawToken);

PublicAction::applyRecipientLocale($result['data'] ?? null);

if (!$result['valid']) {
    PublicAction::renderError(PublicAction::tokenErrorContent($result['error']));
    exit;
}

$tokenData = (array) $result['data'];

if ($tokenData['action_type'] !== Token::ACTION_VALIDATION_APPROVE) {
    PublicAction::renderError(PublicAction::tokenErrorContent('invalid_action'));
    exit;
}

$context = PublicAction::validationContext($tokenData, $isPost);
if (!$context['ok']) {
    PublicAction::renderError($context);
    exit;
}

if (!$isPost) {
    $confirmTitle               = __('Confirmar aprovação', 'mailaprove');
    $confirmMessage             = __('Revise o chamado antes de aprovar esta validação por e-mail.', 'mailaprove');
    $confirmButton              = __('Aprovar validação', 'mailaprove');
    $confirmType                = 'success';
    $confirmNote                = __('Após confirmar, a validação será aprovada no GLPI e os links relacionados serão invalidados.', 'mailaprove');
    $confirmShowComment         = true;
    $confirmCommentLabel        = __('Comentário adicional (opcional)', 'mailaprove');
    $confirmCommentPlaceholder  = __('Deixe em branco para registrar "Aprovado por e-mail"...', 'mailaprove');
    $actionUrl                  = 'approve.php';
    $ticketSummary              = $context['ticket'];
    include(GLPI_ROOT . '/plugins/mailaprove/templates/action_confirm.php');
    exit;
}

$comment = trim($_POST['comment_validation'] ?? '');
if ($comment === '') {
    $comment = __('Aprovado por e-mail', 'mailaprove');
}

// Use DB update directly: the mailaprove token already proves authorization.
// TicketValidation::update() runs canAnswer() against the web session user,
// which is always unauthenticated on public pages — causing the ORM to silently
// strip status/comment_validation from the input without saving anything.
global $DB;
$updateResult = $DB->update(TicketValidation::getTable(), [
    'status'             => CommonITILValidation::ACCEPTED,
    'comment_validation' => $comment,
    'validation_date'    => date('Y-m-d H:i:s'),
], [
    'id'         => (int)$tokenData['items_id'],
    'tickets_id' => (int)$tokenData['tickets_id'],
    'status'     => CommonITILValidation::WAITING,
]);

if ($updateResult) {
    Token::markRelatedAsUsed([
        Token::ACTION_VALIDATION_APPROVE,
        Token::ACTION_VALIDATION_REJECT,
    ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
    AuditLog::record('validation_approved', 'success', AuditLog::contextFromTokenRow($tokenData));

    $confirmTitle = __('Validação aprovada', 'mailaprove');
    $confirmMessage = sprintf(
        __('A validação do chamado #%d foi aprovada com sucesso.', 'mailaprove'),
        (int)$tokenData['tickets_id']
    );
    $confirmType = 'success';
    include(GLPI_ROOT . '/plugins/mailaprove/templates/confirm.php');
} else {
    AuditLog::record('validation_approve_failed', 'error', AuditLog::contextFromTokenRow($tokenData));
    $errorTitle = __('Erro ao processar', 'mailaprove');
    $errorMessage = __('Não foi possível aprovar a validação. Acesse o GLPI para verificar o chamado.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
}
