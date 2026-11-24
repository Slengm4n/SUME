<?php

declare(strict_types=1);

/*
 * Cria um administrador (ou promove um usuário existente).
 *
 *   php bin/create-admin.php "Nome Completo" email@escola.com
 *
 * A senha é pedida no terminal (ou use a variável SUME_ADMIN_PASSWORD).
 */

if (PHP_SAPI !== 'cli') {
    exit("Execute pelo terminal.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Repositories\UserRepository;

[$script, $name, $email] = array_pad($argv, 3, null);
if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php bin/create-admin.php \"Nome Completo\" email@escola.com\n");
    exit(1);
}

$email = mb_strtolower($email);
$users = new UserRepository();

if ($users->emailTaken($email)) {
    Database::connection()->prepare('UPDATE user SET is_master = 1 WHERE email = ?')->execute([$email]);
    echo "Usuário {$email} promovido a administrador.\n";
    exit(0);
}

$password = getenv('SUME_ADMIN_PASSWORD') ?: null;
if ($password === null) {
    echo 'Senha (mín. 8 caracteres, letras e números): ';
    $password = trim((string) fgets(STDIN));
}
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "Senha fraca: use pelo menos 8 caracteres, com letras e números.\n");
    exit(1);
}

$users->create([
    'name'      => $name,
    'email'     => $email,
    'rm'        => '',
    'codigo'    => '',
    'password'  => $password,
    'is_master' => 1,
]);

echo "Administrador {$email} criado. Acesse /entrar para fazer login.\n";
