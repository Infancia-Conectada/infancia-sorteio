<?php
/**
 * Configuração do Dashboard - Conexão e Funções
 * Caminho do .env: /public_html/.env (dois níveis acima)
 */

// Verificação de autenticação
require_once __DIR__ . '/auth.php';

// Função para ler variáveis do .env
function carregarEnv() {
    // Seu .env está em: /public_html/.env
    // Este arquivo está em: /public_html/adm/dashboard/config.php
    // Então o .env está 2 níveis acima
    $caminho = dirname(__DIR__, 2) . '/.env';
    
    if (!file_exists($caminho)) {
        throw new Exception("Arquivo .env não encontrado em: " . $caminho);
    }
    
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        // Ignora comentários
        if (strpos(trim($linha), '#') === 0 || trim($linha) === '') {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($linha, '=') !== false) {
            list($chave, $valor) = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor);
            
            // Remove aspas
            $valor = trim($valor, '"\'');
            
            // Define como constante e variável de ambiente
            if (!defined($chave)) {
                define($chave, $valor);
            }
            $_ENV[$chave] = $valor;
        }
    }
}

// Carrega .env
try {
    carregarEnv();
} catch (Exception $e) {
    die(json_encode([
        'erro' => true,
        'mensagem' => $e->getMessage()
    ]));
}

// Função para conectar ao banco
function conectarBanco() {
    $host = defined('DB_HOST') ? DB_HOST : $_ENV['DB_HOST'];
    $user = defined('DB_USER') ? DB_USER : $_ENV['DB_USER'];
    $pass = defined('DB_PASS') ? DB_PASS : $_ENV['DB_PASS'];
    $name = defined('DB_NAME') ? DB_NAME : $_ENV['DB_NAME'];
    $port = defined('DB_PORT') ? (int)DB_PORT : (int)$_ENV['DB_PORT'];
    
    $conn = new mysqli($host, $user, $pass, $name, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Função principal: buscar dados dos afiliados
function getDadosDashboard() {
    $conn = conectarBanco();
    
    // Query CORRIGIDA: conta apenas participantes que completaram as 4 empresas
    $sql = "
        SELECT 
            a.id,
            a.nome,
            a.email,
            a.telefone,
            a.code,
            COUNT(DISTINCT CASE 
                WHEN p.e1 = 1 AND p.e2 = 1 AND p.e3 = 1 AND p.e4 = 1 
                THEN p.id 
                ELSE NULL 
            END) as total_completos
        FROM afiliados a
        LEFT JOIN participantes p ON p.parametro_unico = a.code
        GROUP BY a.id, a.nome, a.email, a.telefone, a.code
        ORDER BY total_completos DESC, a.nome ASC
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro na query: " . $conn->error);
    }
    
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'email' => $row['email'],
            'telefone' => $row['telefone'],
            'code' => $row['code'],
            'total_completos' => (int)$row['total_completos']
        ];
    }
    
    $conn->close();
    return $dados;
}

// Função para obter estatísticas gerais
function getEstatisticas() {
    $conn = conectarBanco();
    
    // Total de afiliados
    $totalAfiliados = $conn->query("SELECT COUNT(*) as total FROM afiliados")->fetch_assoc()['total'];
    
    // Total de participantes QUE COMPLETARAM as 4 empresas
    $totalCompletos = $conn->query("
        SELECT COUNT(*) as total 
        FROM participantes 
        WHERE e1 = 1 AND e2 = 1 AND e3 = 1 AND e4 = 1
    ")->fetch_assoc()['total'];
    
    // Afiliados ativos (com pelo menos 1 cadastro completo)
    $afiliadosAtivos = $conn->query("
        SELECT COUNT(DISTINCT a.id) as total 
        FROM afiliados a 
        INNER JOIN participantes p ON p.parametro_unico = a.code
        WHERE p.e1 = 1 AND p.e2 = 1 AND p.e3 = 1 AND p.e4 = 1
    ")->fetch_assoc()['total'];
    
    // Total geral de participantes (mesmo incompletos)
    $totalParticipantes = $conn->query("SELECT COUNT(*) as total FROM participantes")->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'total_afiliados' => (int)$totalAfiliados,
        'total_completos' => (int)$totalCompletos,
        'afiliados_ativos' => (int)$afiliadosAtivos,
        'total_participantes' => (int)$totalParticipantes
    ];
}

// Teste de conexão (apenas para debug)
function testarConexao() {
    try {
        $conn = conectarBanco();
        $result = $conn->query("SELECT 1");
        $conn->close();
        return ['sucesso' => true, 'mensagem' => 'Conexão OK'];
    } catch (Exception $e) {
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}
?>