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

// Exclusão do usuário
$sql = "DELETE FROM user WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
  header("Location: ../admin/users.php");
  exit;
} else {
  // Exibir mensagem de erro
  echo "Erro ao excluir o usuário.";
}

$stmt->close();
$mysqli->close();