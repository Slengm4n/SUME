<header class="page-header">
    <div>
        <p class="page-header__kicker"><?= e(ucfirst(format_date(today(), true))) ?></p>
        <h1>Painel da cozinha</h1>
    </div>
    <a href="<?= url('/admin/cardapio') ?>" class="btn btn--primary"><?= icon('plus') ?> Cadastrar refeição</a>
</header>

<section class="stats stats--4" aria-label="Resumo">
    <a class="stat stat--link" href="<?= url('/admin/confirmacoes') ?>">
        <span class="stat__icon"><?= icon('clipboard-check') ?></span>
        <span class="stat__value"><?= (int) $confirmations ?></span>
        <span class="stat__label">confirmados hoje<?php if ($confirmations > 0): ?> · <?= (int) $pickedUp ?> retiraram<?php endif; ?></span>
    </a>
    <div class="stat">
        <span class="stat__icon"><?= icon('users') ?></span>
        <span class="stat__value"><?= $students ? round($confirmations / $students * 100) . '%' : '—' ?></span>
        <span class="stat__label">de adesão (<?= (int) $students ?> alunos)</span>
    </div>
    <a class="stat stat--link" href="<?= url('/admin/desperdicio') ?>">
        <span class="stat__icon"><?= icon('leaf') ?></span>
        <span class="stat__value"><?= percent($waste['waste_rate']) ?></span>
        <span class="stat__label">desperdício em 30 dias</span>
    </a>
    <a class="stat stat--link" href="<?= url('/admin/avaliacoes') ?>">
        <span class="stat__icon"><?= icon('star') ?></span>
        <span class="stat__value"><?= $feedback['average'] !== null ? e(number_format($feedback['average'], 1, ',', '')) : '—' ?></span>
        <span class="stat__label">nota média (<?= (int) $feedback['count'] ?>)</span>
    </a>
</section>

<div class="grid grid--2">
    <section class="card" aria-labelledby="hoje-titulo">
        <div class="card__head">
            <h2 id="hoje-titulo" class="card__title"><?= icon('utensils') ?> Hoje</h2>
            <a href="<?= url('/admin/confirmacoes') ?>" class="link">Registrar retiradas <?= icon('chevron-right') ?></a>
        </div>
        <?php if ($today): ?>
            <p class="today__dish"><?= e($today['dish']) ?></p>
            <?php if ($forecast && $forecast['portions'] > 0): ?>
                <div class="forecast">
                    <span class="forecast__icon"><?= icon('chef') ?></span>
                    <div>
                        <span class="forecast__value">Prepare cerca de <strong><?= (int) $forecast['portions'] ?> porções</strong></span>
                        <span class="forecast__basis"><?= (int) $confirmations ?> confirmados · <?= e($forecast['basis']) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="muted">Ainda não há confirmações para hoje.</p>
            <?php endif; ?>
            <?php if ($wastePending): ?>
                <p class="small muted pending-note"><?= icon('alert') ?> <?= (int) $wastePending ?> refeição(ões) anteriores sem sobras registradas. <a href="<?= url('/admin/desperdicio') ?>">Registrar</a></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty empty--inline">
                <?= icon('alert') ?>
                <p>Nenhuma refeição cadastrada para hoje. <a href="<?= url('/admin/cardapio') ?>">Cadastrar agora</a></p>
            </div>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="proximas-titulo">
        <div class="card__head">
            <h2 id="proximas-titulo" class="card__title"><?= icon('calendar') ?> Próximos dias</h2>
            <a href="<?= url('/admin/cardapio') ?>" class="link">Gerenciar <?= icon('chevron-right') ?></a>
        </div>
        <?php $next = array_values(array_filter($upcoming, fn ($m) => $m['menu_date'] !== today())); ?>
        <?php if ($next): ?>
            <ul class="list list--plain">
                <?php foreach ($next as $m): ?>
                    <li class="list__item">
                        <div class="list__main">
                            <span class="list__meta"><?= e(ucfirst(format_date($m['menu_date'], true))) ?></span>
                            <span class="list__title"><?= e($m['dish']) ?></span>
                        </div>
                        <span class="badge badge--muted" title="Confirmações"><?= icon('users') ?> <?= (int) $m['confirmations'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Nenhuma refeição futura cadastrada.</p>
        <?php endif; ?>
    </section>
</div>

<?php if ($history): ?>
    <section class="section-block" aria-labelledby="historico-titulo">
        <h2 id="historico-titulo" class="section-block__title"><?= icon('chart') ?> Últimas refeições</h2>
        <div class="table-wrap">
            <table class="table table--responsive">
                <thead>
                    <tr><th>Data</th><th>Refeição</th><th class="num">Confirmados</th><th class="num">Nota</th><th class="num">Desperdício</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $m): ?>
                        <tr>
                            <td class="nowrap" data-label="Data"><?= e(format_date($m['menu_date'], true)) ?></td>
                            <td data-label="Refeição"><?= e($m['dish']) ?></td>
                            <td class="num" data-label="Confirmados"><?= (int) $m['confirmations'] ?></td>
                            <td class="num" data-label="Nota"><?= $m['avg_rating'] !== null ? e(number_format((float) $m['avg_rating'], 1, ',', '')) . ' ★' : '—' ?></td>
                            <td class="num" data-label="Desperdício"><?= $m['prepared_portions'] !== null ? percent(\App\Services\WasteReport::rowRate($m)) : '<a href="' . e(url('/admin/desperdicio/registrar', ['id' => $m['id']])) . '">registrar</a>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
