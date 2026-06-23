<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\Config;
use GlpiPlugin\Mailaprove\AuditLog;

Session::checkRight('config', UPDATE);

Html::header(
    __('Aprovação por e-mail', 'mailaprove'),
    $_SERVER['PHP_SELF'],
    'config',
    'GlpiPlugin\\Mailaprove\\Config'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::checkCSRF($_POST);

    Config::updateConfig([
        'token_expiration_hours'    => max(1, min(8760, (int) ($_POST['token_expiration_hours'] ?? 72))),
        'used_token_retention_days' => max(1, min(3650, (int) ($_POST['used_token_retention_days'] ?? 30))),
        'audit_retention_days'      => max(1, min(3650, (int) ($_POST['audit_retention_days'] ?? 180))),
        'enable_validation'         => isset($_POST['enable_validation']) ? 1 : 0,
        'enable_solution'           => isset($_POST['enable_solution']) ? 1 : 0,
        'enable_satisfaction'       => isset($_POST['enable_satisfaction']) ? 1 : 0,
        'template_validation'       => (string) ($_POST['template_validation'] ?? ''),
        'template_solution'         => (string) ($_POST['template_solution'] ?? ''),
        'template_satisfaction'     => (string) ($_POST['template_satisfaction'] ?? ''),
    ]);

    Session::addMessageAfterRedirect(
        __('Configuração atualizada com sucesso.', 'mailaprove'),
        true,
        INFO
    );

    Html::redirect($_SERVER['PHP_SELF']);
}

$config = Config::getConfig();
global $CFG_GLPI;
$templatePreviewUrl = ($CFG_GLPI['root_doc'] ?? '') . '/plugins/mailaprove/ajax/template.preview.php';

$enabledCount = 0;
$enabledCount += !empty($config['enable_validation']) ? 1 : 0;
$enabledCount += !empty($config['enable_solution']) ? 1 : 0;
$enabledCount += !empty($config['enable_satisfaction']) ? 1 : 0;

$features = [
    [
        'id'          => 'enable_validation',
        'name'        => 'enable_validation',
        'icon'        => 'fa-check-double',
        'title'       => __('Aprovação de validação', 'mailaprove'),
        'description' => __('Permite aprovar ou recusar validações de chamados por e-mail', 'mailaprove'),
        'enabled'     => !empty($config['enable_validation']),
    ],
    [
        'id'          => 'enable_solution',
        'name'        => 'enable_solution',
        'icon'        => 'fa-clipboard-check',
        'title'       => __('Aceite de solução', 'mailaprove'),
        'description' => __('Permite aceitar ou recusar soluções de chamados por e-mail', 'mailaprove'),
        'enabled'     => !empty($config['enable_solution']),
    ],
    [
        'id'          => 'enable_satisfaction',
        'name'        => 'enable_satisfaction',
        'icon'        => 'fa-star',
        'title'       => __('Pesquisa de satisfação', 'mailaprove'),
        'description' => __('Permite responder pesquisas de satisfação por e-mail', 'mailaprove'),
        'enabled'     => !empty($config['enable_satisfaction']),
    ],
];

$tags = [
    ['tag' => '##ticket.validation.buttons##', 'description' => __('Botões prontos para aprovar ou recusar uma validação', 'mailaprove')],
    ['tag' => '##ticket.validation.accepturl##', 'description' => __('URL para aprovar uma validação', 'mailaprove')],
    ['tag' => '##ticket.validation.rejecturl##', 'description' => __('URL para recusar uma validação', 'mailaprove')],
    ['tag' => '##ticket.solution.buttons##', 'description' => __('Botões prontos para aceitar ou recusar uma solução', 'mailaprove')],
    ['tag' => '##ticket.solution.accepturl##', 'description' => __('URL para aceitar a solução do chamado', 'mailaprove')],
    ['tag' => '##ticket.solution.rejecturl##', 'description' => __('URL para recusar a solução do chamado', 'mailaprove')],
    ['tag' => '##ticket.satisfaction.button##', 'description' => __('Botão pronto para responder a pesquisa de satisfação', 'mailaprove')],
    ['tag' => '##ticket.satisfaction.url##', 'description' => __('URL da pesquisa de satisfação', 'mailaprove')],
];

