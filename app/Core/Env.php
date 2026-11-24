<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Leitor mínimo de arquivos .env (KEY=valor), sem dependências externas.
 * Variáveis já definidas no ambiente do servidor têm prioridade.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Remove comentário no fim da linha quando o valor não está entre aspas
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $value = trim(explode(' #', $value, 2)[0]);
            }

            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $fromServer = getenv($key);
        if ($fromServer !== false) {
            return $fromServer;
        }

        return self::$values[$key] ?? $default;
    }
}
