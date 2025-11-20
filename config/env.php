<?php
/**
 * Lightweight environment loader
 *
 * Purpose:
 * - Load simple key=value `.env` files into getenv()/$_ENV/$_SERVER when present.
 * - Provide `env($key, $default)` helper which checks in this order:
 *     1) $_ENV
 *     2) $_SERVER
 *     3) getenv()
 *   and returns a fallback default when the value is empty.
 *
 * Notes:
 * - The loader intentionally avoids overwriting existing server/env entries.
 * - Prefer to set real environment variables in the host/panel (getenv) for production.
 */

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        $needleLength = strlen($needle);
        if ($needleLength === 0) {
            return true;
        }
        return substr($haystack, -$needleLength) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('loadEnvFile')) {
    /**
     * Carrega variáveis do arquivo .env para getenv/$_ENV/$_SERVER
     *
     * Behavior:
     * - Skips blank lines and lines starting with '#'
     * - Accepts values wrapped in single/double quotes
     * - Does not overwrite existing keys in $_SERVER or $_ENV
     *
     * Security note: keep .env fora do webroot (ou negue leitura via webserver) —
     * este arquivo pode conter segredos (DB_PASS etc.).
     */
    function loadEnvFile(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Só define quando a variável não existir em $_SERVER/$_ENV para
            // evitar sobrescrever valores já configurados no ambiente do host.
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Obtém variável de ambiente com fallback
     *
     * Order of resolution:
     *  - $_ENV
     *  - $_SERVER
     *  - getenv()
     *
     * Returns $default when value is empty (null/false/empty string).
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!defined('ENV_FILE_LOADED')) {
    define('ENV_FILE_LOADED', true);
    // Carrega .env localizado na raiz do projeto por padrão.
    // Em produção prefira definir variáveis de ambiente reais (getenv) no painel
    // do provedor para evitar manter segredos em arquivos dentro do servidor.
    $rootPath = dirname(__DIR__);
    loadEnvFile($rootPath . '/.env');
}
