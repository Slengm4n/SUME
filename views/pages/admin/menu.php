<?php
$formDate = old('menu_date', $editing['menu_date'] ?? today());
$formDish = old('dish', $editing['dish'] ?? '');
?>
<header class="page-header">
    <div>
        <h1>Cardápio</h1>
        <p class="muted">Uma refeição por dia. Cadastrar numa data que já tem refeição substitui a anterior.</p>
    </div>
</header>

<div class="grid grid--sidebar">
    <form method="post" action="<?= url('/admin/cardapio') ?>" class="card form form--sticky" novalidate>
        <?= csrf_field() ?>
        <h2 class="card__title"><?= icon($editing ? 'pencil' : 'plus') ?> <?= $editing ? 'Editar refeição' : 'Nova refeição' ?></h2>

        <div class="field">
            <label for="menu_date">Data</label>
            <input type="date" id="menu_date" name="menu_date" value="<?= e($formDate) ?>" min="<?= e(today()) ?>" required<?= field_invalid('menu_date') ?>>
            <?= field_error('menu_date') ?>
        </div>
        <div class="field">
            <label for="dish">Refeição</label>
            <textarea id="dish" name="dish" rows="3" maxlength="255" placeholder="Ex.: Arroz, feijão, frango grelhado e salada" required<?= field_invalid('dish') ?>><?= e($formDish) ?></textarea>
            <?= field_error('dish') ?>
        </div>

        <div class="form__actions">
            <?php if ($editing): ?>
                <a href="<?= url('/admin/cardapio') ?>" class="btn btn--ghost">Cancelar</a>
            <?php endif; ?>
            <button type="submit" class="btn btn--primary"><?= $editing ? 'Salvar alterações' : 'Cadastrar' ?></button>
        </div>
    </form>

    <div>
        <section class="section-block section-block--first" aria-labelledby="futuras">
            <h2 id="futuras" class="section-block__title"><?= icon('calendar') ?> Programadas</h2>
            <?php if ($upcoming): ?>
                <ul class="list">
                    <?php foreach ($upcoming as $m): ?>
                        <li class="list__item<?= $m['menu_date'] === today() ? ' list__item--highlight' : '' ?>">
                            <div class="list__date" aria-hidden="true">
                                <span><?= e(date('d', strtotime($m['menu_date']))) ?></span>
                                <small><?= e(mb_substr(format_date($m['menu_date'], true), 0, 3)) ?></small>
                            </div>
                            <div class="list__main">
                                <span class="list__meta">
                                    <?= e(ucfirst(format_date($m['menu_date'], true))) ?>
                                    <?= $m['menu_date'] === today() ? ' · <strong>hoje</strong>' : '' ?>
                                </span>
                                <span class="list__title"><?= e($m['dish']) ?></span>
                            </div>
                            <span class="badge badge--muted" title="Confirmações"><?= icon('users') ?> <?= (int) $m['confirmations'] ?></span>
                            <div class="list__actions">
                                <a href="<?= url('/admin/cardapio', ['editar' => $m['id']]) ?>" class="icon-btn" aria-label="Editar refeição de <?= e(format_date($m['menu_date'])) ?>"><?= icon('pencil') ?></a>
                                <form method="post" action="<?= url('/admin/cardapio/excluir') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <button type="submit" class="icon-btn icon-btn--danger" aria-label="Excluir refeição de <?= e(format_date($m['menu_date'])) ?>"
                                            data-confirm="Excluir a refeição de <?= e(format_date($m['menu_date'], true)) ?>? As <?= (int) $m['confirmations'] ?> confirmação(ões) também serão apagadas.">
                                        <?= icon('trash') ?>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty empty--card">
                    <?= icon('calendar') ?>
                    <p>Nenhuma refeição programada. Use o formulário para cadastrar.</p>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($history): ?>
            <section class="section-block" aria-labelledby="anteriores">
                <h2 id="anteriores" class="section-block__title"><?= icon('clock') ?> Anteriores</h2>
                <div class="table-wrap">
                    <table class="table table--responsive">
                        <thead><tr><th>Data</th><th>Refeição</th><th class="num">Confirmados</th><th class="num">Nota</th><th class="num">Desperdício</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $m): ?>
                                <tr>
                                    <td class="nowrap" data-label="Data"><?= e(format_date($m['menu_date'], true)) ?></td>
                                    <td data-label="Refeição"><?= e($m['dish']) ?></td>
                                    <td class="num" data-label="Confirmados"><a href="<?= url('/admin/confirmacoes', ['data' => $m['menu_date']]) ?>"><?= (int) $m['confirmations'] ?></a></td>
                                    <td class="num" data-label="Nota"><?= $m['avg_rating'] !== null ? e(number_format((float) $m['avg_rating'], 1, ',', '')) . ' ★' : '—' ?></td>
                                    <td class="num" data-label="Desperdício">
                                        <?php if ($m['prepared_portions'] !== null): ?>
                                            <a href="<?= url('/admin/desperdicio/registrar', ['id' => $m['id'], 'voltar' => 'cardapio']) ?>"><?= percent(\App\Services\WasteReport::rowRate($m)) ?></a>
                                        <?php else: ?>
                                            <a href="<?= url('/admin/desperdicio/registrar', ['id' => $m['id'], 'voltar' => 'cardapio']) ?>" class="btn btn--secondary btn--sm"><?= icon('plus') ?> Registrar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
