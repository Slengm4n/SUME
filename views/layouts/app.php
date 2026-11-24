<?php
/** Layout da área logada (alunos e administradores). */
$me = auth_user();
$nav = is_admin()
    ? [
        ['/painel', 'Painel', 'dashboard'],
        ['/admin/cardapio', 'Cardápio', 'calendar'],
        ['/admin/confirmacoes', 'Confirmações', 'clipboard-check'],
        ['/admin/desperdicio', 'Desperdício', 'leaf'],
        ['/admin/avaliacoes', 'Avaliações', 'star'],
        ['/admin/usuarios', 'Usuários', 'users'],
    ]
    : [
        ['/painel', 'Painel', 'home'],
        ['/ranking', 'Ranking', 'trophy'],
        ['/perfil', 'Perfil', 'user'],
    ];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require BASE_PATH . '/views/partials/head.php'; ?>
</head>
<body class="app">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<header class="topbar">
    <div class="topbar__inner container">
        <a href="<?= url('/painel') ?>" class="topbar__brand"><?php require BASE_PATH . '/views/partials/logo.php'; ?></a>

        <nav class="topbar__nav" aria-label="Principal">
            <?php foreach ($nav as [$path, $label, $ic]): ?>
                <a href="<?= url($path) ?>" class="navlink<?= is_active($path) ? ' is-active' : '' ?>" <?= is_active($path) ? 'aria-current="page"' : '' ?>>
                    <?= icon($ic) ?><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <details class="dropdown usermenu" data-dropdown>
            <summary class="usermenu__trigger" aria-label="Menu da conta">
                <?= avatar($me, 'sm') ?>
                <span class="usermenu__name"><?= e(explode(' ', $me['name'])[0]) ?></span>
                <?= icon('chevron-down', 'usermenu__chevron') ?>
            </summary>
            <div class="dropdown__panel" role="menu">
                <div class="dropdown__header">
                    <strong><?= e($me['name']) ?></strong>
                    <span><?= e($me['email']) ?></span>
                    <?php if (is_admin()): ?><span class="badge badge--brand">Administrador</span><?php endif; ?>
                </div>
                <a href="<?= url('/perfil') ?>" role="menuitem"><?= icon('user') ?> Meu perfil</a>
                <?php if (is_admin()): ?>
                    <a href="<?= url('/ranking') ?>" role="menuitem"><?= icon('trophy') ?> Ranking</a>
                    <?php /* No celular a barra inferior mostra só 5 itens; o restante fica aqui */ ?>
                    <?php foreach (array_slice($nav, 5) as [$path, $label, $ic]): ?>
                        <a href="<?= url($path) ?>" role="menuitem" class="show-sm"><?= icon($ic) ?> <?= e($label) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= url('/contato') ?>" role="menuitem"><?= icon('mail') ?> Contato</a>
                <a href="<?= url('/termos') ?>" role="menuitem"><?= icon('file-text') ?> Termos de uso</a>
                <form method="post" action="<?= url('/sair') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" role="menuitem"><?= icon('log-out') ?> Sair</button>
                </form>
            </div>
        </details>
    </div>
</header>

<main id="conteudo" class="main container">
    <?= $content ?>
</main>

<nav class="tabbar" aria-label="Navegação rápida">
    <?php foreach (array_slice($nav, 0, 5) as [$path, $label, $ic]): ?>
        <a href="<?= url($path) ?>" class="tabbar__item<?= is_active($path) ? ' is-active' : '' ?>" <?= is_active($path) ? 'aria-current="page"' : '' ?>>
            <?= icon($ic) ?><span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<footer class="footer footer--app">
    <div class="container footer__inner">
        <span>&copy; <?= date('Y') ?> SUME · UnionTech</span>
        <span><a href="<?= url('/termos') ?>">Termos de uso</a> · <a href="<?= url('/contato') ?>">Contato</a></span>
    </div>
</footer>

<?php require BASE_PATH . '/views/partials/flash.php'; ?>
</body>
</html>
