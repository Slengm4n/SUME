<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["dish"])) {
    $dish = $_POST["dish"];

    $sql = "INSERT INTO menu (menu_date, dish) VALUES (CURDATE(), ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $dish);
    
    $response = [];

    if ($stmt->execute()) {
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Erro ao cadastrar a refeição.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUME | Cadastrar Refeição</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages/registration.css"> <!-- Link para o arquivo CSS -->
    <script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>

<body>

<div class="container-home">
    <div class="container-home-content">
        <h1 class="main-title">Cadastrar Refeição</h1>
        <form id="dishForm" method="post" class="dish-form">
            <h1 class="subtitle">Refeição:</h1>
            <input type="text" name="dish" class="dish-input" placeholder="Digite aqui a refeição do dia:" required>
            <button type="submit" class="btn-submit">Cadastrar</button>
        </form>  
        <a href="../public/home.php" class="back-link">Voltar para Home</a>

        <div class="popup" id="popup">
            <img src="../assets/img/404-tick.png">
            <h2>Obrigado!</h2>
            <p>Refeição cadastrada com sucesso!</p>
            <a href="../public/home.php" class="btn-close-popup">Voltar</a>
        </div>
    </div>
</div>
<div class="loader"></div>
<script src="../assets/js/loader.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/menu.js"></script>

</body>

</html>
