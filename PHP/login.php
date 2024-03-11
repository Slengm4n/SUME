<?php

$is_invalid = false;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

	$mysqli = require __DIR__ . "/database.php";

	$sql = sprintf("SELECT * FROM user
			WHERE email = '%s'",
			$mysqli->real_escape_string($_POST["email"]));

	$result = $mysqli->query($sql);

	$user =$result->fetch_assoc();

	if ($user) {

		if (password_verify($_POST["password"], $user ["password_hash"])){
			
			session_start();

			session_regenerate_id();

			$_SESSION["user_id"] = $user["id"];

			header("Location: index.php");
			exit;
		}
	}	
	$is_invalid = true; 
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SUME | Login </title>
	<link rel="stylesheet" type="text/css" href="../STYLES/style.css">
	<link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
	<script src="https://kit.fontawesome.com/a81368914c.js"></script>
</head>
<body>
	<img class="wave" src="../IMG/wave1.svg">

	<div class="container">
		<div class="img">
			<img src="../IMG/bg.svg">
		</div>
		<div class="login-content">
			<form method="post">
				<img src="../IMG/avatar.svg">
				<h2 class="title">BEM-VINDO!</h2>
				 
				<?php if ($is_invalid): ?>
					<em>Login invalido</em>
					<?php endif; ?>

           		<div class="input-div one">
           		   <div class="i">
           		   		<i class="fas fa-user"></i>
           		   </div>
           		   <div class="div">
           		   		<h5>Email</h5>
           		   		<input type="email" class="input" name="email"
						value="<?= htmlspecialchars($_POST["email"]?? "")?>">
           		   </div>
           		</div>
           		<div class="input-div pass">
           		   <div class="i"> 
           		    	<i class="fas fa-lock"></i>
           		   </div>
           		   <div class="div">
           		    	<h5>Senha</h5>
           		    	<input type="password" class="input"  name="password">
            	   </div>
            	</div>
            	<a class="forgot_password" href="#">Forgot Password?</a>
            	<input type="submit" class="btn" value="Login">
            </form>
        </div>
    </div>
	<script src="../SCRIPTS/menu.js"></script>
    <script type="text/javascript" src="../SCRIPTS/login.js"></script>
</body>
</html>
