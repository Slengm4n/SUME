<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

$sql = "SELECT * FROM user WHERE id = {$_SESSION["user_id"]}";
$result = $mysqli->query($sql);
$user = $result->fetch_assoc();
//Caso seja admin, redireciona para confirmations
if ($user["is_master"] == 1) {
    header("Location: confirmations.php");
    exit;
}
//Busca a refeição do dia
$sql_meal = "SELECT * FROM menu WHERE menu_date = CURDATE()";
$result_meal = $mysqli->query($sql_meal);
$meal = $result_meal->fetch_assoc();

// Verifica se uma refeição foi encontrada
if ($meal) {
    $sql_confirmation = "SELECT * FROM confirmations WHERE user_id = {$_SESSION["user_id"]} AND menu_id = {$meal['id']}";
    $result_confirmation = $mysqli->query($sql_confirmation);
    $user_confirmed = $result_confirmation->num_rows > 0;
    //Verifica se o usuário já tem confirmação do dia efetuada
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$user_confirmed) {
        $sql_add_confirmation = "INSERT INTO confirmations (user_id, menu_id) VALUES ({$_SESSION["user_id"]}, {$meal['id']})";
        if ($mysqli->query($sql_add_confirmation)) {

            // Adicionar pontos pela confirmação de refeição
            $sql_update_points = "UPDATE user SET points = points + 10 WHERE id = {$_SESSION['user_id']}";
            $mysqli->query($sql_update_points);
        }
        header("Location: food.php");
        exit;
    }
} else {
    $user_confirmed = false;
    $meal = ["dish" => "Nenhuma refeição cadastrada para hoje."];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUME | Visualizar Refeição </title>
    <link rel="stylesheet" href="../assets/css/pages/food.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>
<body>

<div class="container-view-food">
    <div class="container-view-food-content">
        <h1 class="main-title">Refeição do Dia:</h2>
        <!--Exibe refeição do dia-->
        <p class="subtitle"><?= htmlspecialchars($meal["dish"]) ?></p>

        <!--Se o usuário não tiver confirmado exibe o btn de confirmar-->
    <?php if (!$user_confirmed && isset($meal['id'])): ?>
        <form method="post" class="confirmation-form">
            <button type="submit" class="btn-submit">Confirmar Participação</button>
        </form>
        <!--Se não exibe um botão de voltar para home ou fazer o feedback da refeição-->
    <?php else: ?>
        <p class="return_alright_confirmed">Você já confirmou sua participação!</p>
        <div class="return_btn">
        <p><a href="home.php">Voltar para Home</a></p>
        <p><a href="../public/feedback.php">Faça um feedback</a></p>
        </div>
    <?php endif; ?>
    </div>
</div>
</body>
</html>