$templateSnippets = [
    [
        'title'       => __('Bloco de e-mail para validação', 'mailaprove'),
        'description' => __('Use no modelo de notificação de validação do chamado.', 'mailaprove'),
        'icon'        => 'fa-check-double',
        'html'        => '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:18px 0;">
  <tr>
    <td style="padding:16px; border:1px solid #d9e1ec; border-radius:8px; background:#f8fafc;">
      <p style="margin:0 0 12px; color:#344054; font-size:14px;">Revise a validação solicitada para este chamado.</p>
      <a href="##ticket.validation.accepturl##" style="display:inline-block; padding:10px 16px; margin-right:8px; border-radius:6px; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:700;">Aprovar</a>
      <a href="##ticket.validation.rejecturl##" style="display:inline-block; padding:10px 16px; border-radius:6px; background:#b91c1c; color:#ffffff; text-decoration:none; font-weight:700;">Rejeitar</a>
    </td>
  </tr>
</table>',
    ],
    [
        'title'       => __('Bloco de e-mail para solução', 'mailaprove'),
        'description' => __('Use no modelo de notificação de solução do chamado.', 'mailaprove'),
        'icon'        => 'fa-clipboard-check',
        'html'        => '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:18px 0;">
  <tr>
    <td style="padding:16px; border:1px solid #d9e1ec; border-radius:8px; background:#f8fafc;">
      <p style="margin:0 0 12px; color:#344054; font-size:14px;">A solução foi registrada. Confirme se o atendimento resolveu sua solicitação.</p>
      <a href="##ticket.solution.accepturl##" style="display:inline-block; padding:10px 16px; margin-right:8px; border-radius:6px; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:700;">Aceitar solução</a>
      <a href="##ticket.solution.rejecturl##" style="display:inline-block; padding:10px 16px; border-radius:6px; background:#b45309; color:#ffffff; text-decoration:none; font-weight:700;">Rejeitar solução</a>
    </td>
  </tr>
</table>',
    ],
    [
        'title'       => __('Bloco de e-mail para satisfação', 'mailaprove'),
        'description' => __('Use no modelo de notificação da pesquisa de satisfação.', 'mailaprove'),
        'icon'        => 'fa-star',
        'html'        => '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; margin:18px 0;">
  <tr>
    <td style="padding:16px; border:1px solid #d9e1ec; border-radius:8px; background:#f8fafc;">
      <p style="margin:0 0 12px; color:#344054; font-size:14px;">Sua opinião ajuda a melhorar o atendimento.</p>
      <a href="##ticket.satisfaction.url##" style="display:inline-block; padding:10px 16px; border-radius:6px; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:700;">Responder pesquisa</a>
    </td>
  </tr>
</table>',
    ],
];

$customTemplateFields = [
    [
        'name'        => 'template_validation',
        'title'       => __('Modelo customizado da validação', 'mailaprove'),
        'description' => __('Variáveis disponíveis: {{approve_url}}, {{reject_url}}, {{ticket_id}}, {{ticket_name}}.', 'mailaprove'),
        'example'     => '<p>Chamado #{{ticket_id}}: {{ticket_name}}</p>
<p>
  <a href="{{approve_url}}" style="display:inline-block;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">Aprovar</a>
  <a href="{{reject_url}}" style="display:inline-block;padding:10px 16px;background:#b91c1c;color:#fff;text-decoration:none;border-radius:6px;">Rejeitar</a>
</p>',
    ],
    [
        'name'        => 'template_solution',
        'title'       => __('Modelo customizado da solução', 'mailaprove'),
        'description' => __('Variáveis disponíveis: {{accept_url}}, {{reject_url}}, {{ticket_id}}, {{ticket_name}}.', 'mailaprove'),
        'example'     => '<p>A solução do chamado #{{ticket_id}} foi registrada.</p>
<p>
  <a href="{{accept_url}}" style="display:inline-block;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">Aceitar solução</a>
  <a href="{{reject_url}}" style="display:inline-block;padding:10px 16px;background:#b45309;color:#fff;text-decoration:none;border-radius:6px;">Rejeitar solução</a>
</p>',
    ],
    [
        'name'        => 'template_satisfaction',
        'title'       => __('Modelo customizado da satisfação', 'mailaprove'),
        'description' => __('Variáveis disponíveis: {{survey_url}}, {{ticket_id}}, {{ticket_name}}.', 'mailaprove'),
        'example'     => '<p>Sua opinião sobre o chamado #{{ticket_id}} é importante.</p>
<p>
  <a href="{{survey_url}}" style="display:inline-block;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">Responder pesquisa</a>
</p>',
    ],
];

AuditLog::ensureTable();
$auditStats = ['success' => 0, 'warning' => 0, 'error' => 0, 'info' => 0];
$recentLogs = [];
global $DB;
foreach (array_keys($auditStats) as $status) {
    $auditStats[$status] = count($DB->request([
        'FROM'  => AuditLog::$table,
        'WHERE' => ['status' => $status],
    ]));
}
foreach ($DB->request([
    'FROM'  => AuditLog::$table,
    'ORDER' => ['id DESC'],
    'LIMIT' => 8,
]) as $row) {
    $recentLogs[] = $row;
}
?>

