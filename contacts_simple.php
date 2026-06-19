<?php
// api/contacts_simple.php - Versão simplificada para teste

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Desabilitar exibição de erros na saída
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

try {
    // Verificar se usuário está logado
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario nao autenticado']);
        exit;
    }

    // Conectar ao banco
    $host = '127.0.0.1';
    $dbname = 'nexus network';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=`$dbname`;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = $_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['tipo_usuario'];

    // Retornar dados simples por enquanto
    if ($tipo_usuario === 'cliente') {
        // Buscar todos os prestadores para teste
        $sql = "SELECT pres_codigo as id, pres_nome as name, pres_profissao as profession 
                FROM prestadores LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Buscar todos os clientes para teste
        $sql = "SELECT cli_codigo as id, cli_nome as name, 'Cliente' as profession 
                FROM clientes LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar campos padrão
    foreach ($contacts as &$contact) {
        $contact['avatar'] = 'assets/img/default-avatar.jpg';
        $contact['online'] = false;
        $contact['lastMessage'] = 'Teste de mensagem';
        $contact['lastMessageTime'] = '5 min';
        $contact['type'] = $tipo_usuario === 'cliente' ? 'prestador' : 'cliente';
    }

    echo json_encode($contacts);

} catch (Exception $e) {
    error_log("Erro em contacts_simple: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor', 'details' => $e->getMessage()]);
}
?>