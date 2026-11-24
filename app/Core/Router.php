<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Roteador simples: método + caminho -> [Controller, método], com "middlewares"
 * por rota: 'guest' (só deslogado), 'auth' (logado) e 'admin' (administrador).
 * Todo POST passa pela verificação de CSRF automaticamente.
 */
final class Router
{
    /** @var array<string, array<string, array{0: array{0: class-string, 1: string}, 1: string[]}>> */
    private array $routes = [];

    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->routes['GET'][$this->normalize($path)] = [$action, $middleware];
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->routes['POST'][$this->normalize($path)] = [$action, $middleware];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = $this->normalize($path);
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            $allowed = array_keys(array_filter($this->routes, fn ($r) => isset($r[$path])));
            if ($allowed) {
                http_response_code(405);
                header('Allow: ' . implode(', ', $allowed));
                echo 'Método não permitido.';
                return;
            }
            abort(404);
        }

        [$action, $middleware] = $route;

        if ($method === 'POST' && !Csrf::isValid($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            abort(419, 'Sua sessão expirou. Recarregue a página e tente novamente.');
        }

        foreach ($middleware as $rule) {
            $this->applyMiddleware($rule);
        }

        [$class, $methodName] = $action;
        (new $class())->{$methodName}();
    }

    private function applyMiddleware(string $rule): void
    {
        switch ($rule) {
            case 'guest':
                if (Auth::check()) {
                    redirect('/painel');
                }
                break;
            case 'auth':
                if (!Auth::check()) {
                    flash('error', 'Faça login para continuar.');
                    redirect('/entrar');
                }
                break;
            case 'admin':
                if (!Auth::check()) {
                    redirect('/entrar');
                }
                if (!Auth::isAdmin()) {
                    abort(403);
                }
                break;
        }
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
