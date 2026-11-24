<?php

declare(strict_types=1);

/*
 * Testa o cálculo de rotas/URLs com o SUME numa subpasta e APP_URL preenchido
 * (cenário do XAMPP: http://localhost:8080/SUME). Rode pelo terminal:
 *
 *   APP_URL=http://localhost:8080/SUME php tests/paths.php
 */

putenv('APP_URL=' . (getenv('APP_URL') ?: 'http://localhost:8080/SUME'));
$_SERVER['SCRIPT_NAME'] = '/SUME/public/index.php';
$_SERVER['REQUEST_URI'] = '/SUME/admin/desperdicio?periodo=90';

require dirname(__DIR__) . '/app/bootstrap.php';

$fail = 0;
$check = static function (string $name, mixed $got, mixed $want) use (&$fail): void {
    $ok = $got === $want;
    $fail += $ok ? 0 : 1;
    echo ($ok ? '  ✔ ' : '  ✘ ') . $name . ($ok ? '' : " (esperado " . var_export($want, true) . ", veio " . var_export($got, true) . ')') . "\n";
};

echo "Rotas com APP_URL em subpasta\n";
$check('current_path() ignora a subpasta', current_path(), '/admin/desperdicio');
$check('is_active() reconhece a página', is_active('/admin/desperdicio'), true);
$check('url() gera endereço completo', url('/entrar'), 'http://localhost:8080/SUME/entrar');
$check('absolute_url() usa o APP_URL', absolute_url('/redefinir-senha', ['token' => 'abc']), 'http://localhost:8080/SUME/redefinir-senha?token=abc');

echo $fail ? "\n{$fail} falha(s)\n" : "\nTudo ok\n";
exit($fail ? 1 : 0);
