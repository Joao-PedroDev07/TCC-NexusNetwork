<?php
// debug_connection.php - Debug detalhado da conexão

header('Content-Type: application/json');

$debug_info = [];

try {
    $debug_info['php_version'] = phpversion();
    $debug_info['mysqli_available'] = extension_loaded('mysqli');
    $debug_info['pdo_available'] = extension_loaded('pdo');
    $debug_info['pdo_mysql_available'] = extension_loaded('pdo_mysql');
    
    // Teste 1: Conectar sem banco específico
    $conn_test = mysqli_connect("localhost", "root", "");
    if ($conn_test) {
        $debug_info['basic_connection'] = 'OK';
        
        // Listar bancos
        $result = mysqli_query($conn_test, "SHOW DATABASES");
        $databases = [];
        while ($row = mysqli_fetch_array($result)) {
            $databases[] = $row[0];
        }
        $debug_info['databases'] = $databases;
        
        // Teste 2: Conectar ao banco específico
        if (in_array('nexus network', $databases)) {
            mysqli_close($conn_test);
            $conn_nexus = mysqli_connect("localhost", "root", "", "nexus network");
            if ($conn_nexus) {
                $debug_info['nexus_connection'] = 'OK';
                
                // Testar consulta
                $result = mysqli_query($conn_nexus, "SHOW TABLES");
                $tables = [];
                while ($row = mysqli_fetch_array($result)) {
                    $tables[] = $row[0];
                }
                $debug_info['tables'] = $tables;
                mysqli_close($conn_nexus);
            } else {
                $debug_info['nexus_connection'] = 'ERRO: ' . mysqli_connect_error();
            }
        } else {
            $debug_info['nexus_connection'] = 'Banco não encontrado';
        }
        
    } else {
        $debug_info['basic_connection'] = 'ERRO: ' . mysqli_connect_error();
    }
    
} catch (Exception $e) {
    $debug_info['exception'] = $e->getMessage();
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>