<?php

declare(strict_types=1);

/*
 * Servidor Google FALSO, só para testes automatizados do "Entrar com Google".
 * Imita os dois endpoints do OpenID Connect usados pelo SUME:
 *
 *   GET  /auth   -> devolve o usuário ao redirect_uri com ?code=...&state=...
 *                   (a identidade vem de fake_sub / fake_email / fake_name na URL)
 *   POST /token  -> troca o code por um id_token, conferindo client_id,
 *                   redirect_uri e o PKCE (code_verifier x code_challenge)
 *
 * Uso: php -S 127.0.0.1:8766 tests/fake-google.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
$unb64 = static fn (string $s): string => (string) base64_decode(strtr($s, '-_', '+/'));

if ($path === '/auth') {
    $q = $_GET;
    $redirect = $q['redirect_uri'] ?? '';
    if (($q['fake_error'] ?? '') !== '') {
        header('Location: ' . $redirect . '?' . http_build_query(['error' => $q['fake_error'], 'state' => $q['state'] ?? '']));
        exit;
    }
    if (($q['code_challenge_method'] ?? '') !== 'S256' || empty($q['code_challenge']) || empty($q['nonce'])) {
        http_response_code(400);
        exit('PKCE/nonce ausente');
    }
    $code = $b64(json_encode([
        'sub'       => $q['fake_sub'] ?? '1000001',
        'email'     => $q['fake_email'] ?? 'aluno.google@gmail.com',
        'name'      => $q['fake_name'] ?? 'Aluno Google',
        'verified'  => ($q['fake_unverified'] ?? '') === '',
        'nonce'     => $q['nonce'],
        'challenge' => $q['code_challenge'],
        'client_id' => $q['client_id'] ?? '',
        'redirect'  => $redirect,
    ]));
    header('Location: ' . $redirect . '?' . http_build_query(['code' => $code, 'state' => $q['state'] ?? '']));
    exit;
}

if ($path === '/token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $c = json_decode($unb64($_POST['code'] ?? ''), true);
    $verifierOk = is_array($c) && $b64(hash('sha256', (string) ($_POST['code_verifier'] ?? ''), true)) === $c['challenge'];
    if (!$verifierOk || $c['client_id'] !== ($_POST['client_id'] ?? '') || $c['redirect'] !== ($_POST['redirect_uri'] ?? '')
        || ($_POST['client_secret'] ?? '') === '') {
        http_response_code(400);
        exit(json_encode(['error' => 'invalid_grant']));
    }
    $payload = [
        'iss' => 'https://accounts.google.com', 'aud' => $c['client_id'], 'sub' => $c['sub'],
        'email' => $c['email'], 'email_verified' => $c['verified'], 'name' => $c['name'],
        'nonce' => $c['nonce'], 'iat' => time(), 'exp' => time() + 3600,
    ];
    $jwt = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode($payload)) . '.' . $b64('assinatura-falsa');
    exit(json_encode(['access_token' => 'x', 'id_token' => $jwt, 'token_type' => 'Bearer', 'expires_in' => 3600]));
}

http_response_code(404);
echo 'not found';
