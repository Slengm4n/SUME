<h1 class="auth__title">Entrar</h1>
<p class="auth__subtitle">Bem-vindo de volta! Use seu e-mail ou RM.</p>

<form method="post" action="<?= url('/entrar') ?>" class="form" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="identifier">E-mail ou RM</label>
        <input type="text" id="identifier" name="identifier" value="<?= e(old('identifier')) ?>"
               autocomplete="username" autocapitalize="none" required autofocus>
    </div>

    <div class="field">
        <label for="password">Senha</label>
        <div class="input-group">
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="input-group__btn" data-toggle-password="password" aria-label="Mostrar senha">Mostrar</button>
        </div>
    </div>

    <button type="submit" class="btn btn--primary btn--block btn--lg">Entrar</button>
</form>

<p class="auth__switch">Ainda não tem conta? <a href="<?= url('/cadastro') ?>">Crie agora</a></p>
