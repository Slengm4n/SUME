<?php $isToday = $date === today(); $isPast = $date < today(); ?>
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

<div data-live-confirmations="<?= $isToday ? e(url('/admin/confirmacoes/dados', ['data' => $date])) : '' ?>">
    <section class="card card--today">
        <div class="card__head">
            <h2 class="card__title"><?= icon('utensils') ?> Refeição</h2>
            <?php if ($isToday): ?><span class="live-dot no-print" title="Ao vivo">Ao vivo</span><?php endif; ?>
        </div>
        <p class="today__dish" data-dish><?= $menu ? e($menu['dish']) : 'Nenhuma refeição cadastrada para esta data.' ?></p>

        <div class="confirm-summary">
            <p class="big-number"><span data-total><?= count($people) ?></span> <small>aluno(s) confirmado(s)</small></p>
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
    </section>

    <section class="section-block">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Aluno</th><th>RM</th><th class="num">Confirmou às</th><th class="print-only">Retirou</th></tr>
                </thead>
                <tbody data-rows>
                    <?php foreach ($people as $p): ?>
                        <tr>
                            <td><span class="person"><?= avatar($p, 'sm') ?> <?= e($p['name']) ?></span></td>
                            <td><?= e($p['RM'] ?? '—') ?></td>
                            <td class="num"><?= e(date('H:i', strtotime($p['confirmed_at']))) ?></td>
                            <td class="print-only"><span class="checkbox-print"></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$people): ?>
                        <tr><td colspan="3" class="table__empty">Nenhuma confirmação ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
