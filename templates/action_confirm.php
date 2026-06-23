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
<div class="abm-icon <?= htmlspecialchars($confirmType, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
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

        <?php if (!empty($confirmShowComment)): ?>
        <div class="abm-form-group" style="margin-top:16px;">
            <label for="confirm_comment" style="display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:#344054;">
                <?= htmlspecialchars($confirmCommentLabel ?? __('Comentário (opcional)', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>
            </label>
            <textarea
                id="confirm_comment"
                name="comment_validation"
                rows="4"
                placeholder="<?= htmlspecialchars($confirmCommentPlaceholder ?? __('Deixe em branco para usar o texto padrão de aprovação...', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>"
                style="width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid #d0d5dd;
                       border-radius:8px; font-size:14px; color:#1e293b; resize:vertical;
                       font-family:inherit; line-height:1.5;"
            ><?= htmlspecialchars($_POST['comment_validation'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <?php endif; ?>

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
