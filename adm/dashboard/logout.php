<?php
/**
 * Logout - Encerra sessão
 * Arquivo: /public_html/adm/dashboard/logout.php
 */

session_start();
session_unset();
session_destroy();

// Redireciona para login
header('Location: ../login.php');
exit;
?>