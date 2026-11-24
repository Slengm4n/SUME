<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));

// Autoload das classes App\* (sem depender do composer dump-autoload)
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Dependências do Composer (PHPMailer)
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

require BASE_PATH . '/app/helpers.php';

Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) config('timezone'));
mb_internal_encoding('UTF-8');

$isDev = config('env') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');

if (PHP_SAPI !== 'cli') {
    // Cabeçalhos básicos de segurança
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    set_exception_handler(static function (Throwable $e) use ($isDev): void {
        error_log((string) $e);
        if ($isDev) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo $e;
            return;
        }
        abort(500);
    });

    Session::start();
}
