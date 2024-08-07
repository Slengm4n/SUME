<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

$sql = "SELECT f.*, u.name, u.RM, m.dish 
        FROM feedbacks f
        JOIN user u ON f.user_id = u.id
        JOIN menu m ON f.meal_id = m.id
        ORDER BY f.created_at DESC";
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks e Rankings</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages/view_feedbacks.css">
</head>
<body>
<div class="container">
    <h1>Feedbacks e Rankings</h1>
    
    <table>
        <thead>
            <tr>
                <th>Nome do Aluno</th>
                <th>RM</th>
                <th>Refeição</th>
                <th>Avaliação</th>
                <th>Comentário</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($feedback = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($feedback["name"]) ?></td>
                    <td><?= htmlspecialchars($feedback["RM"]) ?></td>
                    <td><?= htmlspecialchars($feedback["dish"]) ?></td>
                    <td><?= htmlspecialchars($feedback["rating"]) ?></td>
                    <td><?= htmlspecialchars($feedback["comment"]) ?></td>
                    <td><?= htmlspecialchars($feedback["created_at"]) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="../admin/confirmations.php">Voltar</a>
</div>
</body>
</html>
