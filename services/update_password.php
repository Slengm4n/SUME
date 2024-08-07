<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Verifica a senha atual
    $sql = "SELECT password_hash FROM user WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET password_hash = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("si", $new_password_hash, $user_id);

            if ($stmt->execute()) {
                echo "Senha atualizada com sucesso.";
                header("Location: ../public/profile.php");
                exit;
            } else {
                echo "Erro ao atualizar a senha.";
            }
        } else {
            echo "As novas senhas não coincidem.";
        }
    } else {
        echo "A senha atual está incorreta.";
    }
}
?>
