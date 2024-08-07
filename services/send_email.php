<?php

$name = $_POST["name"];
$email = $_POST["email"];
$subject = $_POST["subject"];
$message = $_POST["message"];

require '../vendor/autoload.php';

use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer (true);

$mail->isSMTP();
$mail->SMTPAuth = true;

$mail->Host = "smtp-relay.brevo.com";
$mail->SMTPSecure = PHPMailer :: ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->Username = "unionstech777@gmail.com";
$mail->Password = "KWTj75UOfhgXNwzB";

$mail->setFrom($email, $name);
$mail->addAddress("unionstech777@gmail.com", "Suporte UnionTech");

$mail->Subject = $subject;
$mail->Body = $message;

$mail->send();

echo "email enviado";
