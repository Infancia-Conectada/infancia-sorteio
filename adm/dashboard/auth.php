<?php
/**
 * Verificação de Autenticação
 * Inclua este arquivo no topo de cada página do dashboard
 * Arquivo: /public_html/adm/dashboard/auth.php
 */

session_start();

// Verifica se está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    // Não está logado, redireciona para login
    header('Location: ../login.php');
    exit;
}

// Verifica timeout de sessão (opcional: 2 horas)
$timeout = 7200; // 2 horas em segundos
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time'] > $timeout)) {
    // Sessão expirada
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Atualiza último acesso
$_SESSION['admin_login_time'] = time();
?>