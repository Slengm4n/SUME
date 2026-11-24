<div class="narrow">
    <a href="<?= url('/admin/usuarios') ?>" class="back-link"><?= icon('arrow-left') ?> Voltar para usuários</a>

    <form method="post" action="<?= url('/admin/usuarios/editar', ['id' => $edit['id']]) ?>" class="card form" novalidate>
        <?= csrf_field() ?>
        <div class="card__head">
            <span class="person person--lg"><?= avatar($edit, 'md') ?>
                <span>
                    <strong><?= e($edit['name']) ?></strong>
                    <span class="muted small">Cadastrado em <?= e(format_date($edit['created_at'])) ?> · <?= (int) $edit['points'] ?> pontos</span>
                </span>
            </span>
        </div>

        <div class="field">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?= e(old('name', $edit['name'])) ?>" maxlength="100" required<?= field_invalid('name') ?>>
            <?= field_error('name') ?>
        </div>
        <div class="field">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e(old('email', $edit['email'])) ?>" maxlength="150" required<?= field_invalid('email') ?>>
            <?= field_error('email') ?>
        </div>
        <div class="form__row">
            <div class="field">
                <label for="rm">RM</label>
                <input type="text" id="rm" name="rm" value="<?= e(old('rm', $edit['RM'] ?? '')) ?>" inputmode="numeric" maxlength="20"<?= field_invalid('rm') ?>>
                <?= field_error('rm') ?>
            </div>
            <div class="field">
                <label for="codigo">Código da ETEC</label>
                <input type="text" id="codigo" name="codigo" value="<?= e(old('codigo', $edit['codigo'] ?? '')) ?>" maxlength="20"<?= field_invalid('codigo') ?>>
                <?= field_error('codigo') ?>
            </div>
        </div>

        <label class="check">
            <input type="checkbox" name="is_master" value="1" <?= $edit['is_master'] ? 'checked' : '' ?>>
            <span><strong>Administrador</strong> — pode gerenciar cardápio, usuários e pontos.</span>
        </label>
        <?= field_error('is_master') ?>

        <div class="form__actions">
            <a href="<?= url('/admin/usuarios') ?>" class="btn btn--ghost">Cancelar</a>
            <button type="submit" class="btn btn--primary">Salvar</button>
        </div>
    </form>
</div>
