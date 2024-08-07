<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

// Verifica se o usuário é um administrador
$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user["is_master"] != 1) {
    header("Location: visualizar_refeicao.php");
    exit;
}

// Obtém a refeição do dia atual
$sql_meal = "SELECT * FROM menu WHERE menu_date = CURDATE()";
$result_meal = $mysqli->query($sql_meal);
$meal = $result_meal->fetch_assoc();

$response = [
    'meal' => $meal,
    'total_confirmations' => 0,
    'confirmations' => [],
];

if ($meal) {
    $sql_confirmations = "SELECT * FROM confirmations WHERE menu_id = ?";
    $stmt_confirmations = $mysqli->prepare($sql_confirmations);
    $stmt_confirmations->bind_param("i", $meal['id']);
    $stmt_confirmations->execute();
    $result_confirmations = $stmt_confirmations->get_result();
    $response['total_confirmations'] = $result_confirmations->num_rows;

    while ($confirmation = $result_confirmations->fetch_assoc()) {
        // Obtém os dados do usuário
        $sql_user = "SELECT * FROM user WHERE id = ?";
        $stmt_user = $mysqli->prepare($sql_user);
        $stmt_user->bind_param("i", $confirmation['user_id']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $user_data = $result_user->fetch_assoc();

        // Adiciona caminho da foto do usuário
        $profile_picture = "../assets/img/default.jpg";
        if (!empty($user_data["profile_picture"])) {
            $profile_picture_path = "../uploads/" . $user_data["profile_picture"];
            if (file_exists($profile_picture_path)) {
                $profile_picture = $profile_picture_path;
            }
        }

        $user_data["profile_picture"] = $profile_picture;
        $response['confirmations'][] = $user_data;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
