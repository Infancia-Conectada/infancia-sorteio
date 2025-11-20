<?php
/**
 * Script de Verificação de Autenticação do Painel
 * Verifica se o usuário está autenticado via sessão PHP
 */

// Headers anti-cache para evitar acesso após logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once __DIR__ . '/../../config/session.php';
start_secure_session();

// Verificar se a sessão existe e é válida
if (!isset($_SESSION['id_afiliado']) || 
    !isset($_SESSION['nome_afiliado']) || 
    !isset($_SESSION['code_afiliado']) ||
    empty($_SESSION['id_afiliado'])) {
    
    // Limpar qualquer resquício de sessão
    session_unset();
    session_destroy();
    
    // Não autenticado - redirecionar para login
    header('Location: /afiliados/');
    exit;
}

// Se chegou aqui, está autenticado - nada a fazer, a página carrega normalmente
