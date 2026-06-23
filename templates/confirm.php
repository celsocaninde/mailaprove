<?php
/**
 * Confirmation page template.
 *
 * Variables:
 * - $confirmTitle (string)
 * - $confirmMessage (string)
 * - $confirmType (string): 'success' or 'warning'
 */

$iconType = $confirmType ?? 'success';
$icon = $iconType === 'success' ? '&#10004;' : '&#9888;';

ob_start();
?>
<div class="abm-icon <?= htmlspecialchars($iconType) ?>" aria-hidden="true">
    <?= $icon ?>
</div>
<h2 class="abm-title"><?= htmlspecialchars($confirmTitle ?? __('Ação concluída', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></h2>
<p class="abm-message"><?= htmlspecialchars($confirmMessage ?? __('Sua ação foi processada com sucesso.', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></p>
<p class="abm-message abm-subtle">
    <?= __('Você já pode fechar esta janela.', 'mailaprove') ?>
</p>
<?php
$pageContent = ob_get_clean();
$pageTitle = $confirmTitle ?? __('Confirmação', 'mailaprove');
include(__DIR__ . '/layout.php');
