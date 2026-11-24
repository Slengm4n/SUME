<?php if (!$reset): ?>
    <h1 class="auth__title">Link inválido</h1>
    <p class="auth__subtitle">Este link de redefinição expirou ou já foi usado. Os links valem por <?= (int) config('password_reset_minutes') ?> minutos e funcionam uma única vez.</p>
    <a href="<?= url('/esqueci-senha') ?>" class="btn btn--primary btn--block btn--lg">Pedir um novo link</a>
    <p class="auth__switch"><a href="<?= url('/entrar') ?>">Voltar para o login</a></p>
<?php else: ?>
    <h1 class="auth__title">Crie uma nova senha</h1>
    <p class="auth__subtitle">Para a conta <strong><?= e($reset['email']) ?></strong>.</p>

    <form method="post" action="<?= url('/redefinir-senha') ?>" class="form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <!-- Ajuda gerenciadores de senha a salvar no usuário certo -->
        <input type="email" name="username" value="<?= e($reset['email']) ?>" autocomplete="username" hidden>

        <div class="field">
            <label for="password">Nova senha</label>
            <div class="input-group">
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required autofocus<?= error_for('password') ? field_invalid('password') : ' aria-describedby="password-hint"' ?>>
                <button type="button" class="input-group__btn" data-toggle-password="password" aria-label="Mostrar senha">Mostrar</button>
            </div>
            <?= error_for('password') ? field_error('password') : '<p class="field__hint" id="password-hint">Mínimo de 8 caracteres, com letras e números.</p>' ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirme a nova senha</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= field_invalid('password_confirmation') ?>>
            <?= field_error('password_confirmation') ?>
        </div>

        <button type="submit" class="btn btn--primary btn--block btn--lg">Salvar nova senha</button>
    </form>
<?php endif; ?>
