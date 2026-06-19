<?php
// processar_avaliacao.php
session_start();
require_once 'conexao.php';

// Capturar o código do prestador primeiro
$pres_codigo = isset($_POST['pres_codigo']) ? intval($_POST['pres_codigo']) : 0;

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: info_prestador.php?pres_codigo=" . $pres_codigo);
    exit;
}

// DEBUG: Verificar sessão
error_log("DEBUG - Sessão completa: " . print_r($_SESSION, true));

// Receber e validar dados - PEGAR O CÓDIGO CORRETO DA SESSÃO
$cli_codigo = null;

// Tentar diferentes possíveis nomes de sessão
if (isset($_SESSION['cli_codigo'])) {
    $cli_codigo = $_SESSION['cli_codigo'];
} elseif (isset($_SESSION['cliente_codigo'])) {
    $cli_codigo = $_SESSION['cliente_codigo'];
} elseif (isset($_SESSION['usuario_id'])) {
    $cli_codigo = $_SESSION['usuario_id'];
} elseif (isset($_SESSION['id'])) {
    $cli_codigo = $_SESSION['id'];
}

// Se não encontrou na sessão, tentar buscar o primeiro cliente da base
if ($cli_codigo === null) {
    $result_primeiro = mysqli_query($conn, "SELECT cli_codigo FROM clientes LIMIT 1");
    if ($result_primeiro && $row = mysqli_fetch_assoc($result_primeiro)) {
        $cli_codigo = $row['cli_codigo'];
        error_log("DEBUG - Usando primeiro cliente da base: " . $cli_codigo);
    } else {
        $_SESSION['mensagem_erro'] = "Erro: Nenhum cliente encontrado no sistema.";
        header("Location: info_prestador.php?pres_codigo=" . $pres_codigo);
        exit;
    }
}

$avaliacao = isset($_POST['avaliacao']) ? intval($_POST['avaliacao']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

error_log("DEBUG - cli_codigo usado: " . $cli_codigo);

// Validações
$erros = [];

if ($pres_codigo <= 0) {
    $erros[] = "Prestador inválido.";
}

if ($avaliacao < 1 || $avaliacao > 5) {
    $erros[] = "Avaliação deve ser entre 1 e 5 estrelas.";
}

if (empty($comentario)) {
    $erros[] = "O comentário não pode estar vazio.";
}

if (strlen($comentario) > 500) {
    $erros[] = "O comentário não pode ter mais de 500 caracteres.";
}

// Verificar se o prestador existe
if (empty($erros) && $pres_codigo > 0) {
    $stmt_check = mysqli_prepare($conn, "SELECT pres_codigo FROM prestadores WHERE pres_codigo = ?");
    mysqli_stmt_bind_param($stmt_check, "i", $pres_codigo);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        $erros[] = "Prestador não encontrado.";
    }
    mysqli_stmt_close($stmt_check);
}

// Verificar se o cliente já avaliou este prestador
if (empty($erros)) {
    $stmt_check_existing = mysqli_prepare($conn, "SELECT avl_codigo FROM avaliacao WHERE cli_codigo = ? AND pres_codigo = ?");
    mysqli_stmt_bind_param($stmt_check_existing, "ii", $cli_codigo, $pres_codigo);
    mysqli_stmt_execute($stmt_check_existing);
    $result_existing = mysqli_stmt_get_result($stmt_check_existing);
    
    if (mysqli_num_rows($result_existing) > 0) {
        // Já existe avaliação - NÃO PERMITIR
        mysqli_stmt_close($stmt_check_existing);
        $_SESSION['mensagem_erro'] = "Você já avaliou este prestador! Para alterar, exclua sua avaliação atual primeiro.";
    } else {
        // Não existe, vamos inserir
        mysqli_stmt_close($stmt_check_existing);
        
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO avaliacao (cli_codigo, pres_codigo, avl_nota, avl_comentario, avl_data) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_insert, "iiis", $cli_codigo, $pres_codigo, $avaliacao, $comentario);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['mensagem_sucesso'] = "Avaliação adicionada com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Erro ao adicionar avaliação: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_insert);
    }
}

// Se houver erros, mostrar e voltar
if (!empty($erros)) {
    $_SESSION['mensagem_erro'] = implode("<br>", $erros);
}

mysqli_close($conn);

// Redirecionar de volta para o perfil do prestador
header("Location: info_prestador.php?pres_codigo=" . $pres_codigo);
exit;
?>