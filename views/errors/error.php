<section class="section">
    <div class="container narrow empty">
        <span class="empty__code"><?= (int) $code ?></span>
        <h1><?= e($title) ?></h1>
        <p><?= e($message) ?></p>
        <div class="empty__actions">
            <a href="<?= url(auth_user() ? '/painel' : '/') ?>" class="btn btn--primary"><?= icon('home') ?> Voltar ao início</a>
        </div>
    </div>
</section>
