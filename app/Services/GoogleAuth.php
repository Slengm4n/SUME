<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * "Entrar com Google" via OpenID Connect (fluxo authorization code + PKCE).
 *
 * Proteções:
 *  - state:  valor aleatório na sessão; impede que alguém force um login (CSRF)
 *  - nonce:  vai dentro do id_token; impede reaproveitar um token antigo
 *  - PKCE:   o código só vale com o "code_verifier" que só o nosso servidor tem
 *  - O id_token é recebido direto do Google por HTTPS (canal servidor-servidor),
 *    então pela especificação OIDC (Core 3.1.3.7) basta validar os campos dele:
 *    emissor, destinatário (aud), validade, nonce e e-mail verificado.
 */
final class GoogleAuth
{
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];
    private const SESSION_KEY = 'google_oauth';
    private const MAX_AGE = 600; // o usuário tem 10 min para concluir no Google

    public static function isConfigured(): bool
    {
        return config('google.client_id') !== '' && config('google.client_secret') !== '';
    }

    public static function redirectUri(): string
    {
        return absolute_url('/auth/google/callback');
    }

    /** URL do Google para onde o usuário é mandado. */
    public static function authorizationUrl(string $intent = 'login'): string
    {
        $verifier = self::base64url(random_bytes(32));
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        Session::put(self::SESSION_KEY, [
            'state'    => $state,
            'nonce'    => $nonce,
            'verifier' => $verifier,
            'intent'   => $intent,
            'at'       => time(),
        ]);

        return self::endpoint('auth_url') . '?' . http_build_query([
            'client_id'             => config('google.client_id'),
            'redirect_uri'          => self::redirectUri(),
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => self::base64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'prompt'                => 'select_account',
        ]);
    }

    /**
     * Valida o retorno do Google e devolve o perfil verificado.
     *
     * @return array{sub: string, email: string, name: string, intent: string}
     * @throws \RuntimeException com mensagem amigável quando algo não confere
     */
    public static function handleCallback(string $state, string $code): array
    {
        $saved = Session::get(self::SESSION_KEY);
        Session::forget(self::SESSION_KEY); // uso único

        if (!is_array($saved) || $state === '' || !hash_equals($saved['state'], $state)) {
            throw new \RuntimeException('A tentativa de login expirou ou não é válida. Tente de novo.');
        }
        if (time() - $saved['at'] > self::MAX_AGE) {
            throw new \RuntimeException('Demorou demais para concluir o login com o Google. Tente de novo.');
        }
        if ($code === '') {
            throw new \RuntimeException('O Google não retornou o código de acesso. Tente de novo.');
        }

        $tokens = self::exchangeCode($code, $saved['verifier']);
        $claims = self::decodeIdToken($tokens['id_token'] ?? '');
        self::validateClaims($claims, $saved['nonce']);

        return [
            'sub'    => (string) $claims['sub'],
            'email'  => mb_strtolower((string) $claims['email']),
            'name'   => trim((string) ($claims['name'] ?? '')) ?: explode('@', (string) $claims['email'])[0],
            'intent' => (string) $saved['intent'],
        ];
    }

    private static function exchangeCode(string $code, string $verifier): array
    {
        $ch = curl_init(self::endpoint('token_url'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => config('google.client_id'),
                'client_secret' => config('google.client_secret'),
                'redirect_uri'  => self::redirectUri(),
                'grant_type'    => 'authorization_code',
                'code_verifier' => $verifier,
            ]),
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status !== 200) {
            error_log("Google token endpoint falhou: HTTP {$status} {$error} " . substr((string) $body, 0, 300));
            throw new \RuntimeException('Não foi possível concluir o login com o Google. Tente de novo.');
        }

        $data = json_decode((string) $body, true);

        return is_array($data) ? $data : [];
    }

    private static function decodeIdToken(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Resposta inválida do Google.');
        }
        $payload = json_decode((string) self::base64urlDecode($parts[1]), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Resposta inválida do Google.');
        }

        return $payload;
    }

    private static function validateClaims(array $c, string $nonce): void
    {
        $now = time();
        $ok = in_array($c['iss'] ?? '', self::ISSUERS, true)
            && ($c['aud'] ?? '') === config('google.client_id')
            && (int) ($c['exp'] ?? 0) > $now - 60
            && (int) ($c['iat'] ?? 0) < $now + 300
            && isset($c['nonce']) && hash_equals($nonce, (string) $c['nonce'])
            && !empty($c['sub']) && !empty($c['email']);

        if (!$ok) {
            error_log('id_token do Google rejeitado: ' . json_encode(array_intersect_key($c, array_flip(['iss', 'aud', 'exp', 'iat']))));
            throw new \RuntimeException('Não foi possível validar sua conta Google. Tente de novo.');
        }
        if (($c['email_verified'] ?? false) !== true && ($c['email_verified'] ?? '') !== 'true') {
            throw new \RuntimeException('Seu e-mail do Google ainda não foi verificado.');
        }

        $allowed = config('google.allowed_domains');
        if ($allowed) {
            $domain = strtolower(substr(strrchr((string) $c['email'], '@') ?: '', 1));
            if (!in_array($domain, array_map('strtolower', $allowed), true)) {
                throw new \RuntimeException('Use o e-mail institucional da escola (' . implode(', ', $allowed) . ').');
            }
        }
    }

    /** Endereços do Google. A troca por um servidor falso só é aceita fora de produção (testes). */
    private static function endpoint(string $key): string
    {
        $defaults = [
            'auth_url'  => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
        ];

        return config('env') === 'production' ? $defaults[$key] : (string) config('google.' . $key);
    }

    private static function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $text): string|false
    {
        return base64_decode(strtr($text, '-_', '+/') . str_repeat('=', (4 - strlen($text) % 4) % 4), true);
    }
}
