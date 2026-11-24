<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name'     => Env::get('APP_NAME', 'SUME'),
    'env'      => Env::get('APP_ENV', 'production'),
    'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'America/Sao_Paulo'),

    'db' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => (int) Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'sume_db'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'points' => [
        'confirmation' => (int) Env::get('POINTS_CONFIRMATION', '10'),
        'feedback'     => (int) Env::get('POINTS_FEEDBACK', '5'),
    ],

    'confirmation_deadline' => Env::get('CONFIRMATION_DEADLINE', ''),

    'uploads' => [
        'avatars_dir' => dirname(__DIR__) . '/public/uploads/avatars',
        'max_bytes'   => 2 * 1024 * 1024,
    ],

    'mail' => [
        'host'      => Env::get('MAIL_HOST', ''),
        'port'      => (int) Env::get('MAIL_PORT', '587'),
        'username'  => Env::get('MAIL_USERNAME', ''),
        'password'  => Env::get('MAIL_PASSWORD', ''),
        'from'      => Env::get('MAIL_FROM_ADDRESS', ''),
        'from_name' => Env::get('MAIL_FROM_NAME', 'SUME'),
        'support'   => Env::get('MAIL_SUPPORT_ADDRESS', ''),
    ],
];
