<?php
/**
 * Satisfaction survey form template.
 *
 * Variables:
 * - $rawToken (string)
 * - $ticketId (int)
 * - $formError (string)
 */

$ticketSummary = $ticketSummary ?? [
    'id'       => (int) ($ticketId ?? 0),
    'name'     => '',
    'subtitle' => sprintf(__('Chamado #%d', 'mailaprove'), (int) ($ticketId ?? 0)),
];
$ticketName = trim((string) ($ticketSummary['name'] ?? ''));
$ticketSubtitle = trim((string) ($ticketSummary['subtitle'] ?? ''));
$ticketMeta = trim((string) ($ticketSummary['meta'] ?? ''));
$ticketPreview = trim((string) ($ticketSummary['preview'] ?? ''));

ob_start();
?>
<div class="abm-icon info">
    &#9733;
</div>
<h2 class="abm-title"><?= __('Pesquisa de satisfação', 'mailaprove') ?></h2>
<p class="abm-message">
    <?= __('Como você avalia a resolução desta solicitação?', 'mailaprove') ?>
</p>

<section class="abm-summary">
    <p class="abm-summary__eyebrow"><?= htmlspecialchars($ticketSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
    <p class="abm-summary__title">
        <?= htmlspecialchars($ticketName !== '' ? $ticketName : __('Sem título informado', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if ($ticketMeta !== ''): ?>
        <p class="abm-summary__meta"><?= htmlspecialchars($ticketMeta, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($ticketPreview !== ''): ?>
        <p class="abm-summary__meta"><?= htmlspecialchars($ticketPreview, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p class="abm-summary__meta"><?= __('Sua resposta ajuda a melhorar a qualidade do atendimento.', 'mailaprove') ?></p>
</section>

<?php if (!empty($formError)): ?>
<div class="abm-form-error">
    <?= htmlspecialchars($formError) ?>
</div>
<?php endif; ?>

<form method="post" action="satisfaction.php" class="abm-form">
    <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">

    <div class="abm-form-group">
        <label style="text-align: center;">
            <?= __('Sua nota', 'mailaprove') ?> <span style="color: #b91c1c;">*</span>
        </label>
        <div class="abm-stars">
            <input type="radio" id="star5" name="satisfaction" value="5">
            <label for="star5" title="5 - <?= __('Excelente', 'mailaprove') ?>">&#9733;</label>

            <input type="radio" id="star4" name="satisfaction" value="4">
            <label for="star4" title="4 - <?= __('Muito bom', 'mailaprove') ?>">&#9733;</label>

            <input type="radio" id="star3" name="satisfaction" value="3">
            <label for="star3" title="3 - <?= __('Bom', 'mailaprove') ?>">&#9733;</label>

            <input type="radio" id="star2" name="satisfaction" value="2">
            <label for="star2" title="2 - <?= __('Regular', 'mailaprove') ?>">&#9733;</label>

            <input type="radio" id="star1" name="satisfaction" value="1">
            <label for="star1" title="1 - <?= __('Ruim', 'mailaprove') ?>">&#9733;</label>
        </div>
    </div>

    <div class="abm-form-group">
        <label for="satisfaction_comment">
            <?= __('Comentário adicional (opcional)', 'mailaprove') ?>
        </label>
        <textarea
            id="satisfaction_comment"
            name="comment"
            rows="4"
            placeholder="<?= __('Compartilhe algum detalhe sobre o atendimento...', 'mailaprove') ?>"
        ><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
    </div>

    <div class="abm-actions">
        <button type="submit" class="abm-btn abm-btn-primary">
            &#10004; <?= __('Enviar pesquisa', 'mailaprove') ?>
        </button>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
$pageTitle = __('Pesquisa de satisfação', 'mailaprove');
include(__DIR__ . '/layout.php');
