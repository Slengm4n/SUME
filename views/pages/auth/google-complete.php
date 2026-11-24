<h1 class="auth__title">Quase lá!</h1>
<p class="auth__subtitle">Sua conta Google foi verificada. Só faltam os dados da escola.</p>

<div class="google-account">
    <span class="avatar avatar--md" aria-hidden="true"><?= e(initials($pending['name'])) ?></span>
    <div>
        <strong><?= e($pending['email']) ?></strong>
        <span class="muted small">Conta Google</span>
    </div>
</div>

<form method="post" action="<?= url('/cadastro/google') ?>" class="form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Nome completo</label>
        <input type="text" id="name" name="name" value="<?= e(old('name', $pending['name'])) ?>" autocomplete="name" maxlength="100" required<?= field_invalid('name') ?>>
        <?= field_error('name') ?>
    </div>

    <div class="form__row">
        <div class="field">
            <label for="rm">RM</label>
            <input type="text" id="rm" name="rm" value="<?= e(old('rm')) ?>" inputmode="numeric" maxlength="20" required autofocus<?= field_invalid('rm') ?>>
            <?= field_error('rm') ?>
        </div>
        <div class="field">
            <label for="codigo">Código da ETEC</label>
            <input type="text" id="codigo" name="codigo" value="<?= e(old('codigo')) ?>" maxlength="20" required<?= field_invalid('codigo') ?>>
            <?= field_error('codigo') ?>
        </div>
    </div>

    <label class="check">
        <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?> required>
        <span>Li e aceito os <a href="<?= url('/termos') ?>" target="_blank">termos de uso</a>.</span>
    </label>
    <?= field_error('terms') ?>

    <button type="submit" class="btn btn--primary btn--block btn--lg">Criar conta</button>
</form>

<p class="auth__switch">Não é você? <a href="<?= url('/entrar') ?>">Usar outra conta</a></p>
