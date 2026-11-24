<?php /** Layout das telas de login e cadastro (tela dividida). */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require BASE_PATH . '/views/partials/head.php'; ?>
</head>
<body class="auth">
<div class="auth__grid">
    <aside class="auth__aside">
        <a href="<?= url('/') ?>" class="auth__brand"><?php require BASE_PATH . '/views/partials/logo.php'; ?></a>
        <div class="auth__pitch">
            <h2>Cada confirmação é um prato a menos no lixo.</h2>
            <p>Avise se vai almoçar, avalie a merenda e suba no ranking da sua escola.</p>
            <ul class="auth__facts">
                <li><?= icon('check-circle') ?> Confirme sua presença em segundos</li>
                <li><?= icon('star') ?> Avalie a refeição e ajude a melhorar o cardápio</li>
                <li><?= icon('trophy') ?> Ganhe pontos e dispute o ranking</li>
            </ul>
        </div>
        <small>&copy; <?= date('Y') ?> UnionTech</small>
    </aside>

    <main id="conteudo" class="auth__main">
        <a href="<?= url('/') ?>" class="auth__back"><?= icon('arrow-left') ?> Voltar ao início</a>
        <div class="auth__card">
            <?= $content ?>
        </div>
    </main>
</div>

<?php require BASE_PATH . '/views/partials/flash.php'; ?>
</body>
</html>
