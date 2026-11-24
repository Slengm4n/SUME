<?php $wrap = !auth_user(); $pdf = asset('docs/termos-de-uso.pdf'); ?>
<?php if ($wrap): ?><section class="section"><div class="container"><?php endif; ?>

<header class="page-header">
    <div>
        <h1>Termos de uso</h1>
        <p class="muted">Leia com atenção antes de usar o SUME.</p>
    </div>
    <a href="<?= $pdf ?>" class="btn btn--secondary" download><?= icon('file-text') ?> Baixar PDF</a>
</header>

<div class="card card--flush pdf-frame">
    <object data="<?= $pdf ?>" type="application/pdf" aria-label="Termos de uso (PDF)">
        <div class="empty empty--inline">
            <?= icon('file-text') ?>
            <p>Seu navegador não exibe PDFs aqui. <a href="<?= $pdf ?>">Abra o documento</a>.</p>
        </div>
    </object>
</div>

<?php if ($wrap): ?></div></section><?php endif; ?>
