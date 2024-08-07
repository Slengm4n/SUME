<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";
$user_id = $_SESSION["user_id"];

// Define o diretório de upload
$target_dir = "../uploads/";
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));

// Gera um nome de arquivo único para evitar conflitos
$target_file = $target_dir . uniqid('', true) . '.' . $imageFileType;

// Verifica se o arquivo é uma imagem real
$check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
if ($check === false) {
    echo "O arquivo não é uma imagem.";
    $uploadOk = 0;
}

// Verifica o tamanho do arquivo (limite de 500KB)
if ($_FILES["profile_picture"]["size"] > 500000) {
    echo "Desculpe, o seu arquivo é muito grande.";
    $uploadOk = 0;
}

// Permite apenas certos formatos de arquivo
$allowed_file_types = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($imageFileType, $allowed_file_types)) {
    echo "Desculpe, apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
    $uploadOk = 0;
}

// Verifica se $uploadOk está definido como 0 por algum erro
if ($uploadOk == 0) {
    echo "Desculpe, seu arquivo não foi enviado.";
} else {
    // Tenta fazer o upload do arquivo
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        // Atualiza o caminho da imagem de perfil no banco de dados
        $sql = "UPDATE user SET profile_picture = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $target_file, $user_id);
            $stmt->execute();
            $stmt->close();
            
            header("Location: ../public/profile.php");
            exit;
        } else {
            echo "Erro ao preparar a consulta SQL.";
        }
    } else {
        echo "Desculpe, houve um erro ao enviar seu arquivo.";
    }
}
?>
