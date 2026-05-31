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

if ($tokenData['action_type'] !== Token::ACTION_SOLUTION_REJECT) {
    PublicAction::renderError(PublicAction::tokenErrorContent('invalid_action'));
    exit;
}

$context = PublicAction::solutionContext($tokenData);
if (!$context['ok']) {
    PublicAction::renderError($context);
    exit;
}

// POST: Process the rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');

    if (empty($comment)) {
        $formError = __('Informe o motivo da recusa da solução.', 'mailaprove');
        $ticketId = (int)$tokenData['tickets_id'];
        $ticketSummary = $context['ticket'];
        $formTitle = __('Recusar solução', 'mailaprove');
        $formAction = 'solution_reject';
        include(GLPI_ROOT . '/plugins/mailaprove/templates/reject_form.php');
        exit;
    }

    $claim = Token::claimTokenWithStatus($rawToken, Token::ACTION_SOLUTION_REJECT);
    if (!$claim['valid']) {
        PublicAction::renderError(PublicAction::tokenErrorContent($claim['error']));
        exit;
    }
    $tokenData = (array) $claim['data'];

    $context = PublicAction::solutionContext($tokenData, true);
    if (!$context['ok']) {
        PublicAction::renderError($context);
        exit;
    }

    $solution = $context['item'];
    $followup = new ITILFollowup();
    $followup->add([
        'items_id'   => $tokenData['tickets_id'],
        'itemtype'   => 'Ticket',
        'content'    => sprintf(
            __('Solução recusada por e-mail. Motivo: %s', 'mailaprove'),
            $comment
        ),
        'requesttypes_id' => 0,
    ]);

    $updateResult = $solution->update([
        'id'     => (int)$tokenData['items_id'],
        'status' => CommonITILValidation::REFUSED,
    ]);

    if ($updateResult) {
        Token::markRelatedAsUsed([
            Token::ACTION_SOLUTION_APPROVE,
            Token::ACTION_SOLUTION_REJECT,
        ], (int) $tokenData['tickets_id'], (int) $tokenData['items_id']);
        AuditLog::record('solution_rejected', 'success', AuditLog::contextFromTokenRow($tokenData));

        $confirmTitle = __('Solução recusada', 'mailaprove');
        $confirmMessage = sprintf(
            __('A solução do chamado #%d foi recusada.', 'mailaprove'),
            (int)$tokenData['tickets_id']
        );
        $confirmType = 'warning';
        include(GLPI_ROOT . '/plugins/mailaprove/templates/confirm.php');
    } else {
        AuditLog::record('solution_reject_failed', 'error', AuditLog::contextFromTokenRow($tokenData));
        $errorTitle = __('Erro ao processar', 'mailaprove');
        $errorMessage = __('Não foi possível recusar a solução. Acesse o GLPI para verificar o chamado.', 'mailaprove');
        include(GLPI_ROOT . '/plugins/mailaprove/templates/error.php');
    }
    exit;
}

// GET: Show rejection form
$ticketId = (int)$tokenData['tickets_id'];
$formError = '';
$ticketSummary = $context['ticket'];
$formTitle = __('Recusar solução', 'mailaprove');
$formAction = 'solution_reject';
include(GLPI_ROOT . '/plugins/mailaprove/templates/reject_form.php');
