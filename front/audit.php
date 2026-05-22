<?php

include('../../../inc/includes.php');

use GlpiPlugin\Mailaprove\AuditLog;

Session::checkRight('config', READ);

AuditLog::ensureTable();

global $DB;

$status = trim((string) ($_GET['status'] ?? ''));
$event = trim((string) ($_GET['event'] ?? ''));
$start = max(0, (int) ($_GET['_start'] ?? 0));
$limit = (int) ($_GET['_limit'] ?? 50);
$limit = in_array($limit, [25, 50, 100, 200], true) ? $limit : 50;

$where = [];
if ($status !== '') {
    $where['status'] = $status;
}
if ($event !== '') {
    $where['event'] = ['LIKE', '%' . $event . '%'];
}

if (($_GET['export'] ?? '') === 'csv') {
    Html::header_nocache();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mailaprove-auditoria-' . date('Ymd-His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'id',
        'status',
        'evento',
        'acao',
        'chamado',
        'item',
        'usuario',
        'token',
        'ip',
        'user_agent',
        'mensagem',
        'payload',
        'data',
    ]);

    foreach ($DB->request([
        'FROM'  => AuditLog::$table,
        'WHERE' => $where,
        'ORDER' => ['id DESC'],
        'LIMIT' => 5000,
    ]) as $row) {
        fputcsv($output, [
            (int) ($row['id'] ?? 0),
            (string) ($row['status'] ?? ''),
            (string) ($row['event'] ?? ''),
            (string) ($row['action_type'] ?? ''),
            (int) ($row['tickets_id'] ?? 0),
            (int) ($row['items_id'] ?? 0),
            (int) ($row['users_id'] ?? 0),
            (int) ($row['token_id'] ?? 0),
            (string) ($row['ip'] ?? ''),
            (string) ($row['user_agent'] ?? ''),
            (string) ($row['message'] ?? ''),
            (string) ($row['payload'] ?? ''),
            (string) ($row['date_creation'] ?? ''),
        ]);
    }

    fclose($output);
    exit;
}

$total = count($DB->request([
    'FROM'  => AuditLog::$table,
    'WHERE' => $where,
]));

$rows = [];
foreach ($DB->request([
    'FROM'  => AuditLog::$table,
    'WHERE' => $where,
    'ORDER' => ['id DESC'],
    'START' => $start,
    'LIMIT' => $limit,
]) as $row) {
    $rows[] = $row;
}

Html::header(
    __('Auditoria da aprovação por e-mail', 'mailaprove'),
    $_SERVER['PHP_SELF'],
    'config',
    'GlpiPlugin\\Mailaprove\\Config'
);

$statusOptions = [
    ''        => __('Todos os status', 'mailaprove'),
    'success' => __('Sucesso', 'mailaprove'),
    'warning' => __('Alerta', 'mailaprove'),
    'error'   => __('Erro', 'mailaprove'),
    'info'    => __('Informação', 'mailaprove'),
];

function mailaprove_audit_status_class(string $status): string
{
    return match ($status) {
        'success' => 'success',
        'warning' => 'warning',
        'error'   => 'error',
        default   => 'info',
    };
}
?>

