<?php $deadline = (string) config('confirmation_deadline'); ?>
<header class="page-header">
    <div>
        <p class="page-header__kicker"><?= e(ucfirst(format_date(today(), true))) ?></p>
        <h1>Olá, <?= e(explode(' ', $user['name'])[0]) ?>!</h1>
    </div>
</header>

<div class="grid grid--dashboard">
    <section class="card card--today" aria-labelledby="hoje-titulo">
        <div class="card__head">
            <h2 id="hoje-titulo" class="card__title"><?= icon('utensils') ?> Refeição de hoje</h2>
            <?php if ($today && $deadline && !confirmation_closed($today['menu_date'])): ?>
                <span class="badge badge--muted"><?= icon('clock') ?> Confirme até <?= e($deadline) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($today): ?>
            <p class="today__dish"><?= e($today['dish']) ?></p>
            <div class="today__actions">
                <?php
                $menu = $today;
                $confirmed = $todayConfirmed;
                $compact = false;
                require BASE_PATH . '/views/partials/meal-action.php';
                ?>
            </div>
        <?php else: ?>
            <div class="empty empty--inline">
                <?= icon('calendar') ?>
                <p>Nenhuma refeição cadastrada para hoje.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="stats" aria-label="Seus números">
        <div class="stat">
            <span class="stat__icon"><?= icon('award') ?></span>
            <span class="stat__value"><?= (int) $user['points'] ?></span>
            <span class="stat__label">pontos</span>
        </div>
        <div class="stat">
            <span class="stat__icon"><?= icon('trophy') ?></span>
            <span class="stat__value"><?= $rank ? e($rank) . 'º' : '—' ?></span>
            <span class="stat__label">no ranking</span>
        </div>
        <div class="stat">
            <span class="stat__icon"><?= icon('utensils') ?></span>
            <span class="stat__value"><?= (int) $totalMeals ?></span>
            <span class="stat__label">refeições</span>
        </div>
    </section>
</div>

<?php if ($pendingFeedback): ?>
    <section class="section-block" aria-labelledby="avaliar-titulo">
        <h2 id="avaliar-titulo" class="section-block__title"><?= icon('star') ?> Avalie e ganhe +<?= (int) config('points.feedback') ?> pontos</h2>
        <ul class="list">
            <?php foreach ($pendingFeedback as $m): ?>
                <li class="list__item">
                    <div class="list__main">
                        <span class="list__meta"><?= e(ucfirst(format_date($m['menu_date'], true))) ?></span>
                        <span class="list__title"><?= e($m['dish']) ?></span>
                    </div>
                    <a href="<?= url('/avaliar', ['refeicao' => $m['id']]) ?>" class="btn btn--secondary btn--sm">Avaliar <?= icon('chevron-right') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="section-block" aria-labelledby="proximas-titulo">
    <h2 id="proximas-titulo" class="section-block__title"><?= icon('calendar') ?> Próximas refeições</h2>
    <?php if ($upcoming): ?>
        <ul class="list">
            <?php foreach ($upcoming as $m): ?>
                <li class="list__item">
                    <div class="list__date" aria-hidden="true">
                        <span><?= e(date('d', strtotime($m['menu_date']))) ?></span>
                        <small><?= e(mb_substr(format_date($m['menu_date'], true), 0, 3)) ?></small>
                    </div>
                    <div class="list__main">
                        <span class="list__meta"><?= e(ucfirst(format_date($m['menu_date'], true))) ?></span>
                        <span class="list__title"><?= e($m['dish']) ?></span>
                    </div>
                    <?php
                    $menu = $m;
                    $confirmed = in_array((int) $m['id'], $confirmedIds, true);
                    $compact = true;
                    require BASE_PATH . '/views/partials/meal-action.php';
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty empty--card">
            <?= icon('calendar') ?>
            <p>O cardápio dos próximos dias ainda não foi publicado.</p>
        </div>
    <?php endif; ?>
</section>
