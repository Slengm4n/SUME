<?php
$types = [
    'success' => 'check-circle',
    'error'   => 'alert',
    'info'    => 'info',
];
$messages = array_filter(array_map(fn ($t) => flashed($t), array_combine(array_keys($types), array_keys($types))));
if (!$messages) {
    return;
}
?>
<div class="toasts" aria-live="polite">
    <?php foreach ($messages as $type => $message): ?>
        <div class="toast toast--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>" data-toast>
            <?= icon($types[$type]) ?>
            <p><?= e($message) ?></p>
            <button type="button" class="toast__close" aria-label="Fechar aviso" data-toast-close><?= icon('x') ?></button>
        </div>
    <?php endforeach; ?>
</div>
