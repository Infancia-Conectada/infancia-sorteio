<?php
/**
 * API Endpoint - Retorna dados em JSON
 * Endpoint: api.php?acao=completo
 */

// Verificação de autenticação
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Inclui arquivo de configuração
require_once __DIR__ . '/config.php';

// Log de requisição (para debug)
error_log("[API] Requisição recebida: " . ($_GET['acao'] ?? 'nenhuma'));

try {
    $acao = $_GET['acao'] ?? 'completo';
    
    switch ($acao) {
        case 'dados':
            // Retorna apenas lista de afiliados
            $dados = getDadosDashboard();
            echo json_encode([
                'sucesso' => true,
                'dados' => $dados,
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'estatisticas':
            // Retorna apenas estatísticas
            $stats = getEstatisticas();
            echo json_encode([
                'sucesso' => true,
                'estatisticas' => $stats,
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'completo':
            // Retorna tudo de uma vez (padrão)
            $dados = getDadosDashboard();
            $stats = getEstatisticas();
            echo json_encode([
                'sucesso' => true,
                'dados' => $dados,
                'estatisticas' => $stats,
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'teste':
            // Testa conexão
            $teste = testarConexao();
            echo json_encode([
                'sucesso' => $teste['sucesso'],
                'mensagem' => $teste['mensagem'],
                'timestamp' => time()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Ação inválida: ' . $acao);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("[API] Erro: " . $e->getMessage());
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage(),
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
}
?>