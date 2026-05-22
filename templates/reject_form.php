<?php
/**
 * Rejection form template.
 *
 * Variables:
 * - $rawToken (string): The token to submit with the form
 * - $ticketId (int): Ticket ID for display
 * - $formError (string): Error message to display (if any)
 * - $formTitle (string, optional): Form title (defaults to 'Reject Validation')
 * - $formAction (string, optional): 'reject' or 'solution_reject'
 */

$formTitle = $formTitle ?? __('Recusar validação', 'mailaprove');
$formAction = $formAction ?? 'reject';
$actionUrl = ($formAction === 'solution_reject')
    ? 'solution_reject.php'
    : 'reject.php';
$commentField = ($formAction === 'solution_reject') ? 'comment' : 'comment_validation';
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
<div class="abm-icon warning">
    &#9888;
</div>
<h2 class="abm-title"><?= htmlspecialchars($formTitle) ?></h2>

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
    <p class="abm-summary__meta"><?= __('Informe um motivo claro. Ele será registrado no histórico do chamado.', 'mailaprove') ?></p>
</section>

<?php if (!empty($formError)): ?>
<div class="abm-form-error">
    <?= htmlspecialchars($formError) ?>
</div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($actionUrl) ?>" class="abm-form">
    <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">

    <div class="abm-form-group">
        <label for="comment_field">
            <?= __('Motivo da recusa', 'mailaprove') ?> <span style="color: #b91c1c;">*</span>
        </label>
        <textarea
            id="comment_field"
            name="<?= htmlspecialchars($commentField) ?>"
            rows="5"
            placeholder="<?= __('Descreva de forma objetiva o motivo da recusa...', 'mailaprove') ?>"
            required
        ><?= htmlspecialchars($_POST[$commentField] ?? '') ?></textarea>
    </div>

    <div class="abm-actions">
        <button type="submit" class="abm-btn abm-btn-danger">
            &#10006; <?= __('Confirmar recusa', 'mailaprove') ?>
        </button>
    </div>
</form>
<?php
$pageContent = ob_get_clean();
$pageTitle = $formTitle;
include(__DIR__ . '/layout.php');