<style>
    .mailaprove-audit-page {
        --mailaprove-primary: #2563eb;
        --mailaprove-accent: #0f766e;
        --mailaprove-border: var(--tblr-border-color, #d9dee8);
        --mailaprove-surface: var(--tblr-bg-surface, #fff);
        --mailaprove-muted: var(--tblr-secondary-color, #667085);
        max-width: 1240px;
        margin: 0 auto;
    }

    .mailaprove-audit-hero,
    .mailaprove-audit-panel {
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .mailaprove-audit-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.2rem;
        background:
            linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(15, 118, 110, 0.08)),
            var(--mailaprove-surface);
    }

    .mailaprove-audit-hero__title {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 750;
        letter-spacing: 0;
    }

    .mailaprove-audit-hero__subtitle {
        margin: 0.25rem 0 0;
        color: var(--mailaprove-muted);
        font-size: 0.9rem;
    }

    .mailaprove-audit-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: end;
        padding: 1rem;
        margin-top: 1rem;
    }

    .mailaprove-audit-toolbar label {
        display: grid;
        gap: 0.3rem;
        min-width: 190px;
        font-size: 0.8rem;
        color: var(--mailaprove-muted);
        font-weight: 650;
    }

    .mailaprove-audit-toolbar input,
    .mailaprove-audit-toolbar select {
        min-height: 38px;
        border: 1px solid var(--mailaprove-border);
        border-radius: 8px;
        background: var(--mailaprove-surface);
        color: inherit;
        padding: 0.45rem 0.65rem;
    }

    .mailaprove-audit-panel {
        margin-top: 1rem;
        overflow: hidden;
    }

    .mailaprove-audit-table {
        width: 100%;
        border-collapse: collapse;
    }

    .mailaprove-audit-table th,
    .mailaprove-audit-table td {
        padding: 0.8rem;
        border-bottom: 1px solid var(--mailaprove-border);
        vertical-align: top;
        text-align: left;
        font-size: 0.86rem;
    }

    .mailaprove-audit-table th {
        color: var(--mailaprove-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: color-mix(in srgb, var(--mailaprove-surface) 92%, var(--mailaprove-primary) 8%);
    }

    .mailaprove-audit-badge {
        display: inline-flex;
        min-width: 76px;
        justify-content: center;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .mailaprove-audit-badge--success { color: #0f766e; background: rgba(15, 118, 110, 0.1); }
    .mailaprove-audit-badge--warning { color: #b45309; background: rgba(180, 83, 9, 0.1); }
    .mailaprove-audit-badge--error { color: #b91c1c; background: rgba(185, 28, 28, 0.1); }
    .mailaprove-audit-badge--info { color: #2563eb; background: rgba(37, 99, 235, 0.1); }

    .mailaprove-audit-muted {
        color: var(--mailaprove-muted);
        font-size: 0.8rem;
    }

    .mailaprove-audit-empty {
        padding: 2rem;
        color: var(--mailaprove-muted);
        text-align: center;
    }

    .mailaprove-audit-pagination {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: center;
        padding: 1rem;
        color: var(--mailaprove-muted);
        font-size: 0.86rem;
    }

    @media (max-width: 760px) {
        .mailaprove-audit-hero,
        .mailaprove-audit-pagination {
            align-items: flex-start;
            flex-direction: column;
        }

        .mailaprove-audit-table {
            min-width: 860px;
        }

        .mailaprove-audit-panel {
            overflow-x: auto;
        }
    }
</style>

<div class="container-fluid mailaprove-audit-page">
    <section class="mailaprove-audit-hero">
        <div>
            <h2 class="mailaprove-audit-hero__title"><?= __('Auditoria da aprovação por e-mail', 'mailaprove') ?></h2>
            <p class="mailaprove-audit-hero__subtitle"><?= __('Revise links gerados, cliques, tokens expirados e tentativas com falha.', 'mailaprove') ?></p>
        </div>
        <span class="d-flex gap-2 flex-wrap justify-content-end">
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars('audit.php?status=' . urlencode($status) . '&event=' . urlencode($event) . '&export=csv', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-file-csv" aria-hidden="true"></i>
                <?= __('Exportar CSV', 'mailaprove') ?>
            </a>
            <a class="btn btn-outline-primary" href="config.form.php">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <?= __('Voltar para configuração', 'mailaprove') ?>
            </a>
        </span>
    </section>

    <form method="get" class="mailaprove-audit-panel mailaprove-audit-toolbar">
        <label>
            <?= __('Status', 'mailaprove') ?>
            <select name="status">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= __('Evento contém', 'mailaprove') ?>
            <input type="search" name="event" value="<?= htmlspecialchars($event, ENT_QUOTES, 'UTF-8') ?>" placeholder="token_used">
        </label>
        <label>
            <?= __('Por página', 'mailaprove') ?>
            <select name="_limit">
                <?php foreach ([25, 50, 100, 200] as $option): ?>
                    <option value="<?= $option ?>" <?= $limit === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter" aria-hidden="true"></i>
            <?= __('Aplicar', 'mailaprove') ?>
        </button>
        <a class="btn btn-outline-secondary" href="audit.php"><?= __('Limpar', 'mailaprove') ?></a>
    </form>

    <section class="mailaprove-audit-panel">
        <?php if ($rows === []): ?>
            <div class="mailaprove-audit-empty"><?= __('Nenhum evento de auditoria encontrado.', 'mailaprove') ?></div>
        <?php else: ?>
            <table class="mailaprove-audit-table">
                <thead>
                    <tr>
                        <th><?= __('Status', 'mailaprove') ?></th>
                        <th><?= __('Evento', 'mailaprove') ?></th>
                        <th><?= __('Chamado', 'mailaprove') ?></th>
                        <th><?= __('Usuário', 'mailaprove') ?></th>
                        <th><?= __('IP / Agente', 'mailaprove') ?></th>
                        <th><?= __('Data', 'mailaprove') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $rowStatus = (string) ($row['status'] ?? 'info'); ?>
                        <tr>
                            <td>
                                <span class="mailaprove-audit-badge mailaprove-audit-badge--<?= mailaprove_audit_status_class($rowStatus) ?>">
                                    <?= htmlspecialchars($rowStatus, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars((string) ($row['event'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <div class="mailaprove-audit-muted"><?= htmlspecialchars((string) ($row['action_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($row['message'])): ?>
                                    <div class="mailaprove-audit-muted"><?= htmlspecialchars((string) $row['message'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>#<?= (int) ($row['tickets_id'] ?? 0) ?><div class="mailaprove-audit-muted"><?= __('item', 'mailaprove') ?> <?= (int) ($row['items_id'] ?? 0) ?></div></td>
                            <td>#<?= (int) ($row['users_id'] ?? 0) ?><div class="mailaprove-audit-muted"><?= __('token', 'mailaprove') ?> <?= (int) ($row['token_id'] ?? 0) ?></div></td>
                            <td>
                                <?= htmlspecialchars((string) ($row['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <div class="mailaprove-audit-muted"><?= htmlspecialchars((string) ($row['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($row['date_creation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php
            $pageFrom = $total > 0 ? $start + 1 : 0;
            $pageTo = min($start + count($rows), $total);
            $queryBase = 'audit.php?status=' . urlencode($status) . '&event=' . urlencode($event) . '&_limit=' . $limit;
        ?>
        <div class="mailaprove-audit-pagination">
            <span><?= sprintf(__('%1$d-%2$d de %3$d eventos', 'mailaprove'), $pageFrom, $pageTo, $total) ?></span>
            <span>
                <?php if ($start > 0): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($queryBase . '&_start=' . max(0, $start - $limit), ENT_QUOTES, 'UTF-8') ?>"><?= __('Anterior', 'mailaprove') ?></a>
                <?php endif; ?>
                <?php if ($start + $limit < $total): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($queryBase . '&_start=' . ($start + $limit), ENT_QUOTES, 'UTF-8') ?>"><?= __('Próxima', 'mailaprove') ?></a>
                <?php endif; ?>
            </span>
        </div>
    </section>
</div>

<?php
Html::footer();
