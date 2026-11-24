<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

/** Lê uma configuração com notação de ponto: config('mail.host') */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= require BASE_PATH . '/config/app.php';

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/** Escapa texto para HTML. Use SEMPRE ao imprimir dados nas views. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL base da aplicação, sem barra no final.
 * Funciona em http://localhost/SUME, em subpastas e com o servidor embutido do PHP.
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    if (config('url')) {
        return $base = config('url');
    }

    // Servidor embutido (php -S ... -t public): a aplicação fica sempre na raiz
    if (PHP_SAPI === 'cli-server') {
        return $base = '';
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Acesso via .htaccess da raiz (/SUME/painel -> /SUME/public/index.php)
    if (str_ends_with($scriptDir, '/public') && !str_starts_with($uri, $scriptDir . '/') && $uri !== $scriptDir) {
        $scriptDir = substr($scriptDir, 0, -strlen('/public'));
    }

    return $base = rtrim($scriptDir, '/');
}

function url(string $path = '/', array $query = []): string
{
    $url = base_url() . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * URL completa (com http://host), usada em e-mails e no retorno do Google.
 * Em produção defina APP_URL: sem ele, o host vem do cabeçalho da requisição,
 * que pode ser forjado (ataque de "envenenamento" do link de redefinição).
 */
function absolute_url(string $path = '/', array $query = []): string
{
    $relative = url($path, $query);
    if (preg_match('#^https?://#', $relative)) {
        return $relative;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    return ($https ? 'https' : 'http') . '://' . $host . $relative;
}

/** URL de arquivo estático com "cache busting" pela data de modificação. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return url('assets/' . ltrim($path, '/')) . $version;
}

/** Caminho atual da requisição, relativo à aplicação (ex.: "/painel"). */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    // Com APP_URL a base é absoluta (http://host/SUME): compara só a parte do caminho
    $base = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    if (str_starts_with($path, '/public/') || $path === '/public') {
        $path = substr($path, strlen('/public'));
    }
    if ($path === '/index.php') {
        $path = '/';
    }

    return '/' . trim($path, '/');
}

function is_active(string $path): bool
{
    $current = current_path();

    return $path === '/' ? $current === '/' : ($current === $path || str_starts_with($current, rtrim($path, '/') . '/'));
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target, true, 302);
    exit;
}

/** Volta para a página anterior (se for da própria aplicação). */
function back(string $fallback = '/painel'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === explode(':', $host)[0]) {
        header('Location: ' . $referer, true, 302);
        exit;
    }
    redirect($fallback);
}

function view(string $view, array $data = [], ?string $layout = 'app'): void
{
    echo View::render($view, $data, $layout);
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

/**
 * Baixa um CSV que abre direto no Excel em português
 * (UTF-8 com BOM e ponto e vírgula como separador).
 */
function csv_download(string $filename, array $header, iterable $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ';');
    foreach ($rows as $row) {
        // Evita que o Excel interprete células começando com = + - @ como fórmula
        $row = array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $row);
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

/** Percentual no formato brasileiro: 0.1234 -> "12,3%" */
function percent(?float $value, int $decimals = 1): string
{
    return $value === null ? '—' : number_format($value * 100, $decimals, ',', '.') . '%';
}

function number_br(float|int|null $value, int $decimals = 0): string
{
    return $value === null ? '—' : number_format((float) $value, $decimals, ',', '.');
}

function abort(int $code, ?string $message = null): never
{
    http_response_code($code);

    $titles = [
        403 => 'Acesso negado',
        404 => 'Página não encontrada',
        419 => 'Sessão expirada',
        500 => 'Algo deu errado',
    ];
    $messages = [
        403 => 'Você não tem permissão para acessar esta página.',
        404 => 'O endereço que você tentou acessar não existe ou foi movido.',
        419 => 'Recarregue a página e tente novamente.',
        500 => 'Tivemos um problema inesperado. Tente novamente em instantes.',
    ];

    echo View::render('errors/error', [
        'title'   => $titles[$code] ?? 'Erro',
        'code'    => $code,
        'message' => $message ?? ($messages[$code] ?? ''),
    ], 'guest');
    exit;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function flash(string $key, mixed $value): void
{
    Session::flash($key, $value);
}

function flashed(string $key, mixed $default = null): mixed
{
    return Session::getFlash($key, $default);
}

/** Guarda os dados do formulário e os erros para reexibir após o redirect. */
function with_input(array $errors = [], array $except = ['password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation', '_token']): void
{
    flash('old', array_diff_key($_POST, array_flip($except)));
    if ($errors) {
        flash('errors', $errors);
    }
}

function old(string $key, mixed $default = ''): string
{
    $old = flashed('old', []);

    return (string) ($old[$key] ?? $default);
}

function error_for(string $field): ?string
{
    return flashed('errors', [])[$field] ?? null;
}

/** Mensagem de erro de um campo, pronta para a view. */
function field_error(string $field): string
{
    $message = error_for($field);

    return $message ? '<p class="field__error" id="' . e($field) . '-error">' . e($message) . '</p>' : '';
}

/** Atributos de acessibilidade para um campo com erro. */
function field_invalid(string $field): string
{
    return error_for($field) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function auth_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

/** Data de hoje no fuso da aplicação (não depende do fuso do MySQL). */
function today(): string
{
    return date('Y-m-d');
}

function format_date(string $date, bool $withWeekday = false): string
{
    $ts = strtotime($date);
    $weekdays = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    $text = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
    if (date('Y', $ts) !== date('Y')) {
        $text .= ' ' . date('Y', $ts);
    }

    return $withWeekday ? $weekdays[(int) date('w', $ts)] . ', ' . $text : $text;
}

function format_datetime(string $datetime): string
{
    return format_date($datetime) . ' às ' . date('H:i', strtotime($datetime));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

    return mb_strtoupper($first . $last);
}

/** URL da foto de perfil, ou null para usar o avatar com iniciais. */
function avatar_url(?array $user): ?string
{
    $file = basename((string) ($user['profile_picture'] ?? ''));
    if ($file === '' || !is_file(config('uploads.avatars_dir') . '/' . $file)) {
        return null;
    }

    return url('uploads/avatars/' . rawurlencode($file));
}

/** Avatar pronto: foto ou círculo com iniciais. */
function avatar(?array $user, string $size = 'md'): string
{
    $name = (string) ($user['name'] ?? '?');
    $src = avatar_url($user);
    if ($src) {
        return '<img class="avatar avatar--' . e($size) . '" src="' . e($src) . '" alt="" loading="lazy">';
    }

    return '<span class="avatar avatar--' . e($size) . '" aria-hidden="true">' . e(initials($name)) . '</span>';
}

/** Ícone SVG (traço) do conjunto em views/partials/icons.php */
function icon(string $name, string $class = ''): string
{
    static $icons = null;
    $icons ??= require BASE_PATH . '/views/partials/icons.php';

    $paths = $icons[$name] ?? $icons['circle'];

    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

/** O prazo para confirmar/cancelar a refeição de hoje já passou? */
function confirmation_closed(string $menuDate): bool
{
    if ($menuDate > today()) {
        return false;
    }
    if ($menuDate < today()) {
        return true;
    }

    $deadline = (string) config('confirmation_deadline');
    if (!preg_match('/^\d{2}:\d{2}$/', $deadline)) {
        return false;
    }

    return date('H:i') >= $deadline;
}
