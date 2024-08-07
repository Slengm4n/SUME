<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $company = $_POST["company"];

    $sql = "UPDATE user SET name = ?, email = ?, company = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssi", $name, $email, $company, $user_id);

    if ($stmt->execute()) {
        $_SESSION["user_name"] = $name;
        header("Location: ../public/profile.php");
        exit;
    } else {
        echo "Erro ao atualizar o perfil.";
    }
}
?>
