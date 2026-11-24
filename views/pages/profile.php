<header class="page-header">
    <h1>Meu perfil</h1>
</header>

<div class="profile">
    <aside class="card profile__card">
        <form method="post" action="<?= url('/perfil/foto') ?>" enctype="multipart/form-data" class="avatar-upload" data-avatar-form>
            <?= csrf_field() ?>
            <label class="avatar-upload__label" for="avatar">
                <?= avatar($user, 'xl') ?>
                <span class="avatar-upload__overlay"><?= icon('camera') ?><span>Trocar foto</span></span>
            </label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-avatar-input>
            <noscript><button type="submit" class="btn btn--secondary btn--sm">Enviar foto</button></noscript>
        </form>
        <?php if (avatar_url($user)): ?>
            <form method="post" action="<?= url('/perfil/foto/remover') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="link link--muted">Remover foto</button>
            </form>
        <?php endif; ?>
        <p class="muted small">JPG, PNG, WEBP ou GIF · até 2 MB</p>

        <h2 class="profile__name"><?= e($user['name']) ?></h2>
        <?php if (is_admin()): ?><span class="badge badge--brand">Administrador</span><?php endif; ?>

        <dl class="profile__meta">
            <?php if ($user['RM']): ?><div><dt>RM</dt><dd><?= e($user['RM']) ?></dd></div><?php endif; ?>
            <?php if ($user['codigo']): ?><div><dt>ETEC</dt><dd><?= e($user['codigo']) ?></dd></div><?php endif; ?>
            <?php if (!is_admin()): ?><div><dt>Pontos</dt><dd><?= (int) $user['points'] ?></dd></div><?php endif; ?>
            <div><dt>Membro desde</dt><dd><?= e(format_date($user['created_at'])) ?></dd></div>
        </dl>
    </aside>

    <div class="profile__forms">
        <form method="post" action="<?= url('/perfil') ?>" class="card form" novalidate>
            <?= csrf_field() ?>
            <h2 class="card__title"><?= icon('user') ?> Dados pessoais</h2>

            <div class="field">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" autocomplete="name" maxlength="100" required<?= field_invalid('name') ?>>
                <?= field_error('name') ?>
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e(old('email', $user['email'])) ?>" autocomplete="email" maxlength="150" required<?= field_invalid('email') ?>>
                <?= field_error('email') ?>
            </div>
            <p class="muted small">Para alterar RM ou código da ETEC, fale com a administração da escola.</p>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary">Salvar dados</button>
            </div>
        </form>

        <form method="post" action="<?= url('/perfil/senha') ?>" class="card form" id="senha" novalidate>
            <?= csrf_field() ?>
            <h2 class="card__title"><?= icon('lock') ?> <?= $user['has_password'] ? 'Alterar senha' : 'Criar senha' ?></h2>

            <?php if ($user['has_password']): ?>
                <div class="field">
                    <label for="current_password">Senha atual</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required<?= field_invalid('current_password') ?>>
                    <?= field_error('current_password') ?>
                </div>
            <?php else: ?>
                <p class="muted small">Você entra com o Google. Crie uma senha se quiser entrar também com e-mail/RM.</p>
            <?php endif; ?>
            <div class="form__row">
                <div class="field">
                    <label for="new_password">Nova senha</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required<?= field_invalid('new_password') ?>>
                    <?= field_error('new_password') ?: '<p class="field__hint">Mínimo de 8 caracteres, com letras e números.</p>' ?>
                </div>
                <div class="field">
                    <label for="new_password_confirmation">Repita a nova senha</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" required<?= field_invalid('new_password_confirmation') ?>>
                    <?= field_error('new_password_confirmation') ?>
                </div>
            </div>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary"><?= $user['has_password'] ? 'Alterar senha' : 'Criar senha' ?></button>
            </div>
        </form>

        <?php if ($user['google_id'] || \App\Services\GoogleAuth::isConfigured()): ?>
            <section class="card" aria-labelledby="google-titulo">
                <h2 id="google-titulo" class="card__title"><?= icon('shield') ?> Conta Google</h2>
                <?php if ($user['google_id']): ?>
                    <div class="connected">
                        <p><span class="badge badge--success"><?= icon('check') ?> Conectada</span> Você pode entrar com o botão "Entrar com Google".</p>
                        <?php if ($user['has_password']): ?>
                            <form method="post" action="<?= url('/perfil/google/desconectar') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Desconectar sua conta Google? Você continuará entrando com e-mail/RM e senha.">Desconectar</button>
                            </form>
                        <?php else: ?>
                            <p class="muted small">Para desconectar, crie uma senha antes.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="muted">Conecte sua conta Google para entrar com um clique, sem digitar senha.</p>
                    <div class="form__actions form__actions--start">
                        <a href="<?= url('/auth/google') ?>" class="btn btn--google">Conectar conta Google</a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
