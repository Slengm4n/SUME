<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$mysqli = require __DIR__ . "/../database/db.php";

$sql = "SELECT * FROM menu WHERE menu_date = CURDATE()";
$result = $mysqli->query($sql);
$meal = $result->fetch_assoc();

$user_id = $_SESSION["user_id"];
$meal_id = $meal['id'] ?? null;

// Verificar se o usuário confirmou a presença na refeição do dia
$sql_check_confirmation = "SELECT * FROM confirmations WHERE user_id = ? AND menu_id = ?";
$stmt_check_confirmation = $mysqli->prepare($sql_check_confirmation);
$stmt_check_confirmation->bind_param("ii", $user_id, $meal_id);
$stmt_check_confirmation->execute();
$result_check_confirmation = $stmt_check_confirmation->get_result();
$user_confirmed = $result_check_confirmation->num_rows > 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && $user_confirmed) {
    $rating = $_POST["rating"];
    $comment = $_POST["comment"];

    // Verificar se o usuário já fez feedback para esta refeição
    $sql_check_feedback = "SELECT * FROM feedbacks WHERE user_id = ? AND meal_id = ?";
    $stmt_check_feedback = $mysqli->prepare($sql_check_feedback);
    $stmt_check_feedback->bind_param("ii", $user_id, $meal_id);
    $stmt_check_feedback->execute();
    $result_check_feedback = $stmt_check_feedback->get_result();

    if ($result_check_feedback->num_rows > 0) {
        $feedback_message = "Você já enviou um feedback para esta refeição.";
    } else {
        $sql = "INSERT INTO feedbacks (user_id, meal_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiis", $user_id, $meal_id, $rating, $comment);

        if ($stmt->execute()) {
            // Adicionar pontos pelo feedback
            $sql_update_points = "UPDATE user SET points = points + 5 WHERE id = ?";
            $stmt_update_points = $mysqli->prepare($sql_update_points);
            $stmt_update_points->bind_param("i", $user_id);
            $stmt_update_points->execute();

            header("Location: feedback.php");
            exit;
        } else {
            $feedback_message = "Erro ao enviar o feedback.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/pages/feedback.css">
</head>
<body>

<div class="wrapper">
		<h3>Feedback da Refeição</h3>
        <p>Refeição: <?= htmlspecialchars($meal["dish"] ?? "Nenhuma refeição cadastrada para hoje.") ?></p>
        <?php if ($meal): ?>
            <?php if ($user_confirmed): ?>
		<form id="dishForm" method="post">
			<div class="rating">
				<input type="number" name="rating" hidden>
				<i class='bx bx-star star' style="--i: 0;"></i>
				<i class='bx bx-star star' style="--i: 1;"></i>
				<i class='bx bx-star star' style="--i: 2;"></i>
				<i class='bx bx-star star' style="--i: 3;"></i>
				<i class='bx bx-star star' style="--i: 4;"></i>
			</div>
			<textarea name="comment" cols="30" rows="5" placeholder="Sua opinião..."></textarea>
			<div class="btn-group">
				<button type="submit" class="btn submit" onclick="openPopup()">Enviar</button>
				<a href="../public/home.php" class="btn cancel">Cancelar</a>

	</div>

		</form>
        <?php if (isset($feedback_message)): ?>
                <p class="alert"><?= $feedback_message ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="alert">Você precisa confirmar sua presença na refeição do dia para enviar um feedback.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="alert">Não há uma refeição cadastrada para hoje. Volte mais tarde.</p>
    <?php endif; ?>
</div>
<script src="../assets/js/rating.js"></script>
</body>
</html>
