<?php

declare(strict_types=1);

include('../../../inc/includes.php');

Session::checkRight('config', READ);
Html::header_nocache();
header('Content-Type: application/json; charset=utf-8');

$type = (string) ($_POST['type'] ?? '');
$template = trim((string) ($_POST['template'] ?? ''));

$allowedTypes = [
    'template_validation',
    'template_solution',
    'template_satisfaction',
];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode([
        'ok'      => false,
        'html'    => '',
        'message' => __('Tipo de modelo desconhecido.', 'mailaprove'),
    ], JSON_THROW_ON_ERROR);
    return;
}

if ($template === '') {
    echo json_encode([
        'ok'      => true,
        'html'    => renderMailaprovePreviewShell(
            '<p style="margin:0;color:#667085;font-size:14px;">'
            . htmlspecialchars(__('Este modelo está vazio. O bloco padrão do plugin será usado.', 'mailaprove'), ENT_QUOTES, 'UTF-8')
            . '</p>'
        ),
        'message' => __('Prévia de modelo vazio.', 'mailaprove'),
    ], JSON_THROW_ON_ERROR);
    return;
}

$baseUrl = 'https://glpi.example/plugins/mailaprove/front/';
$values = [
    'ticket_id'       => '12345',
    'ticket_name'     => __('Solicitação de exemplo para acesso a notebook', 'mailaprove'),
    'approve_url'     => $baseUrl . 'approve.php?token=preview',
    'accept_url'      => $baseUrl . 'solution_approve.php?token=preview',
    'reject_url'      => $baseUrl . 'reject.php?token=preview',
    'survey_url'      => $baseUrl . 'satisfaction.php?token=preview',
    'url'             => $baseUrl . 'satisfaction.php?token=preview',
    'primary_url'     => $baseUrl . 'approve.php?token=preview',
    'secondary_url'   => $baseUrl . 'reject.php?token=preview',
    'approve_label'   => __('Aprovar', 'mailaprove'),
    'accept_label'    => __('Aceitar solução', 'mailaprove'),
    'survey_label'    => __('Responder pesquisa', 'mailaprove'),
    'primary_label'   => __('Aprovar', 'mailaprove'),
    'reject_label'    => __('Recusar', 'mailaprove'),
    'secondary_label' => __('Recusar', 'mailaprove'),
    'label'           => __('Responder pesquisa', 'mailaprove'),
];

if ($type === 'template_solution') {
    $values['primary_url'] = $values['accept_url'];
    $values['primary_label'] = $values['accept_label'];
    $values['reject_url'] = $baseUrl . 'solution_reject.php?token=preview';
    $values['secondary_url'] = $values['reject_url'];
    $values['reject_label'] = __('Recusar solução', 'mailaprove');
    $values['secondary_label'] = $values['reject_label'];
}

if ($type === 'template_satisfaction') {
    $values['primary_url'] = $values['survey_url'];
    $values['primary_label'] = $values['survey_label'];
}

$replace = [];
foreach ($values as $key => $value) {
    $replace['{{' . $key . '}}'] = (string) $value;
}

echo json_encode([
    'ok'      => true,
    'html'    => renderMailaprovePreviewShell(strtr($template, $replace)),
    'message' => __('Prévia gerada com dados de exemplo.', 'mailaprove'),
], JSON_THROW_ON_ERROR);

function renderMailaprovePreviewShell(string $content): string
{
    return '<!doctype html><html><head><meta charset="utf-8">'
        . '<style>'
        . 'body{margin:0;padding:18px;background:#f6f8fb;color:#101828;font-family:Arial,Helvetica,sans-serif;}'
        . '.mailaprove-preview-mail{max-width:760px;margin:0 auto;padding:22px;background:#fff;border:1px solid #d9e1ec;border-radius:8px;}'
        . 'a{cursor:pointer;}'
        . '</style></head><body><div class="mailaprove-preview-mail">'
        . $content
        . '</div></body></html>';
}
