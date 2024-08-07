<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$userId) {
  header("Location: users.php");
  exit;
}

$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
  header("Location: users.php");
  exit;
}

$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = trim($_POST["name"]);
  $email = trim($_POST["email"]);
  $rm = trim($_POST["rm"]);
  $codigo = trim($_POST["codigo"]);

  $sql = "UPDATE user SET name = ?, email = ?, RM = ?, codigo = ? WHERE id = ?";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('sssss', $name, $email, $rm, $codigo, $userId);

  if ($stmt->execute()) {
    header("Location: ../admin/users.php");
    exit;
  } else {
    // Exibir mensagem de erro
    $errorMessage = "Erro ao atualizar o usuário.";
  }
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,   
 initial-scale=1.0">
  <title>SUME   
 | Editar Usuário</title>
  <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/pages/users.css">   

  <script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>
<body>
  <div class="container-users">
    <h1>Editar Usuário</h1>
    <?php if (isset($errorMessage)): ?>
      <p class="error-message"><?= $errorMessage ?></p>
    <?php endif; ?>
    <form action="" method="post" class="edit-user-form">
      <input type="hidden" name="id" value="<?= $user['id'] ?>">
      <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>
      <div class="form-group">
        <label for="rm">RM:</label>
        <input type="text" name="rm" id="rm" value="<?= htmlspecialchars($user['RM']) ?>" required>
      </div>
      <div class="form-group">
        <label for="codigo">Código:</label>
        <input type="text" name="codigo" id="codigo" value="<?= htmlspecialchars($user['codigo']) ?>" required>
      </div>
      <button type="submit" class="btn">Atualizar</button>
      <a href="users.php" class="btn btn-cancel">Cancelar</a>
    </form>
    <p><a href="../public/home.php" class="back-link">Voltar para Home</a></p>
  </div>
</body>
</html>