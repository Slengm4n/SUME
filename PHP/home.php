<?php

session_start();

if (isset($_SESSION["user_id"])) {
    
    $mysqli = require __DIR__ . "/database.php";
    
    $sql = "SELECT * FROM user
            WHERE id = {$_SESSION["user_id"]}";
            
    $result = $mysqli->query($sql);
    
    $user = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SUME | Home </title>
	<link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../STYLES/style.css">
	<script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>
<body>
    <header>
        <nav class="navbar">
            <h1>Sistema Único de Merenda Escolar</h1>
            <ul class="nav-menu">
                <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Uniontech</a></li>
                <li class="nav-item"><a href="#" class="nav-link">Saiba mais</a></li>
                <li class="nav-item"><a href="./PHP/signup&signin.php" class="nav-link">Sair</a></li>
            </ul>
            <div class="hamburguer">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>
    <h1>Home</h1>

    <?php if (isset($user) && $user): ?>

        <p>Ola <?= htmlspecialchars($user["name"])?></p>

        <p><a href="logout.php">Log out</a></p>

    <?php else: ?>

        <p><a href="login.php">Log in</a> or <a href="cadastro.php">Cadastro</a></p>

    <?php endif; ?>

</body>
</html>