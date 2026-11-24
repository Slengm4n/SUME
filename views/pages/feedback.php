<?php
$labels = [1 => 'Muito ruim', 2 => 'Ruim', 3 => 'Ok', 4 => 'Boa', 5 => 'Excelente'];
?>
<div class="narrow">
    <a href="<?= url('/painel') ?>" class="back-link"><?= icon('arrow-left') ?> Voltar ao painel</a>

    <section class="card">
        <p class="page-header__kicker"><?= e(ucfirst(format_date($menu['menu_date'], true))) ?></p>
        <h1 class="card__title card__title--lg">Como estava a refeição?</h1>
        <p class="today__dish today__dish--sm"><?= e($menu['dish']) ?></p>

        <?php if ($existing): ?>
            <div class="empty empty--inline">
                <?= icon('check-circle') ?>
                <p>Você já avaliou esta refeição com <strong><?= (int) $existing['rating'] ?> estrela(s)</strong>. Obrigado!</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= url('/avaliar', ['refeicao' => $menu['id']]) ?>" class="form">
                <?= csrf_field() ?>

                <fieldset class="rating"<?= field_invalid('rating') ?>>
                    <legend>Sua nota</legend>
                    <div class="rating__stars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="nota-<?= $i ?>" name="rating" value="<?= $i ?>" <?= old('rating') === (string) $i ? 'checked' : '' ?> required>
                            <label for="nota-<?= $i ?>" title="<?= e($labels[$i]) ?>">
                                <?= icon('star') ?><span class="sr-only"><?= $i ?> estrela(s) — <?= e($labels[$i]) ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                    <p class="rating__caption" data-rating-caption aria-live="polite">Toque nas estrelas</p>
                    <?= field_error('rating') ?>
                </fieldset>

                <div class="field">
                    <label for="comment">Comentário <span class="optional">(opcional)</span></label>
                    <textarea id="comment" name="comment" rows="4" maxlength="500" placeholder="O que você achou? O que poderia melhorar?"><?= e(old('comment')) ?></textarea>
                </div>

                <div class="form__actions">
                    <a href="<?= url('/painel') ?>" class="btn btn--ghost">Cancelar</a>
                    <button type="submit" class="btn btn--primary"><?= icon('send') ?> Enviar avaliação</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
