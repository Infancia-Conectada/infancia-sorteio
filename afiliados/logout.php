<?php
/**
 * Script de Logout
 * Destrói a sessão e redireciona para a página de login
 */

require_once __DIR__ . '/../config/session.php';
start_secure_session();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login com headers anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Location: /afiliados/');
exit;
