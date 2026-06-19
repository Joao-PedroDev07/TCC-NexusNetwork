<?php
session_start();
include_once("conexao.php");


// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Acesso inválido!</p>";
    header("Location: cad_prestadores.php");
    exit;
}

// Função para sanitizar string (substitui FILTER_SANITIZE_STRING)
function sanitize_string($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}


// Capturar e sanitizar dados
$nome = sanitize_string(filter_input(INPUT_POST, 'nome') ?? '');
$datanasc = sanitize_string(filter_input(INPUT_POST, 'data_nascimento') ?? ''); 
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
$telefone = sanitize_string(filter_input(INPUT_POST, 'telefone') ?? '');
$cpf = sanitize_string(filter_input(INPUT_POST, 'cpf') ?? '');
$genero = sanitize_string(filter_input(INPUT_POST, 'genero') ?? '');
$profissao = sanitize_string(filter_input(INPUT_POST, 'profissao') ?? '');
$cidade = sanitize_string(filter_input(INPUT_POST, 'cidade') ?? '');
$estado = sanitize_string(filter_input(INPUT_POST, 'estado') ?? '');
$senha_raw = filter_input(INPUT_POST, 'senha') ?? '';

// Verificar se todos os campos obrigatórios foram preenchidos
if (empty($nome) || empty($datanasc) || empty($email) || empty($telefone) || 
    empty($cpf) || empty($genero) || empty($profissao) || empty($cidade) || 
    empty($estado) || empty($senha_raw)) {
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Todos os campos são obrigatórios!</p>";
    header("Location: cad_prestadores.php");
    exit;
}

// Validações específicas
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Email inválido!</p>";
    header("Location: cad_prestadores.php");
    exit;
}



// Criptografar a senha
$senha = password_hash($senha_raw, PASSWORD_DEFAULT);

// Verificar se o email já existe
$sql_check = "SELECT pres_email FROM prestadores WHERE pres_email = ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Este email já está cadastrado!</p>";
    header("Location: cad_prestadores.php");
    exit;
}

// Verificar se o CPF já existe
$sql_check_cpf = "SELECT prestador_cpf FROM prestadores WHERE prestador_cpf = ?";
$stmt_check_cpf = mysqli_prepare($conn, $sql_check_cpf);
mysqli_stmt_bind_param($stmt_check_cpf, "s", $cpf);
mysqli_stmt_execute($stmt_check_cpf);
$result_check_cpf = mysqli_stmt_get_result($stmt_check_cpf);

if (mysqli_num_rows($result_check_cpf) > 0) {
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Este CPF já está cadastrado!</p>";
    header("Location: cad_prestadores.php");
    exit;
}

// Processar upload da foto
$caminho_foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto = $_FILES['foto'];
    
    // Verificar tipo de arquivo usando finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $foto['tmp_name']);
    finfo_close($finfo);
    
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime_type, $tipos_permitidos)) {
        $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Apenas arquivos de imagem são permitidos!</p>";
        header("Location: cad_prestadores.php");
        exit;
    }
    
    // Verificar tamanho do arquivo (5MB máximo)
    if ($foto['size'] > 5 * 1024 * 1024) {
        $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Arquivo muito grande! Máximo 5MB.</p>";
        header("Location: cad_prestadores.php");
        exit;
    }
    
    // Criar nome único para a imagem
    $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
    $nome_foto = uniqid() . "_" . time() . "." . $extensao;
    $caminho_foto = "uploads/fotos/" . $nome_foto;

    // Criar a pasta se não existir
    if (!is_dir("uploads/fotos")) {
        mkdir("uploads/fotos", 0755, true);
    }

    // Mover a foto para a pasta
    if (!move_uploaded_file($foto["tmp_name"], $caminho_foto)) {
        $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Erro ao salvar a imagem!</p>";
        header("Location: cad_prestadores.php");
        exit;
    }
}

// Inserir no banco usando prepared statement (Evita SQL Injection)
$sql = "INSERT INTO prestadores (
            pres_nome, pres_datanasc, pres_email, pres_telefone, pres_genero, 
            pres_profissao, pres_estado, pres_cidade, pres_senha, prestador_cpf, 
            pres_foto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssssssssss", 
    $nome, $datanasc, $email, $telefone, $genero, 
    $profissao, $estado, $cidade, $senha, $cpf, $caminho_foto
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['msg'] = "<p style='color:green; font-size:18px'>Cadastro realizado com sucesso!</p>";
    header("Location: index.php");
    exit;
} else {
    // Se houve erro, excluir a foto se foi enviada
    if ($caminho_foto && file_exists($caminho_foto)) {
        unlink($caminho_foto);
    }
    
    $_SESSION['msg'] = "<p style='color:red; font-size:18px'>Erro ao salvar no banco: " . mysqli_error($conn) . "</p>";
    header("Location: cad_prestadores.php");
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>