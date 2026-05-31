<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Token;
use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\AuditLog;

$rawToken = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($rawToken)) {
    $errorTitle = __('Token ausente', 'mailaprove');
    $errorMessage = __('Nenhum token foi informado. Use o link original recebido por e-mail.', 'mailaprove');
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

if ($tokenData['action_type'] !== Token::ACTION_SATISFACTION) {
    PublicAction::renderError(PublicAction::tokenErrorContent('invalid_action'));
    exit;
}

$context = PublicAction::satisfactionContext($tokenData);
if (!$context['ok']) {
    PublicAction::renderError($context);
    exit;
}

// POST: Process satisfaction response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['satisfaction'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $formError = __('Selecione uma nota entre 1 e 5 estrelas.', 'mailaprove');
        $ticketId = (int)$tokenData['tickets_id'];
        $ticketSummary = $context['ticket'];
        include(GLPI_ROOT . '/plugins/mailaprove/templates/satisfaction_form.php');
        exit;
    }

    $claim = Token::claimTokenWithStatus($rawToken, Token::ACTION_SATISFACTION);
    if (!$claim['valid']) {
        PublicAction::renderError(PublicAction::tokenErrorContent($claim['error']));
        exit;
    }
    $tokenData = (array) $claim['data'];

    $context = PublicAction::satisfactionContext($tokenData, true);
    if (!$context['ok']) {
        PublicAction::renderError($context);
        exit;
    }

    $satisfaction = $context['item'];
    $updateResult = $satisfaction->update([
        'id'            => (int)$tokenData['items_id'],
        'satisfaction'   => $rating,
        'comment'        => $comment,
        'date_answered'  => date('Y-m-d H:i:s'),
    ]);

    if ($updateResult) {
        Token::markRelatedAsUsed([
            Token::ACTION_SATISFACTION,
        ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
        AuditLog::record('satisfaction_submitted', 'success', AuditLog::contextFromTokenRow($tokenData, [
            'payload' => ['rating' => $rating],
        ]));

        $confirmTitle = __('Pesquisa enviada', 'mailaprove');
        $confirmMessage = sprintf(
            __('Obrigado pelo feedback sobre o chamado #%d.', 'mailaprove'),
            (int)$tokenData['tickets_id']
        );
        $confirmType = 'success';
        include(GLPI_ROOT . '/plugins/mailaprove/templates/confirm.php');
    } else {
        AuditLog::record('satisfaction_submit_failed', 'error', AuditLog::contextFromTokenRow($tokenData));
        $errorTitle = __('Erro ao processar', 'mailaprove');
        $errorMessage = __('Não foi possível enviar a pesquisa. Acesse o GLPI para verificar o chamado.', 'mailaprove');
        include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    }
    exit;
}

// GET: Show satisfaction form
$ticketId = (int)$tokenData['tickets_id'];
$formError = '';
$ticketSummary = $context['ticket'];
include(GLPI_ROOT . '/plugins/mailaprove/templates/satisfaction_form.php');
