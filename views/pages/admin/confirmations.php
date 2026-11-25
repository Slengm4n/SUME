<?php
$isToday = $date === today();
$isPast = $date < today();
$canPickup = $menu && !($date > today());
$closed = $menu && $menu['pickup_closed_at'] !== null;
$total = count($people);
$noShow = (int) config('points.no_show');
?>
<header class="page-header">
    <div>
        <h1>Confirmações</h1>
        <p class="muted"><?= e(ucfirst(format_date($date, true))) ?><?= $isToday ? '<span class="no-print"> · atualiza automaticamente</span>' : '' ?></p>
    </div>
    <div class="toolbar no-print">
        <form method="get" action="<?= url('/admin/confirmacoes') ?>" class="inline-form">
            <label for="data" class="sr-only">Data</label>
            <input type="date" id="data" name="data" value="<?= e($date) ?>" onchange="this.form.submit()">
            <noscript><button type="submit" class="btn btn--secondary btn--sm">Ver</button></noscript>
        </form>
        <?php if ($people): ?>
            <a href="<?= url('/admin/confirmacoes/exportar', ['data' => $date]) ?>" class="btn btn--secondary btn--sm"><?= icon('download') ?> Excel</a>
            <button type="button" class="btn btn--secondary btn--sm" data-print><?= icon('printer') ?> Imprimir / PDF</button>
        <?php endif; ?>
    </div>
</header>

<div data-confirmations data-live="<?= $isToday ? e(url('/admin/confirmacoes/dados', ['data' => $date])) : '' ?>"
     data-pickup-url="<?= e(url('/admin/confirmacoes/retirada')) ?>" data-can-pickup="<?= $canPickup ? '1' : '0' ?>">
    <section class="card card--today">
        <div class="card__head">
            <h2 class="card__title"><?= icon('utensils') ?> Refeição</h2>
            <?php if ($isToday): ?><span class="live-dot no-print" title="Ao vivo">Ao vivo</span><?php endif; ?>
        </div>
        <p class="today__dish" data-dish><?= $menu ? e($menu['dish']) : 'Nenhuma refeição cadastrada para esta data.' ?></p>

        <div class="confirm-summary">
            <?php if ($canPickup): ?>
                <div class="pickup-count">
                    <p class="big-number"><span data-picked><?= (int) $pickedUp ?></span><span class="pickup-count__of">/<span data-total><?= $total ?></span></span>
                        <small>confirmados já retiraram</small></p>
                    <div class="progress" aria-hidden="true"><span class="progress__bar" data-progress style="width: <?= $total ? round($pickedUp / $total * 100) : 0 ?>%"></span></div>
                </div>
            <?php else: ?>
                <p class="big-number"><span data-total><?= $total ?></span> <small>aluno(s) confirmado(s)</small></p>
            <?php endif; ?>

            <?php if ($menu && !$isPast): ?>
                <div class="forecast">
                    <span class="forecast__icon"><?= icon('chef') ?></span>
                    <div>
                        <span class="forecast__value">Prepare cerca de <strong><span data-forecast><?= (int) $forecast['portions'] ?></span> porções</strong></span>
                        <span class="forecast__basis">Baseado no <?= e($forecast['basis']) ?></span>
                    </div>
                </div>
            <?php elseif ($menu && $menu['prepared_portions'] === null): ?>
                <a href="<?= url('/admin/desperdicio/registrar', ['id' => $menu['id']]) ?>" class="btn btn--secondary no-print"><?= icon('plus') ?> Registrar sobras desta refeição</a>
            <?php endif; ?>
        </div>

        <?php if ($canPickup && $people): ?>
            <div class="pickup-close no-print">
                <?php if ($closed): ?>
                    <span class="badge badge--muted"><?= icon('lock') ?> Retirada encerrada às <?= e(date('H:i', strtotime($menu['pickup_closed_at']))) ?></span>
                    <form method="post" action="<?= url('/admin/confirmacoes/encerrar') ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
                        <input type="hidden" name="closed" value="0">
                        <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Reabrir a retirada? Quem não retirou recupera os <?= $noShow ?> pontos perdidos.">Reabrir</button>
                    </form>
                <?php else: ?>
                    <p class="muted small">Ao terminar de servir, encerre a retirada: quem confirmou e não retirou perde <?= $noShow ?> pontos.</p>
                    <form method="post" action="<?= url('/admin/confirmacoes/encerrar') ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
                        <input type="hidden" name="closed" value="1">
                        <button type="submit" class="btn btn--secondary btn--sm" data-confirm="Encerrar a retirada? Quem confirmou e não retirou perde <?= $noShow ?> pontos (dá para reabrir depois)."><?= icon('lock') ?> Encerrar retirada</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <?php if ($canPickup && $people): ?>
            <div class="pickup-search no-print">
                <div class="search" role="search">
                    <?= icon('search') ?>
                    <label for="busca-retirada" class="sr-only">Buscar aluno por nome ou RM</label>
                    <input type="search" id="busca-retirada" placeholder="Nome ou RM" autocomplete="off" data-pickup-search>
                </div>
                <p class="muted small" data-search-hint>Digite o RM e aperte Enter para marcar a retirada.</p>
            </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="table table--responsive">
                <thead>
                    <tr><th>Aluno</th><th>RM</th><th class="num">Confirmou às</th><th><?= $canPickup ? 'Retirada' : '<span class="print-only">Retirou</span>' ?></th></tr>
                </thead>
                <tbody data-rows>
                    <?php foreach ($people as $p): ?>
                        <tr data-search="<?= e(mb_strtolower($p['name'] . ' ' . ($p['RM'] ?? ''))) ?>" data-user="<?= (int) $p['id'] ?>" <?= $p['picked_up_at'] ? 'data-picked' : '' ?>>
                            <td data-label="Aluno"><span class="person"><?= avatar($p, 'sm') ?> <?= e($p['name']) ?></span></td>
                            <td data-label="RM"><?= e($p['RM'] ?? '—') ?></td>
                            <td data-label="Confirmou às" class="num"><?= e(date('H:i', strtotime($p['confirmed_at']))) ?></td>
                            <td class="table__actions">
                                <?php if ($canPickup): ?>
                                    <form method="post" action="<?= url('/admin/confirmacoes/retirada') ?>" class="inline-form" data-pickup-form>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
                                        <?php if ($p['picked_up_at']): ?>
                                            <input type="hidden" name="picked_up" value="0">
                                            <button type="submit" class="btn btn--done btn--sm" title="Desmarcar retirada" aria-label="<?= e($p['name']) ?> retirou às <?= e(date('H:i', strtotime($p['picked_up_at']))) ?>. Desmarcar">
                                                <?= icon('check') ?> Retirou <?= e(date('H:i', strtotime($p['picked_up_at']))) ?>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="picked_up" value="1">
                                            <button type="submit" class="btn btn--secondary btn--sm" aria-label="Marcar retirada de <?= e($p['name']) ?>">Marcar retirada</button>
                                            <span class="checkbox-print"></span>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="print-only"><span class="checkbox-print"></span></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$people): ?>
                        <tr><td colspan="4" class="table__empty">Nenhuma confirmação ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
