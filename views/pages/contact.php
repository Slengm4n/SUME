<?php $wrap = !auth_user(); ?>
<?php if ($wrap): ?><section class="section"><div class="container"><?php endif; ?>

<div class="narrow">
    <header class="page-header page-header--stack">
        <h1>Fale com a gente</h1>
        <p class="muted">Dúvidas, sugestões ou problemas com o SUME? Mande uma mensagem.</p>
    </header>

    <?php if (!$configured): ?>
        <div class="notice notice--warning" role="status">
            <?= icon('alert') ?>
            <p>O envio de e-mails ainda não foi configurado neste servidor. Avise a administração da escola.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/contato') ?>" class="card form" novalidate>
        <?= csrf_field() ?>

        <!-- Campo armadilha contra robôs: deve ficar vazio -->
        <div class="hp" aria-hidden="true">
            <label for="website">Não preencha</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="form__row">
            <div class="field">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" value="<?= e(old('name', $user['name'] ?? '')) ?>" autocomplete="name" maxlength="100" required<?= field_invalid('name') ?>>
                <?= field_error('name') ?>
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" autocomplete="email" required<?= field_invalid('email') ?>>
                <?= field_error('email') ?>
            </div>
        </div>

        <div class="field">
            <label for="subject">Assunto</label>
            <input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>" maxlength="150" required<?= field_invalid('subject') ?>>
            <?= field_error('subject') ?>
        </div>

        <div class="field">
            <label for="message">Mensagem</label>
            <textarea id="message" name="message" rows="6" maxlength="3000" required<?= field_invalid('message') ?>><?= e(old('message')) ?></textarea>
            <?= field_error('message') ?>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn--primary"><?= icon('send') ?> Enviar mensagem</button>
        </div>
    </form>
</div>

<?php if ($wrap): ?></div></section><?php endif; ?>
