<?php /** Layout das páginas públicas (início, contato, termos, erros). */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require BASE_PATH . '/views/partials/head.php'; ?>
</head>
<body class="guest">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<header class="topbar topbar--guest">
    <div class="topbar__inner container">
        <a href="<?= url('/') ?>" class="topbar__brand"><?php require BASE_PATH . '/views/partials/logo.php'; ?></a>
        <nav class="topbar__actions" aria-label="Principal">
            <a href="<?= url('/contato') ?>" class="btn btn--ghost btn--sm hide-sm">Contato</a>
            <?php if (auth_user()): ?>
                <a href="<?= url('/painel') ?>" class="btn btn--primary btn--sm">Ir para o painel</a>
            <?php else: ?>
                <a href="<?= url('/entrar') ?>" class="btn btn--ghost btn--sm">Entrar</a>
                <a href="<?= url('/cadastro') ?>" class="btn btn--primary btn--sm">Criar conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="conteudo">
    <?= $content ?>
</main>

<footer class="footer">
    <div class="container footer__inner">
        <div class="footer__brand">
            <?php require BASE_PATH . '/views/partials/logo.php'; ?>
            <p>Sistema Único de Merenda Escolar. Um projeto UnionTech.</p>
        </div>
        <nav class="footer__links" aria-label="Rodapé">
            <a href="<?= url('/contato') ?>">Contato</a>
            <a href="<?= url('/termos') ?>">Termos de uso</a>
            <a href="https://github.com/Slengm4n/SUME" target="_blank" rel="noopener">Código-fonte</a>
        </nav>
        <small>&copy; <?= date('Y') ?> UnionTech. Todos os direitos reservados.</small>
    </div>
</footer>

<?php require BASE_PATH . '/views/partials/flash.php'; ?>
</body>
</html>
