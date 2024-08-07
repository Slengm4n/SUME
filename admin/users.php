<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

$sql = "SELECT * FROM user WHERE is_master = 0"; // Exclui usuários administradores
$result = $mysqli->query($sql);

$users = [];
while ($user = $result->fetch_assoc()) {
    $users[] = $user;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUME | Usuários </title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pages/users.css">
    <script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>
<body>
    <div class="container-users">
        <h1>Gerenciador Usuários</h1>
            <table class="content-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>RM</th>
                        <th>Código</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['RM']) ?></td>
                            <td><?= htmlspecialchars($user['codigo']) ?></td>
                            <td>
                                 <a href="../services/update_user.php?id=<?= $user['id'] ?>" class="btn btn-edit">Editar</a>
                                 <a href="../services/delete_user.php?id=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><a href="../public/home.php" class="back-link">Voltar para Home</a></p>
        </div>
<div class="loader"></div>
<script src="../assets/js/loader.js"></script>
</body>
</html>
