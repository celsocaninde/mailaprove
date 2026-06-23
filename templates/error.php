<?php
ob_start();
?>
<div class="abm-icon error" aria-hidden="true">
    &#10006;
</div>
<h2 class="abm-title"><?= htmlspecialchars($errorTitle ?? __('Erro', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></h2>
<p class="abm-message"><?= htmlspecialchars($errorMessage ?? __('Não foi possível concluir a ação.', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></p>
<p class="abm-message" style="font-size: 13px;">
    <?= __('Se o problema persistir, acesse o GLPI diretamente.', 'mailaprove') ?>
</p>
<?php
$pageContent = ob_get_clean();
$pageTitle = $errorTitle ?? __('Erro', 'mailaprove');
include(__DIR__ . '/layout.php');
