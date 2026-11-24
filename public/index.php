<?php

declare(strict_types=1);

/*
 * Front controller: toda requisição passa por aqui.
 * Arquivos estáticos (CSS, JS, imagens) são servidos direto pelo Apache.
 */

// Servidor embutido do PHP (php -S): deixa ele servir os arquivos estáticos
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __FILE__ && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new App\Core\Router();
require BASE_PATH . '/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
