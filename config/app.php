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
        // smtp = envia de verdade | log = grava em storage/logs/mail.log (desenvolvimento e testes)
        'driver'    => Env::get('MAIL_DRIVER', 'smtp'),
        'host'      => Env::get('MAIL_HOST', ''),
        'port'      => (int) Env::get('MAIL_PORT', '587'),
        'username'  => Env::get('MAIL_USERNAME', ''),
        'password'  => Env::get('MAIL_PASSWORD', ''),
        'from'      => Env::get('MAIL_FROM_ADDRESS', ''),
        'from_name' => Env::get('MAIL_FROM_NAME', 'SUME'),
        'support'   => Env::get('MAIL_SUPPORT_ADDRESS', ''),
    ],

    'google' => [
        'client_id'     => Env::get('GOOGLE_CLIENT_ID', ''),
        'client_secret' => Env::get('GOOGLE_CLIENT_SECRET', ''),
        // Ex.: "etec.sp.gov.br,cps.sp.gov.br" para aceitar só e-mails desses domínios (vazio = qualquer conta Google)
        'allowed_domains' => array_values(array_filter(array_map('trim', explode(',', (string) Env::get('GOOGLE_ALLOWED_DOMAINS', ''))))),
        // Só para testes automatizados (servidor Google falso). Nunca altere em produção.
        'auth_url'  => Env::get('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'token_url' => Env::get('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
    ],

    'password_reset_minutes' => 60,
];
