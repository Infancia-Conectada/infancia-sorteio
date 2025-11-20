<?php
require_once __DIR__ . '/env.php';

/**
 * Inicia sessão com parâmetros de cookie mais seguros.
 *
 * Comportamento principal:
 * - Detecta quando a conexão é HTTPS e, por padrão, habilita Secure nos cookies.
 * - Permite forçar Secure via env FORCE_SECURE_COOKIES=true.
 * - Define HttpOnly para dificultar acesso via JavaScript (proteção contra XSS).
 * - Lê SESSION_SAMESITE do env (Strict|Lax|None). Note que SameSite=None exige
 *   cookies Secure (navegadores atuais rejeitam None sem Secure).
 * - Usa SESSION_NAME para evitar conflito com outras aplicações no mesmo domínio.
 *
 * Observação: alterar o nome da sessão invalidará sessões existentes (logout).
 */
if (!function_exists('start_secure_session')) {
    function start_secure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $cookieDefaults = session_get_cookie_params();
        // Detecta conexão HTTPS de forma simples (podemos melhorar se necessário)
        $httpsDetected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443);

        // Force secure override: permite ao ambiente forçar Secure mesmo se a
        // detecção automática falhar.
        $forceSecure = filter_var(env('FORCE_SECURE_COOKIES', $httpsDetected), FILTER_VALIDATE_BOOLEAN);

        // IMPORTANTE: SESSION_SAMESITE=None exige secure=true. Mantenha isso em mente
        // ao configurar em produção.
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookieDefaults['path'] ?? '/',
            'domain' => $cookieDefaults['domain'] ?? '',
            'secure' => (bool) $forceSecure,
            'httponly' => true,
            'samesite' => env('SESSION_SAMESITE', 'Strict')
        ]);

        // Nome do cookie de sessão — trocar aqui força logout de todas as sessões
        $sessionName = env('SESSION_NAME', 'infancia_session');
        session_name($sessionName);

        session_start();
    }
}
