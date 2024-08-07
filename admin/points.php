<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";
$user_id = $_SESSION["user_id"];

$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Deduzir pontos de todos os usuários
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deduct_points"])) {
    $points_to_deduct = (int)$_POST["points"];
    $sql_deduct_points = "UPDATE user SET points = GREATEST(0, points - ?)";
    $stmt_deduct_points = $mysqli->prepare($sql_deduct_points);
    $stmt_deduct_points->bind_param("i", $points_to_deduct);
    $stmt_deduct_points->execute();
}

// Gerar um número aleatório para confirmação de reset
$reset_confirmation_number = rand(1000, 9999);

// Resetar pontos de todos os usuários
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_points"])) {
    if (isset($_POST["confirmation_number"]) && $_POST["confirmation_number"] == $_SESSION["reset_confirmation_number"]) {
        $sql_reset_points = "UPDATE user SET points = 0";
        $mysqli->query($sql_reset_points);
    } else {
        echo "Número de confirmação incorreto.";
    }
}

$_SESSION["reset_confirmation_number"] = $reset_confirmation_number;

// Buscar todos os usuários e seus pontos ordenados por pontos (ranking)
$sql_users = "SELECT id, name, points, is_master FROM user ORDER BY points DESC";
$result_users = $mysqli->query($sql_users);

// Verifica se houve resultados na consulta
if (!$result_users) {
    // Tratar o caso de erro na consulta, redirecionar ou mostrar uma mensagem de erro
    die("Erro ao buscar usuários: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/pages/points.css">
    <title>Gerenciar Pontos</title>
</head>
<body>
<div class="container">
    <?php if (isset($user) && $user): ?>
        <h1>Gerenciar Pontos dos Usuários</h1>
        <table>
            <tr>
                <th>Posição</th>
                <th>ID do Usuário</th>
                <th>Nome</th>
                <th>Pontos</th>
            </tr>
            <?php
            $posicao = 1;
            while ($user_row = $result_users->fetch_assoc()): ?>
                <tr>
                    <td><?= $posicao++ ?></td>
                    <td><?= htmlspecialchars($user_row["id"]) ?></td>
                    <td><?= htmlspecialchars($user_row["name"]) ?></td>
                    <td><?= htmlspecialchars($user_row["points"]) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <?php if ($user["is_master"] == 1): ?>
            <h2>Deduzir Pontos de Todos os Usuários</h2>
            <form method="post">
                <label for="points">Pontos a deduzir:</label>
                <input type="number" name="points" id="points" required>
                <button type="submit" name="deduct_points">Deduzir Pontos</button>
            </form>

            <h2>Resetar Pontos de Todos os Usuários</h2>
            <form method="post">
                <p>Para confirmar o reset, digite o número: <strong><?= $reset_confirmation_number ?></strong></p>
                <label for="confirmation_number">Número de Confirmação:</label>
                <input type="number" name="confirmation_number" id="confirmation_number" required>
                <button type="submit" name="reset_points">Resetar Pontos</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
