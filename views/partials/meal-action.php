<?php
/**
 * Botão de confirmar/cancelar presença para uma refeição.
 * Espera: $menu (array), $confirmed (bool), $compact (bool, opcional)
 */
$compact ??= false;
$closed = confirmation_closed($menu['menu_date']);
?>
<?php if ($closed): ?>
    <span class="badge <?= $confirmed ? 'badge--success' : 'badge--muted' ?>">
        <?= icon($confirmed ? 'check' : 'lock') ?> <?= $confirmed ? 'Confirmado' : 'Prazo encerrado' ?>
    </span>
<?php elseif ($confirmed): ?>
    <form method="post" action="<?= url('/refeicao/cancelar') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
        <?php if (!$compact): ?>
            <span class="badge badge--success"><?= icon('check') ?> Presença confirmada</span>
        <?php endif; ?>
        <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Cancelar sua presença em <?= e(format_date($menu['menu_date'], true)) ?>? Os pontos ganhos serão devolvidos.">
            <?= $compact ? icon('check') . ' Confirmado · cancelar' : 'Não vou mais' ?>
        </button>
    </form>
<?php else: ?>
    <form method="post" action="<?= url('/refeicao/confirmar') ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
        <button type="submit" class="btn btn--primary <?= $compact ? 'btn--sm' : 'btn--lg' ?>">
            <?= icon('check') ?> <?= $compact ? 'Confirmar' : 'Vou comer' ?>
        </button>
    </form>
<?php endif; ?>
