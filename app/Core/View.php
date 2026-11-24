<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renderiza views PHP dentro de um layout.
 * A view define o conteúdo; o layout recebe $content já renderizado.
 */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $content = self::renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function renderFile(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
