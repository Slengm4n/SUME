<h1 class="auth__title">Criar conta</h1>
<p class="auth__subtitle">Leva menos de um minuto.</p>

<form method="post" action="<?= url('/cadastro') ?>" class="form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Nome completo</label>
        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" autocomplete="name" maxlength="100" required<?= field_invalid('name') ?>>
        <?= field_error('name') ?>
    </div>

    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="150" required<?= field_invalid('email') ?>>
        <?= field_error('email') ?>
    </div>

    <div class="form__row">
        <div class="field">
            <label for="rm">RM</label>
            <input type="text" id="rm" name="rm" value="<?= e(old('rm')) ?>" inputmode="numeric" maxlength="20" required<?= field_invalid('rm') ?>>
            <?= field_error('rm') ?>
        </div>
        <div class="field">
            <label for="codigo">Código da ETEC</label>
            <input type="text" id="codigo" name="codigo" value="<?= e(old('codigo')) ?>" maxlength="20" required<?= field_invalid('codigo') ?>>
            <?= field_error('codigo') ?>
        </div>
    </div>

    <div class="field">
        <label for="password">Senha</label>
        <div class="input-group">
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required<?= error_for('password') ? field_invalid('password') : ' aria-describedby="password-hint"' ?>>
            <button type="button" class="input-group__btn" data-toggle-password="password" aria-label="Mostrar senha">Mostrar</button>
        </div>
        <?= error_for('password') ? field_error('password') : '<p class="field__hint" id="password-hint">Mínimo de 8 caracteres, com letras e números.</p>' ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirme a senha</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= field_invalid('password_confirmation') ?>>
        <?= field_error('password_confirmation') ?>
    </div>

    <label class="check">
        <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?> required>
        <span>Li e aceito os <a href="<?= url('/termos') ?>" target="_blank">termos de uso</a>.</span>
    </label>
    <?= field_error('terms') ?>

    <button type="submit" class="btn btn--primary btn--block btn--lg">Criar conta</button>
</form>

<p class="auth__switch">Já tem conta? <a href="<?= url('/entrar') ?>">Entrar</a></p>