<style>
    .mailaprove-page {
        --mailaprove-primary: #2563eb;
        --mailaprove-accent: #2563eb;
        --mailaprove-warning: #b45309;
        --mailaprove-border: var(--tblr-border-color, #d9dee8);
        --mailaprove-surface: var(--tblr-bg-surface, #fff);
        --mailaprove-muted: var(--tblr-secondary-color, #667085);
        --mailaprove-text: var(--tblr-body-color, #182433);
        color: var(--mailaprove-text);
        max-width: 1180px;
        margin: 0 auto;
    }

    .mailaprove-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.75rem 1.85rem;
        border: 0;
        border-radius: 12px;
        color: #fff;
        background:
            radial-gradient(circle at 92% -30%, rgba(255, 255, 255, 0.25), transparent 40%),
            linear-gradient(135deg, #1e40af 0%, #2563eb 52%, #1d4ed8 100%);
        box-shadow: 0 8px 22px rgba(37, 99, 235, 0.14);
    }

    .mailaprove-hero__main {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .mailaprove-hero__icon {
        width: 58px;
        height: 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-size: 1.5rem;
        color: #fff;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.28);
        flex: 0 0 auto;
    }

    .mailaprove-hero__title {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 850;
        letter-spacing: -0.01em;
        color: #fff;
    }

    .mailaprove-hero__subtitle {
        margin: 0.25rem 0 0;
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.95rem;
    }

    .mailaprove-status {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.6rem 0.9rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        font-weight: 800;
        white-space: nowrap;
        backdrop-filter: blur(2px);
    }

    .mailaprove-grid {
        display: grid;
        grid-template-columns: minmax(280px, 0.9fr) minmax(360px, 1.4fr);
        gap: 1rem;
        margin-top: 1rem;
        align-items: start;
    }

    .mailaprove-panel {
        border: 1px solid var(--mailaprove-border);
        border-radius: 14px;
        background: var(--mailaprove-surface);
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .mailaprove-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid var(--mailaprove-border);
    }

    .mailaprove-panel__title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 750;
    }

    .mailaprove-panel__body {
        padding: 1.1rem;
    }

    .mailaprove-expiration {
        display: grid;
        grid-template-columns: 112px 1fr;
        gap: 1rem;
        align-items: center;
    }

    .mailaprove-expiration__value {
        width: 112px;
        height: 92px;
        border-radius: 8px;
        border: 1px solid rgba(37, 99, 235, 0.24);
        background: rgba(37, 99, 235, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mailaprove-expiration__value input {
        width: 76px;
        border: 0;
        background: transparent;
        text-align: center;
        color: var(--mailaprove-primary);
        font-size: 1.8rem;
        font-weight: 800;
        outline: none;
    }

    .mailaprove-expiration__label {
        margin: 0;
        font-weight: 700;
    }

    .mailaprove-expiration__help {
        margin: 0.25rem 0 0;
        color: var(--mailaprove-muted);
        font-size: 0.86rem;
    }

    .mailaprove-feature-list {
        display: grid;
        gap: 0.75rem;
    }

    .mailaprove-feature {
        display: grid;
        grid-template-columns: 42px 1fr auto;
        gap: 0.85rem;
        align-items: center;
        padding: 0.9rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }

    .mailaprove-feature:hover {
        border-color: rgba(37, 99, 235, 0.35);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.07);
        transform: translateY(-1px);
    }

    .mailaprove-feature__icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(37, 99, 235, 0.1);
        color: var(--mailaprove-primary);
    }

    .mailaprove-feature__title {
        display: block;
        margin: 0;
        font-weight: 750;
    }

    .mailaprove-feature__description {
        display: block;
        margin: 0.2rem 0 0;
        color: var(--mailaprove-muted);
        font-size: 0.86rem;
    }

    .mailaprove-feature .form-check-input {
        width: 3rem;
        height: 1.55rem;
        cursor: pointer;
    }

    .mailaprove-tags {
        display: grid;
        gap: 0.55rem;
    }

    .mailaprove-tag {
        display: grid;
        grid-template-columns: minmax(250px, 0.8fr) 1fr;
        gap: 0.75rem;
        align-items: center;
        padding: 0.7rem 0.8rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
    }

    .mailaprove-tag code {
        color: var(--mailaprove-primary);
        white-space: nowrap;
        font-size: 0.82rem;
    }

    .mailaprove-tag__description {
        color: var(--mailaprove-muted);
        font-size: 0.88rem;
    }

    .mailaprove-snippets {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .mailaprove-snippet {
        display: grid;
        gap: 0.75rem;
        min-width: 0;
        padding: 0.9rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
    }

    .mailaprove-snippet__head {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 0.7rem;
        align-items: center;
    }

    .mailaprove-snippet__icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: var(--mailaprove-primary);
        background: rgba(37, 99, 235, 0.1);
    }

    .mailaprove-snippet__title {
        display: block;
        font-weight: 750;
        line-height: 1.25;
    }

    .mailaprove-snippet__description {
        display: block;
        margin-top: 0.18rem;
        color: var(--mailaprove-muted);
        font-size: 0.82rem;
    }

    .mailaprove-snippet__code {
        min-height: 150px;
        max-height: 210px;
        overflow: auto;
        margin: 0;
        padding: 0.85rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: #0f172a;
        color: #dbeafe;
        font-size: 0.78rem;
        white-space: pre-wrap;
    }

    .mailaprove-copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 34px;
        border-radius: 8px;
        font-weight: 700;
    }

    .mailaprove-custom-templates {
        display: grid;
        gap: 0.9rem;
    }

    .mailaprove-custom-template {
        display: grid;
        gap: 0.65rem;
        padding: 0.9rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
    }

    .mailaprove-custom-template__top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .mailaprove-custom-template__title {
        display: block;
        font-weight: 750;
    }

    .mailaprove-custom-template__description {
        display: block;
        margin-top: 0.18rem;
        color: var(--mailaprove-muted);
        font-size: 0.82rem;
    }

    .mailaprove-custom-template__actions {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.45rem;
    }

    .mailaprove-custom-template textarea {
        width: 100%;
        min-height: 132px;
        padding: 0.75rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
        color: inherit;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.82rem;
        resize: vertical;
    }

    .mailaprove-custom-template textarea:focus {
        outline: none;
        border-color: var(--mailaprove-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
    }

    .mailaprove-template-preview {
        display: grid;
        gap: 0.75rem;
        margin-top: 1rem;
        padding: 0.9rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: #ffffff;
    }

    .mailaprove-template-preview[hidden] {
        display: none;
    }

    .mailaprove-template-preview__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .mailaprove-template-preview__title {
        margin: 0;
        font-weight: 750;
    }

    .mailaprove-template-preview__meta {
        margin: 0.15rem 0 0;
        color: var(--mailaprove-muted);
        font-size: 0.82rem;
    }

    .mailaprove-template-preview__frame {
        width: 100%;
        min-height: 220px;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: #fff;
    }

    .mailaprove-template-preview__empty {
        margin: 0;
        color: var(--mailaprove-muted);
        font-size: 0.86rem;
    }

    .mailaprove-preview {
        display: grid;
        grid-template-columns: minmax(320px, 1fr) minmax(320px, 1fr);
        gap: 0.85rem;
    }

    .mailaprove-preview-card {
        padding: 1rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(37, 99, 235, 0.06));
    }

    .mailaprove-preview-card__title {
        margin: 0 0 0.35rem;
        font-weight: 750;
    }

    .mailaprove-preview-card__text {
        margin: 0 0 0.8rem;
        color: var(--mailaprove-muted);
        font-size: 0.88rem;
    }

    .mailaprove-preview-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .mailaprove-preview-actions a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0.45rem 0.7rem;
        border-radius: 8px;
        color: #fff;
        font-size: 0.84rem;
        font-weight: 750;
        text-decoration: none;
    }

    .mailaprove-preview-actions .is-approve {
        background: #2563eb;
    }

    .mailaprove-preview-actions .is-primary {
        background: #2563eb;
    }

    .mailaprove-preview-actions .is-reject {
        background: #b91c1c;
    }

    .mailaprove-audit-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .mailaprove-audit-card {
        padding: 0.85rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
    }

    .mailaprove-audit-card__value {
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--mailaprove-primary);
        line-height: 1.1;
    }

    .mailaprove-audit-card__label {
        display: block;
        margin-top: 0.25rem;
        color: var(--mailaprove-muted);
        font-size: 0.78rem;
        font-weight: 650;
        text-transform: uppercase;
    }

    .mailaprove-audit-list {
        display: grid;
        gap: 0.55rem;
    }

    .mailaprove-audit-row {
        display: grid;
        grid-template-columns: 120px 1fr auto;
        gap: 0.8rem;
        align-items: center;
        padding: 0.72rem 0.8rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
    }

    .mailaprove-audit-row__event {
        font-weight: 750;
    }

    .mailaprove-audit-row__meta {
        color: var(--mailaprove-muted);
        font-size: 0.82rem;
    }

    .mailaprove-audit-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 76px;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .mailaprove-audit-badge--success {
        color: #1d4ed8;
        background: rgba(29, 78, 216, 0.12);
    }

    .mailaprove-audit-badge--warning {
        color: #b45309;
        background: rgba(180, 83, 9, 0.1);
    }

    .mailaprove-audit-badge--error {
        color: #b91c1c;
        background: rgba(185, 28, 28, 0.1);
    }

    .mailaprove-audit-badge--info {
        color: #2563eb;
        background: rgba(37, 99, 235, 0.1);
    }

    .mailaprove-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .mailaprove-save {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border: 0;
        border-radius: 12px;
        padding: 0.8rem 1.6rem;
        font-size: 1rem;
        font-weight: 850;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);
        transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
    }

    .mailaprove-save:hover {
        color: #fff;
        filter: brightness(1.06);
        transform: translateY(-1px);
        box-shadow: 0 18px 32px rgba(37, 99, 235, 0.36);
    }

    @media (max-width: 992px) {
        .mailaprove-grid,
        .mailaprove-tag,
        .mailaprove-snippets,
        .mailaprove-preview,
        .mailaprove-audit-row {
            grid-template-columns: 1fr;
        }

        .mailaprove-audit-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mailaprove-hero {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 560px) {
        .mailaprove-expiration,
        .mailaprove-feature {
            grid-template-columns: 1fr;
        }

        .mailaprove-status {
            width: 100%;
            justify-content: center;
        }

        .mailaprove-audit-stats {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .mailaprove-feature,
        .mailaprove-feature:hover,
        .mailaprove-save,
        .mailaprove-save:hover {
            transition: none;
            transform: none;
        }
    }
</style>

<div class="container-fluid mailaprove-page">
    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_glpi_csrf_token" value="<?= htmlspecialchars(Session::getNewCSRFToken(), ENT_QUOTES, 'UTF-8') ?>">

        <section class="mailaprove-hero">
            <div class="mailaprove-hero__main">
                <span class="mailaprove-hero__icon" aria-hidden="true">
                    <i class="fas fa-envelope-open-text"></i>
                </span>
                <div>
                    <h2 class="mailaprove-hero__title"><?= __('Configuração da aprovação por e-mail', 'mailaprove') ?></h2>
                    <p class="mailaprove-hero__subtitle"><?= __('Controle links de aprovação para validações, soluções e pesquisas de satisfação.', 'mailaprove') ?></p>
                </div>
            </div>
            <div class="mailaprove-status">
                <i class="fas fa-toggle-on" aria-hidden="true"></i>
                <span><?= sprintf(__('%d de 3 recursos ativos', 'mailaprove'), $enabledCount) ?></span>
            </div>
        </section>

        <div class="mailaprove-grid">
            <section class="mailaprove-panel">
                <div class="mailaprove-panel__header">
                    <h3 class="mailaprove-panel__title"><?= __('Configurações gerais', 'mailaprove') ?></h3>
                    <i class="fas fa-clock text-muted" aria-hidden="true"></i>
                </div>
                <div class="mailaprove-panel__body">
                    <div class="mailaprove-expiration">
                        <label class="mailaprove-expiration__value" for="token_expiration_hours">
                            <input type="number"
                                   id="token_expiration_hours"
                                   name="token_expiration_hours"
                                   value="<?= (int) $config['token_expiration_hours'] ?>"
                                   min="1"
                                   max="8760">
                        </label>
                        <div>
                            <p class="mailaprove-expiration__label"><?= __('Expiração do token (horas)', 'mailaprove') ?></p>
                            <p class="mailaprove-expiration__help"><?= __('Os links enviados por e-mail expiram após este período.', 'mailaprove') ?></p>
                        </div>
                    </div>
                    <div class="mailaprove-expiration mt-3">
                        <label class="mailaprove-expiration__value" for="used_token_retention_days">
                            <input type="number"
                                   id="used_token_retention_days"
                                   name="used_token_retention_days"
                                   value="<?= (int) $config['used_token_retention_days'] ?>"
                                   min="1"
                                   max="3650">
                        </label>
                        <div>
                            <p class="mailaprove-expiration__label"><?= __('Retenção de tokens usados (dias)', 'mailaprove') ?></p>
                            <p class="mailaprove-expiration__help"><?= __('Mantém tokens processados por este período para investigação e rastreabilidade.', 'mailaprove') ?></p>
                        </div>
                    </div>
                    <div class="mailaprove-expiration mt-3">
                        <label class="mailaprove-expiration__value" for="audit_retention_days">
                            <input type="number"
                                   id="audit_retention_days"
                                   name="audit_retention_days"
                                   value="<?= (int) $config['audit_retention_days'] ?>"
                                   min="1"
                                   max="3650">
                        </label>
                        <div>
                            <p class="mailaprove-expiration__label"><?= __('Retenção da auditoria (dias)', 'mailaprove') ?></p>
                            <p class="mailaprove-expiration__help"><?= __('Eventos mais antigos serão removidos pelo cron do plugin.', 'mailaprove') ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mailaprove-panel">
                <div class="mailaprove-panel__header">
                    <h3 class="mailaprove-panel__title"><?= __('Recursos', 'mailaprove') ?></h3>
                    <i class="fas fa-sliders-h text-muted" aria-hidden="true"></i>
                </div>
                <div class="mailaprove-panel__body">
                    <div class="mailaprove-feature-list">
                        <?php foreach ($features as $feature): ?>
                            <label class="mailaprove-feature" for="<?= htmlspecialchars($feature['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <span class="mailaprove-feature__icon" aria-hidden="true">
                                    <i class="fas <?= htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <span>
                                    <span class="mailaprove-feature__title"><?= $feature['title'] ?></span>
                                    <span class="mailaprove-feature__description"><?= $feature['description'] ?></span>
                                </span>
                                <span class="form-check form-switch m-0">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           id="<?= htmlspecialchars($feature['id'], ENT_QUOTES, 'UTF-8') ?>"
                                           name="<?= htmlspecialchars($feature['name'], ENT_QUOTES, 'UTF-8') ?>"
                                           <?= $feature['enabled'] ? 'checked' : '' ?>>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <section class="mailaprove-panel mt-3">
            <div class="mailaprove-panel__header">
                <h3 class="mailaprove-panel__title"><?= __('Tags dos modelos de notificação', 'mailaprove') ?></h3>
                <i class="fas fa-code text-muted" aria-hidden="true"></i>
            </div>
            <div class="mailaprove-panel__body">
                <div class="mailaprove-tags">
                    <?php foreach ($tags as $tag): ?>
                        <div class="mailaprove-tag">
                            <code><?= htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8') ?></code>
                            <span class="mailaprove-tag__description"><?= $tag['description'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="mailaprove-panel mt-3">
            <div class="mailaprove-panel__header">
                <h3 class="mailaprove-panel__title"><?= __('Blocos de e-mail prontos', 'mailaprove') ?></h3>
                <i class="fas fa-copy text-muted" aria-hidden="true"></i>
            </div>
            <div class="mailaprove-panel__body">
                <div class="mailaprove-snippets">
                    <?php foreach ($templateSnippets as $index => $snippet): ?>
                        <?php $snippetId = 'mailaprove-snippet-' . $index; ?>
                        <article class="mailaprove-snippet">
                            <div class="mailaprove-snippet__head">
                                <span class="mailaprove-snippet__icon" aria-hidden="true">
                                    <i class="fas <?= htmlspecialchars($snippet['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <span>
                                    <span class="mailaprove-snippet__title"><?= $snippet['title'] ?></span>
                                    <span class="mailaprove-snippet__description"><?= $snippet['description'] ?></span>
                                </span>
                            </div>
                            <pre class="mailaprove-snippet__code" id="<?= htmlspecialchars($snippetId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($snippet['html'], ENT_QUOTES, 'UTF-8') ?></pre>
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm mailaprove-copy-btn"
                                    data-mailaprove-copy-target="<?= htmlspecialchars($snippetId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-copy-label="<?= htmlspecialchars(__('Copiar HTML', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-copied-label="<?= htmlspecialchars(__('Copiado', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-copy" aria-hidden="true"></i>
                                <span><?= __('Copiar HTML', 'mailaprove') ?></span>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="mailaprove-panel mt-3">
            <div class="mailaprove-panel__header">
                <h3 class="mailaprove-panel__title"><?= __('Modelos customizados dos botões', 'mailaprove') ?></h3>
                <i class="fas fa-pen-nib text-muted" aria-hidden="true"></i>
            </div>
            <div class="mailaprove-panel__body">
                <div class="mailaprove-custom-templates">
                    <?php foreach ($customTemplateFields as $field): ?>
                        <?php
                            $fieldName = $field['name'];
                            $exampleId = $fieldName . '_example';
                        ?>
                        <div class="mailaprove-custom-template">
                            <div class="mailaprove-custom-template__top">
                                <span>
                                    <span class="mailaprove-custom-template__title"><?= $field['title'] ?></span>
                                    <span class="mailaprove-custom-template__description"><?= $field['description'] ?></span>
                                </span>
                                <span class="mailaprove-custom-template__actions">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm mailaprove-copy-btn"
                                            data-mailaprove-preview-target="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-mailaprove-preview-title="<?= htmlspecialchars($field['title'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                        <span><?= __('Prévia', 'mailaprove') ?></span>
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm mailaprove-copy-btn"
                                            data-mailaprove-fill-target="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-mailaprove-fill-source="<?= htmlspecialchars($exampleId, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                                        <span><?= __('Usar exemplo', 'mailaprove') ?></span>
                                    </button>
                                </span>
                            </div>
                            <textarea id="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                      name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
                                      spellcheck="false"
                                      placeholder="<?= htmlspecialchars(__('Deixe em branco para usar o modelo padrão do plugin.', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($config[$fieldName] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <template id="<?= htmlspecialchars($exampleId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($field['example'], ENT_QUOTES, 'UTF-8') ?></template>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mailaprove-template-preview"
                     data-mailaprove-template-preview
                     data-preview-url="<?= htmlspecialchars($templatePreviewUrl, ENT_QUOTES, 'UTF-8') ?>"
                     hidden>
                    <div class="mailaprove-template-preview__top">
                        <span>
                            <p class="mailaprove-template-preview__title" data-mailaprove-preview-heading><?= __('Prévia do modelo', 'mailaprove') ?></p>
                            <p class="mailaprove-template-preview__meta"><?= __('Dados de chamado fictício são usados somente nesta prévia.', 'mailaprove') ?></p>
                        </span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-mailaprove-preview-close>
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                            <?= __('Fechar', 'mailaprove') ?>
                        </button>
                    </div>
                    <iframe class="mailaprove-template-preview__frame"
                            title="<?= htmlspecialchars(__('Prévia do modelo customizado', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>"
                            sandbox
                            data-mailaprove-preview-frame></iframe>
                    <p class="mailaprove-template-preview__empty" data-mailaprove-preview-message></p>
                </div>
            </div>
        </section>

        <section class="mailaprove-panel mt-3">
            <div class="mailaprove-panel__header">
                <h3 class="mailaprove-panel__title"><?= __('Prévia do e-mail', 'mailaprove') ?></h3>
                <i class="fas fa-eye text-muted" aria-hidden="true"></i>
            </div>
            <div class="mailaprove-panel__body">
                <div class="mailaprove-preview">
                    <div class="mailaprove-preview-card">
                        <p class="mailaprove-preview-card__title"><?= __('Solicitação de validação', 'mailaprove') ?></p>
                        <p class="mailaprove-preview-card__text"><?= __('O e-mail gerado pode exibir ações de aprovação e recusa em botões claros.', 'mailaprove') ?></p>
                        <div class="mailaprove-preview-actions">
                            <a href="#" class="is-approve" onclick="return false;"><?= __('Aprovar', 'mailaprove') ?></a>
                            <a href="#" class="is-reject" onclick="return false;"><?= __('Recusar', 'mailaprove') ?></a>
                        </div>
                    </div>
                    <div class="mailaprove-preview-card">
                        <p class="mailaprove-preview-card__title"><?= __('Solução e satisfação', 'mailaprove') ?></p>
                        <p class="mailaprove-preview-card__text"><?= __('Use os blocos de solução e pesquisa nas notificações que seus usuários já recebem.', 'mailaprove') ?></p>
                        <div class="mailaprove-preview-actions">
                            <a href="#" class="is-primary" onclick="return false;"><?= __('Aceitar solução', 'mailaprove') ?></a>
                            <a href="#" class="is-primary" onclick="return false;"><?= __('Responder pesquisa', 'mailaprove') ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mailaprove-panel mt-3">
            <div class="mailaprove-panel__header">
                <h3 class="mailaprove-panel__title"><?= __('Trilha de auditoria', 'mailaprove') ?></h3>
                <a class="btn btn-outline-primary btn-sm" href="audit.php">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    <?= __('Ver tudo', 'mailaprove') ?>
                </a>
            </div>
            <div class="mailaprove-panel__body">
                <div class="mailaprove-audit-stats">
                    <?php foreach (['success', 'warning', 'error', 'info'] as $status): ?>
                        <div class="mailaprove-audit-card">
                            <span class="mailaprove-audit-card__value"><?= (int) ($auditStats[$status] ?? 0) ?></span>
                            <span class="mailaprove-audit-card__label"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mailaprove-audit-list">
                    <?php if ($recentLogs === []): ?>
                        <div class="mailaprove-tag">
                            <code><?= __('Nenhum evento ainda', 'mailaprove') ?></code>
                            <span class="mailaprove-tag__description"><?= __('Os eventos aparecerão aqui quando links forem gerados ou utilizados.', 'mailaprove') ?></span>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <?php $status = (string) ($log['status'] ?? 'info'); ?>
                        <div class="mailaprove-audit-row">
                            <span class="mailaprove-audit-badge mailaprove-audit-badge--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span>
                                <span class="mailaprove-audit-row__event"><?= htmlspecialchars((string) ($log['event'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="mailaprove-audit-row__meta">
                                    #<?= (int) ($log['tickets_id'] ?? 0) ?> ·
                                    <?= htmlspecialchars((string) ($log['action_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                            <span class="mailaprove-audit-row__meta"><?= htmlspecialchars((string) ($log['date_creation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="mailaprove-footer">
            <button type="submit" class="btn btn-primary mailaprove-save">
                <i class="fas fa-save" aria-hidden="true"></i>
                <span><?= __('Salvar configuração', 'mailaprove') ?></span>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('click', function (event) {
    const fillButton = event.target.closest('[data-mailaprove-fill-target]');
    if (fillButton) {
        const target = document.getElementById(fillButton.getAttribute('data-mailaprove-fill-target'));
        const source = document.getElementById(fillButton.getAttribute('data-mailaprove-fill-source'));
        if (target && source) {
            target.value = source.innerHTML;
            target.focus();
        }
        return;
    }

    const previewButton = event.target.closest('[data-mailaprove-preview-target]');
    if (previewButton) {
        previewTemplate(previewButton);
        return;
    }

    const closePreview = event.target.closest('[data-mailaprove-preview-close]');
    if (closePreview) {
        const preview = document.querySelector('[data-mailaprove-template-preview]');
        if (preview) {
            preview.hidden = true;
        }
        return;
    }

    const button = event.target.closest('[data-mailaprove-copy-target]');
    if (!button) {
        return;
    }

    const target = document.getElementById(button.getAttribute('data-mailaprove-copy-target'));
    if (!target) {
        return;
    }

    const value = target.textContent || '';
    const copiedLabel = button.getAttribute('data-copied-label') || 'Copiado';
    const copyLabel = button.getAttribute('data-copy-label') || 'Copiar HTML';
    const label = button.querySelector('span');

    function markCopied() {
        if (label) {
            label.textContent = copiedLabel;
            window.setTimeout(function () {
                label.textContent = copyLabel;
            }, 1300);
        }
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(markCopied).catch(function () {
            fallbackCopy(value);
            markCopied();
        });
        return;
    }

    fallbackCopy(value);
    markCopied();
});

function previewTemplate(button) {
    const preview = document.querySelector('[data-mailaprove-template-preview]');
    const frame = preview ? preview.querySelector('[data-mailaprove-preview-frame]') : null;
    const message = preview ? preview.querySelector('[data-mailaprove-preview-message]') : null;
    const heading = preview ? preview.querySelector('[data-mailaprove-preview-heading]') : null;
    const target = document.getElementById(button.getAttribute('data-mailaprove-preview-target'));
    const csrf = document.querySelector('input[name="_glpi_csrf_token"]');

    if (!preview || !frame || !target) {
        return;
    }

    const title = button.getAttribute('data-mailaprove-preview-title') || 'Prévia do modelo';
    if (heading) {
        heading.textContent = title;
    }
    if (message) {
        message.textContent = 'Carregando prévia...';
    }
    preview.hidden = false;
    preview.scrollIntoView({behavior: 'smooth', block: 'nearest'});

    const formData = new FormData();
    formData.append('type', target.name || target.id || '');
    formData.append('template', target.value || '');

    fetch(preview.getAttribute('data-preview-url'), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': csrf ? csrf.value : ''
        },
        body: formData,
        credentials: 'same-origin'
    }).then(function (response) {
        return response.json();
    }).then(function (data) {
        frame.srcdoc = data.html || '';
        if (message) {
            message.textContent = data.message || '';
        }
    }).catch(function () {
        frame.srcdoc = '';
        if (message) {
            message.textContent = 'Não foi possível gerar a prévia.';
        }
    });
}

function fallbackCopy(value) {
    const helper = document.createElement('textarea');
    helper.value = value;
    helper.setAttribute('readonly', '');
    helper.style.position = 'fixed';
    helper.style.left = '-9999px';
    helper.style.opacity = '0';
    document.body.appendChild(helper);
    helper.select();
    try {
        document.execCommand('copy');
    } catch (_e) {
    }
    document.body.removeChild(helper);
}
</script>

<?php
Html::footer();
