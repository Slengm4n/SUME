<?php $max = max($summary['distribution']) ?: 1; ?>
<header class="page-header">
    <h1>Avaliações</h1>
</header>

<div class="grid grid--sidebar">
    <aside class="card">
        <p class="big-number">
            <?= $summary['average'] !== null ? e(number_format($summary['average'], 1, ',', '')) : '—' ?>
            <small>de 5 · <?= (int) $summary['count'] ?> avaliação(ões)</small>
        </p>
        <ul class="bars" aria-label="Distribuição das notas">
            <?php for ($i = 5; $i >= 1; $i--): $n = $summary['distribution'][$i]; ?>
                <li>
                    <a href="<?= url('/admin/avaliacoes', $filter === $i ? [] : ['nota' => $i]) ?>" class="bars__row<?= $filter === $i ? ' is-active' : '' ?>">
                        <span class="bars__label"><?= $i ?> ★</span>
                        <span class="bars__track"><span class="bars__fill" style="width: <?= round($n / $max * 100) ?>%"></span></span>
                        <span class="bars__value"><?= $n ?></span>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
        <?php if ($filter): ?>
            <a href="<?= url('/admin/avaliacoes') ?>" class="link"><?= icon('x') ?> Limpar filtro</a>
        <?php endif; ?>
    </aside>

    <div>
        <?php if ($feedbacks): ?>
            <ul class="reviews">
                <?php foreach ($feedbacks as $f): ?>
                    <li class="review">
                        <div class="review__head">
                            <span class="person"><?= avatar($f, 'sm') ?>
                                <span>
                                    <strong><?= e($f['name']) ?></strong>
                                    <span class="muted small">RM <?= e($f['RM'] ?? '—') ?> · <?= e(format_datetime($f['created_at'])) ?></span>
                                </span>
                            </span>
                            <span class="stars" aria-label="<?= (int) $f['rating'] ?> de 5 estrelas">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="stars__star<?= $i <= $f['rating'] ? ' is-on' : '' ?>"><?= icon('star') ?></span>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <p class="review__dish"><?= icon('utensils') ?> <?= e($f['dish']) ?> <span class="muted">· <?= e(format_date($f['menu_date'], true)) ?></span></p>
                        <?php if ($f['comment'] !== null && $f['comment'] !== ''): ?>
                            <p class="review__text"><?= nl2br(e($f['comment'])) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty empty--card">
                <?= icon('star') ?>
                <p><?= $filter ? 'Nenhuma avaliação com essa nota.' : 'Ainda não há avaliações.' ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
