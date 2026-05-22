<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Token;
use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\AuditLog;

$rawToken = $_POST['token'] ?? $_GET['token'] ?? '';

if (empty($rawToken)) {
    $errorTitle = __('Token ausente', 'mailaprove');
    $errorMessage = __('Nenhum token foi informado. Use o link original recebido por e-mail.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$result = $isPost
    ? Token::claimTokenWithStatus($rawToken, Token::ACTION_SOLUTION_APPROVE)
    : Token::validateTokenWithStatus($rawToken);

if (!$result['valid']) {
    PublicAction::renderError(PublicAction::tokenErrorContent($result['error']));
    exit;
}

$tokenData = (array) $result['data'];

if ($tokenData['action_type'] !== Token::ACTION_SOLUTION_APPROVE) {
    PublicAction::renderError(PublicAction::tokenErrorContent('invalid_action'));
    exit;
}

$context = PublicAction::solutionContext($tokenData, $isPost);
if (!$context['ok']) {
    PublicAction::renderError($context);
    exit;
}

if (!$isPost) {
    $confirmTitle = __('Confirmar aceite da solução', 'mailaprove');
    $confirmMessage = __('Revise o chamado antes de aceitar esta solução por e-mail.', 'mailaprove');
    $confirmButton = __('Aceitar solução', 'mailaprove');
    $confirmType = 'success';
    $confirmNote = __('Após confirmar, a solução será marcada como aceita no GLPI e os links relacionados serão invalidados.', 'mailaprove');
    $actionUrl = 'solution_approve.php';
    $ticketSummary = $context['ticket'];
    include(GLPI_ROOT . '/plugins/mailaprove/templates/action_confirm.php');
    exit;
}

$solution = $context['item'];
$followup = new ITILFollowup();
$followup->add([
    'items_id'   => $tokenData['tickets_id'],
    'itemtype'   => 'Ticket',
    'content'    => __('Solução aceita por e-mail', 'mailaprove'),
    'requesttypes_id' => 0,
]);

$updateResult = $solution->update([
    'id'     => (int)$tokenData['items_id'],
    'status' => CommonITILValidation::ACCEPTED,
]);

if ($updateResult) {
    Token::markRelatedAsUsed([
        Token::ACTION_SOLUTION_APPROVE,
        Token::ACTION_SOLUTION_REJECT,
    ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
    AuditLog::record('solution_approved', 'success', AuditLog::contextFromTokenRow($tokenData));

    $confirmTitle = __('Solução aceita', 'mailaprove');
    $confirmMessage = sprintf(
        __('A solução do chamado #%d foi aceita com sucesso.', 'mailaprove'),
        (int)$tokenData['tickets_id']
    );
    $confirmType = 'success';
    include(GLPI_ROOT . '/plugins/mailaprove/templates/confirm.php');
} else {
    AuditLog::record('solution_approve_failed', 'error', AuditLog::contextFromTokenRow($tokenData));
    $errorTitle = __('Erro ao processar', 'mailaprove');
    $errorMessage = __('Não foi possível aceitar a solução. Acesse o GLPI para verificar o chamado.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
}
