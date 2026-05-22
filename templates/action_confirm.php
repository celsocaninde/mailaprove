<?php
/**
 * Explicit action confirmation page.
 *
 * Expected variables:
 * - $rawToken
 * - $actionUrl
 * - $confirmTitle
 * - $confirmMessage
 * - $confirmButton
 * - $confirmType
 * - $ticketSummary
 * - $confirmNote
 */

$confirmType = $confirmType ?? 'success';
$icon = match ($confirmType) {
    'warning' => '&#9888;',
    'danger'  => '&#10006;',
    default   => '&#10004;',
};
$buttonClass = match ($confirmType) {
    'warning' => 'abm-btn-danger',
    'danger'  => 'abm-btn-danger',
    default   => 'abm-btn-success',
};
$ticketSummary = $ticketSummary ?? [];
$ticketId = (int) ($ticketSummary['id'] ?? 0);
$ticketName = trim((string) ($ticketSummary['name'] ?? ''));
$ticketSubtitle = trim((string) ($ticketSummary['subtitle'] ?? ''));
$ticketMeta = trim((string) ($ticketSummary['meta'] ?? ''));
$ticketPreview = trim((string) ($ticketSummary['preview'] ?? ''));

ob_start();
?>
<div class="abm-icon <?= htmlspecialchars($confirmType, ENT_QUOTES, 'UTF-8') ?>">
    <?= $icon ?>
</div>
<h2 class="abm-title"><?= htmlspecialchars($confirmTitle ?? __('Confirmar ação', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></h2>
<p class="abm-message"><?= htmlspecialchars($confirmMessage ?? __('Revise os dados antes de continuar.', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></p>

<section class="abm-summary">
    <p class="abm-summary__eyebrow"><?= htmlspecialchars($ticketSubtitle !== '' ? $ticketSubtitle : sprintf(__('Chamado #%d', 'mailaprove'), $ticketId), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="abm-summary__title">
        <?= htmlspecialchars($ticketName !== '' ? $ticketName : __('Sem título informado', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if ($ticketMeta !== ''): ?>
        <p class="abm-summary__meta"><?= htmlspecialchars($ticketMeta, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($ticketPreview !== ''): ?>
        <p class="abm-summary__meta"><?= htmlspecialchars($ticketPreview, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</section>

<form method="post" action="<?= htmlspecialchars($actionUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" class="abm-form">
    <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="confirm" value="1">

    <div class="abm-confirm-box">
        <p>
            <?= htmlspecialchars($confirmNote ?? __('Esta ação será registrada no GLPI e o link não poderá ser usado novamente.', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>
        </p>
        <div class="abm-actions">
            <button type="submit" class="abm-btn <?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon ?> <?= htmlspecialchars($confirmButton ?? __('Confirmar', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
$pageTitle = $confirmTitle ?? __('Confirmar ação', 'mailaprove');
include(__DIR__ . '/layout.php');
