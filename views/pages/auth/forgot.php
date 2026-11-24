<h1 class="auth__title">Esqueceu a senha?</h1>
<p class="auth__subtitle">Informe o e-mail da sua conta e enviaremos um link para você criar uma nova senha.</p>

<?php if (!$available): ?>
    <div class="notice notice--warning" role="status">
        <?= icon('alert') ?>
        <p>A recuperação por e-mail ainda não foi configurada neste servidor. Fale com a administração da escola.</p>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/esqueci-senha') ?>" class="form" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" autocapitalize="none" required autofocus<?= field_invalid('email') ?>>
        <?= field_error('email') ?>
    </div>
    <button type="submit" class="btn btn--primary btn--block btn--lg"<?= $available ? '' : ' disabled' ?>><?= icon('mail') ?> Enviar link</button>
</form>

<p class="auth__switch">Lembrou? <a href="<?= url('/entrar') ?>">Voltar para o login</a></p>
