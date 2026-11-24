<?php
$podium = array_slice($ranking, 0, 3);
$rest = array_slice($ranking, 3);
$medals = ['gold', 'silver', 'bronze'];
?>
<header class="page-header">
    <div>
        <h1>Ranking</h1>
        <p class="muted">Confirme presença (+<?= (int) config('points.confirmation') ?>) e avalie refeições (+<?= (int) config('points.feedback') ?>) para subir.</p>
    </div>
</header>

<?php if (!$ranking): ?>
    <div class="empty empty--card">
        <?= icon('trophy') ?>
        <p>Ainda não há alunos no ranking.</p>
    </div>
<?php else: ?>
    <ol class="podium" aria-label="Três primeiros colocados">
        <?php foreach ($podium as $i => $r): ?>
            <li class="podium__item podium__item--<?= $medals[$i] ?><?= (int) $r['id'] === $me ? ' is-me' : '' ?>">
                <span class="podium__pos"><?= $i + 1 ?>º</span>
                <?= avatar($r, 'lg') ?>
                <span class="podium__name"><?= e($r['name']) ?></span>
                <span class="podium__points"><?= (int) $r['points'] ?> pts</span>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if ($rest): ?>
        <ol class="leaderboard" start="4">
            <?php foreach ($rest as $i => $r): ?>
                <li class="leaderboard__row<?= (int) $r['id'] === $me ? ' is-me' : '' ?>">
                    <span class="leaderboard__pos"><?= $i + 4 ?>º</span>
                    <?= avatar($r, 'sm') ?>
                    <span class="leaderboard__name"><?= e($r['name']) ?><?= (int) $r['id'] === $me ? ' <span class="badge badge--brand">você</span>' : '' ?></span>
                    <span class="leaderboard__points"><?= (int) $r['points'] ?> pts</span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
<?php endif; ?>

<?php if (is_admin()): ?>
    <section class="section-block" aria-labelledby="gerenciar-titulo">
        <h2 id="gerenciar-titulo" class="section-block__title"><?= icon('shield') ?> Gerenciar pontos</h2>
        <div class="grid grid--2">
            <form method="post" action="<?= url('/admin/pontos/deduzir') ?>" class="card form">
                <?= csrf_field() ?>
                <h3 class="card__title">Deduzir de todos</h3>
                <p class="muted">Remove pontos de todos os alunos (nunca abaixo de zero).</p>
                <div class="field">
                    <label for="points">Pontos</label>
                    <input type="number" id="points" name="points" min="1" max="100000" required>
                </div>
                <button type="submit" class="btn btn--secondary" data-confirm="Deduzir esses pontos de TODOS os alunos?">Deduzir pontos</button>
            </form>

            <form method="post" action="<?= url('/admin/pontos/zerar') ?>" class="card card--danger form">
                <?= csrf_field() ?>
                <h3 class="card__title">Zerar ranking</h3>
                <p class="muted">Zera os pontos de todos. Use no início de um novo período. <strong>Não pode ser desfeito.</strong></p>
                <div class="field">
                    <label for="confirmation">Digite <strong>ZERAR</strong> para confirmar</label>
                    <input type="text" id="confirmation" name="confirmation" autocomplete="off" required pattern="[Zz][Ee][Rr][Aa][Rr]">
                </div>
                <button type="submit" class="btn btn--danger"><?= icon('trash') ?> Zerar todos os pontos</button>
            </form>
        </div>
    </section>
<?php endif; ?>
