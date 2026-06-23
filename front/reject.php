<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Token;
use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\AuditLog;

$rawToken = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($rawToken)) {
    $errorTitle = __('Token ausente', 'mailaprove');
    $errorMessage = __('Nenhum token de aprovação foi informado. Use o link original recebido por e-mail.', 'mailaprove');
    include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    exit;
}

$result = Token::validateTokenWithStatus($rawToken);

PublicAction::applyRecipientLocale($result['data'] ?? null);

if (!$result['valid']) {
    PublicAction::renderError(PublicAction::tokenErrorContent($result['error']));
    exit;
}

$tokenData = (array) $result['data'];

if ($tokenData['action_type'] !== Token::ACTION_VALIDATION_REJECT) {
    PublicAction::renderError(PublicAction::tokenErrorContent('invalid_action'));
    exit;
}

$context = PublicAction::validationContext($tokenData);
if (!$context['ok']) {
    PublicAction::renderError($context);
    exit;
}

// POST: Process the rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment_validation'] ?? '');

    if (empty($comment)) {
        $formError = __('Informe o motivo da recusa.', 'mailaprove');
        $ticketId = (int)$tokenData['tickets_id'];
        $ticketSummary = $context['ticket'];
        include(GLPI_ROOT . '/plugins/mailaprove/templates/reject_form.php');
        exit;
    }

    $claim = Token::claimTokenWithStatus($rawToken, Token::ACTION_VALIDATION_REJECT);
    if (!$claim['valid']) {
        PublicAction::renderError(PublicAction::tokenErrorContent($claim['error']));
        exit;
    }
    $tokenData = (array) $claim['data'];

    $context = PublicAction::validationContext($tokenData, true);
    if (!$context['ok']) {
        PublicAction::renderError($context);
        exit;
    }

    // Use DB update directly: token already proves authorization.
    // TicketValidation::update() strips status/comment via canAnswer() check.
    global $DB;
    $updateResult = $DB->update(TicketValidation::getTable(), [
        'status'             => CommonITILValidation::REFUSED,
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
        AuditLog::record('validation_rejected', 'success', AuditLog::contextFromTokenRow($tokenData));

        $confirmTitle = __('Validação recusada', 'mailaprove');
        $confirmMessage = sprintf(
            __('A validação do chamado #%d foi recusada.', 'mailaprove'),
            (int)$tokenData['tickets_id']
        );
        $confirmType = 'warning';
        include(GLPI_ROOT . '/plugins/mailaprove/templates/confirm.php');
    } else {
        AuditLog::record('validation_reject_failed', 'error', AuditLog::contextFromTokenRow($tokenData));
        $errorTitle = __('Erro ao processar', 'mailaprove');
        $errorMessage = __('Não foi possível recusar a validação. Acesse o GLPI para verificar o chamado.', 'mailaprove');
        include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    }
    exit;
}

// GET: Show rejection form
$ticketId = (int)$tokenData['tickets_id'];
$formError = '';
$ticketSummary = $context['ticket'];
include(GLPI_ROOT . '/plugins/mailaprove/templates/reject_form.php');
