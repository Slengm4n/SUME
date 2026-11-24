<?php
$done = $menu['prepared_portions'] !== null;
$preparedValue = old('prepared_portions', $done ? (string) $menu['prepared_portions'] : '');
?>
<div class="narrow">
    <a href="<?= url('/admin/desperdicio') ?>" class="back-link"><?= icon('arrow-left') ?> Voltar ao relatório</a>

    <form method="post" action="<?= url('/admin/desperdicio/registrar', ['id' => $menu['id']]) ?>" class="card form" novalidate data-waste-form>
        <?= csrf_field() ?>
        <input type="hidden" name="voltar" value="<?= e(input('voltar')) ?>">

        <div>
            <p class="page-header__kicker"><?= e(ucfirst(format_date($menu['menu_date'], true))) ?></p>
            <h1 class="card__title card__title--lg"><?= $done ? 'Editar sobras' : 'Registrar sobras' ?></h1>
            <p class="today__dish today__dish--sm"><?= e($menu['dish']) ?></p>
        </div>

        <dl class="facts">
            <div><dt>Confirmados</dt><dd><?= (int) $confirmations ?></dd></div>
            <div>
                <dt>Sugestão do sistema</dt>
                <dd><?= $forecast['portions'] ? (int) $forecast['portions'] . ' porções' : '—' ?></dd>
            </div>
        </dl>

        <div class="form__row">
            <div class="field">
                <label for="prepared_portions">Porções preparadas</label>
                <input type="number" id="prepared_portions" name="prepared_portions" min="1" max="100000" inputmode="numeric"
                       value="<?= e($preparedValue) ?>" required<?= field_invalid('prepared_portions') ?>>
                <?= field_error('prepared_portions') ?: '<p class="field__hint">O número real, não a sugestão.</p>' ?>
            </div>
            <div class="field">
                <label for="leftover_portions">Porções que sobraram</label>
                <input type="number" id="leftover_portions" name="leftover_portions" min="0" max="100000" inputmode="numeric"
                       value="<?= e(old('leftover_portions', $done ? (string) $menu['leftover_portions'] : '')) ?>" required<?= field_invalid('leftover_portions') ?>>
                <?= field_error('leftover_portions') ?>
            </div>
        </div>

        <div class="field">
            <label for="waste_kg">Peso descartado em kg <span class="optional">(opcional)</span></label>
            <input type="text" id="waste_kg" name="waste_kg" inputmode="decimal" placeholder="Ex.: 3,5"
                   value="<?= e(old('waste_kg', $menu['waste_kg'] !== null ? number_br((float) $menu['waste_kg'], 2) : '')) ?>"<?= field_invalid('waste_kg') ?>>
            <?= field_error('waste_kg') ?: '<p class="field__hint">Se a cozinha pesar o que foi para o lixo, anote aqui.</p>' ?>
        </div>

        <p class="waste-preview" data-waste-preview aria-live="polite"></p>

        <div class="form__actions">
            <a href="<?= url('/admin/desperdicio') ?>" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn btn--primary">Salvar registro</button>
        </div>
    </form>
</div>
