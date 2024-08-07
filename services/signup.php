<?php

if (empty($_POST["name"])){
    die("Nome é obrigatório");
}

if ( ! filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    die("E-mail válido é obrigatório");
}

if (strlen($_POST["password"]) < 6 ) {
    die("A senha deve conter no mínimo 8 caracteres");
}

if ( ! preg_match("/[a-z]/i", $_POST["password"])) {
    die ("A senha conter no mínimo uma letra");
}

if ( ! preg_match("/[0-9]/i", $_POST["password"])) {
    die ("A senha conter no mínimo um número");
}

if ($_POST["password"] !== $_POST["password_confirmation"]){
    die("A senhas não condizem");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
  
$mysqli = require __DIR__ . "/../database/db.php";

$sql = "INSERT INTO user (name, email, RM, codigo, password_hash)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $mysqli->stmt_init();

if ( ! $stmt->prepare($sql)){
    die("SQL error:" . $mysqli->error);
}

$stmt->bind_param("sssss",
                    $_POST["name"],
                    $_POST["email"],
                    $_POST["RM"],
                    $_POST["codigo"],
                    $password_hash);

if ($stmt->execute()) {      
    

    header("Location: ../public/login.php");
    exit;
    
} else {
    
    if ($mysqli->errno === 1062) {
        die("email already taken");
    } else {
        die($mysqli->error . " " . $mysqli->errno);
    }
}

