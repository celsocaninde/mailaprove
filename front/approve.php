<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Token;
use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\AuditLog;

$rawToken = $_POST['token'] ?? $_GET['token'] ?? '';

if (empty($rawToken)) {
    $error = 'missing_token';
    $errorTitle = __('Token ausente', 'mailaprove');
    $errorMessage = __('Nenhum token de aprovação foi informado. Use o link original recebido por e-mail.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$result = $isPost
    ? Token::claimTokenWithStatus($rawToken, Token::ACTION_VALIDATION_APPROVE)
    : Token::validateTokenWithStatus($rawToken);

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
    $confirmTitle = __('Confirmar aprovação', 'mailaprove');
    $confirmMessage = __('Revise o chamado antes de aprovar esta validação por e-mail.', 'mailaprove');
    $confirmButton = __('Aprovar validação', 'mailaprove');
    $confirmType = 'success';
    $confirmNote = __('Após confirmar, a validação será aprovada no GLPI e os links relacionados serão invalidados.', 'mailaprove');
    $actionUrl = 'approve.php';
    $ticketSummary = $context['ticket'];
    include(GLPI_ROOT . '/plugins/mailaprove/templates/action_confirm.php');
    exit;
}

$validation = $context['item'];
$updateResult = $validation->update([
    'id'                 => (int)$tokenData['items_id'],
    'status'             => CommonITILValidation::ACCEPTED,
    'comment_validation' => __('Aprovado por e-mail', 'mailaprove'),
    'validation_date'    => date('Y-m-d H:i:s'),
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
