<?php

// Função para sanitizar string (limpar dados)
function sanitize_string($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Buscar dados completos do prestador
function buscarDadosPrestador($conn, $pres_codigo) {
    $sql = "SELECT pres_codigo, pres_nome, pres_datanasc, pres_email, pres_telefone, pres_genero, 
                    pres_profissao, pres_estado, pres_cidade, pres_senha, prestador_cpf, 
                    pres_foto, pres_latitude, pres_longitude, pres_descricao, pres_precomin, pres_precomax,
                    pres_data_cadastro
            FROM prestadores 
            WHERE pres_codigo = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row;
    }
    
    return false;
}


// Atualizar dados do prestador (exceto email, CPF e data nascimento)
function atualizarDadosPrestador($conn, $pres_codigo, $dados) {
    $sql = "UPDATE prestadores SET 
                pres_nome = ?, 
                pres_telefone = ?, 
                pres_genero = ?, 
                pres_estado = ?, 
                pres_cidade = ?,
                pres_profissao = ?,
                pres_descricao = ?,
                pres_precomin = ?,
                pres_precomax = ?
            WHERE pres_codigo = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssddi", 
        $dados['nome'], 
        $dados['telefone'], 
        $dados['genero'], 
        $dados['estado'], 
        $dados['cidade'],
        $dados['profissao'], 
        $dados['descricao'],
        $dados['preco_min'], 
        $dados['preco_max'],
        $pres_codigo
    );
    
    return mysqli_stmt_execute($stmt);
}

// Validar telefone
function validarTelefone($telefone) {
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    
    // Verifica se tem 10 ou 11 dígitos
    if (strlen($telefone_limpo) < 10 || strlen($telefone_limpo) > 11) {
        return false;
    }
    
    // Se tem 11 dígitos, o terceiro deve ser 9 (celular)
    if (strlen($telefone_limpo) == 11) {
        if ($telefone_limpo[2] != '9') {
            return false;
        }
    }
    
    // Verifica se não são todos os dígitos iguais
    if (preg_match('/(\d)\1{9,10}/', $telefone_limpo)) {
        return false;
    }
    
    return true;
}

// Processar upload de foto
function processarFoto($arquivo_foto) {
    if (!isset($arquivo_foto) || $arquivo_foto['error'] != 0) {
        return ['erro' => 'Erro no upload da imagem!'];
    }
    
    $foto = $arquivo_foto;
    
    // Verificar tipo de arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $foto['tmp_name']);
    finfo_close($finfo);
    
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime_type, $tipos_permitidos)) {
        return ['erro' => 'Apenas arquivos de imagem são permitidos (JPG, PNG, GIF, WEBP)!'];
    }
    
    // Verificar tamanho (5MB máximo)
    if ($foto['size'] > 5 * 1024 * 1024) {
        return ['erro' => 'Arquivo muito grande! Máximo 5MB.'];
    }
    
    // Verificar dimensões da imagem
    $info_imagem = getimagesize($foto['tmp_name']);
    if ($info_imagem === false) {
        return ['erro' => 'Arquivo de imagem inválido!'];
    }
    
    // Limitar dimensões (opcional)
    if ($info_imagem[0] > 2000 || $info_imagem[1] > 2000) {
        return ['erro' => 'Imagem muito grande! Máximo 2000x2000 pixels.'];
    }
    
    // Criar nome único
    $extensao = pathinfo($foto['name'], PATHINFO_EXTENSION);
    $nome_foto = uniqid() . "_" . time() . "." . $extensao;
    $caminho_foto = "uploads/fotos/" . $nome_foto;
    
    // Criar pasta se não existir
    if (!is_dir("uploads/fotos")) {
        mkdir("uploads/fotos", 0755, true);
    }
    
    // Mover arquivo
    if (!move_uploaded_file($foto["tmp_name"], $caminho_foto)) {
        return ['erro' => 'Erro ao salvar a imagem no servidor!'];
    }
    
    return ['sucesso' => $caminho_foto];
}

// Atualizar foto do prestador
function atualizarFotoPrestador($conn, $pres_codigo, $caminho_foto) {
    $sql = "UPDATE prestadores SET pres_foto = ? WHERE pres_codigo = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $caminho_foto, $pres_codigo);
    
    return mysqli_stmt_execute($stmt);
}

// Excluir conta do prestador
function excluirContaPrestador($conn, $pres_codigo) {
    // Iniciar transação
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Excluir mensagens relacionadas aos chats do prestador
        $sql_mensagens = "DELETE FROM mensagens 
                         WHERE chat_codigo IN (
                             SELECT chat_codigo FROM chat WHERE pres_codigo = ?
                         )";
        $stmt = mysqli_prepare($conn, $sql_mensagens);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 2. Excluir chats do prestador
        $sql_chat = "DELETE FROM chat WHERE pres_codigo = ?";
        $stmt = mysqli_prepare($conn, $sql_chat);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 3. Excluir avaliações do prestador
        $sql_avaliacoes = "DELETE FROM avaliacao WHERE pres_codigo = ?";
        $stmt = mysqli_prepare($conn, $sql_avaliacoes);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 4. Excluir favoritos do prestador
        $sql_favoritos = "DELETE FROM favoritos WHERE pres_codigo = ?";
        $stmt = mysqli_prepare($conn, $sql_favoritos);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 5. Excluir visualizações do prestador
        $sql_visualizacoes = "DELETE FROM visualizacoes WHERE pres_codigo = ?";
        $stmt = mysqli_prepare($conn, $sql_visualizacoes);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 6. Excluir códigos de reset se existirem
        $sql_reset = "DELETE FROM reset_codigos 
                     WHERE pres_email = (
                         SELECT pres_email FROM prestadores WHERE pres_codigo = ?
                     )";
        $stmt = mysqli_prepare($conn, $sql_reset);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // 7. Finalmente, excluir o prestador
        $sql_prestador = "DELETE FROM prestadores WHERE pres_codigo = ?";
        $stmt = mysqli_prepare($conn, $sql_prestador);
        mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Erro ao excluir prestador");
        }
        mysqli_stmt_close($stmt);
        
        // Commit da transação
        mysqli_commit($conn);
        return true;
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        mysqli_rollback($conn);
        error_log("Erro ao excluir conta do prestador: " . $e->getMessage());
        return false;
    }
}

// Verificar se email já existe (para outros prestadores)
function emailJaExisteOutroPrestador($conn, $email, $pres_codigo_atual) {
    $sql = "SELECT pres_email FROM prestadores WHERE pres_email = ? AND pres_codigo != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $email, $pres_codigo_atual);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

// Verificar se CPF já existe (para outros prestadores)  
function cpfJaExisteOutroPrestador($conn, $cpf, $pres_codigo_atual) {
    $cpf_limpo = preg_replace('/\D/', '', $cpf);
    
    $sql = "SELECT prestador_cpf FROM prestadores WHERE prestador_cpf = ? AND pres_codigo != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $cpf_limpo, $pres_codigo_atual);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

// Validar CPF
function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validação do primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    
    if ($cpf[9] != $digito1) {
        return false;
    }
    
    // Validação do segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    
    if ($cpf[10] != $digito2) {
        return false;
    }
    
    return true;
}

// Formatar telefone para exibição
function formatarTelefone($telefone) {
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    
    if (strlen($telefone_limpo) == 11) {
        // Celular: (11) 99999-9999
        return '(' . substr($telefone_limpo, 0, 2) . ') ' .
               substr($telefone_limpo, 2, 5) . '-' .
               substr($telefone_limpo, 7, 4);
    } elseif (strlen($telefone_limpo) == 10) {
        // Fixo: (11) 9999-9999
        return '(' . substr($telefone_limpo, 0, 2) . ') ' .
               substr($telefone_limpo, 2, 4) . '-' .
               substr($telefone_limpo, 6, 4);
    }
    
    return $telefone;
}

// Formatar CPF para exibição
function formatarCPF($cpf) {
    $cpf_limpo = preg_replace('/\D/', '', $cpf);
    
    if (strlen($cpf_limpo) == 11) {
        return substr($cpf_limpo, 0, 3) . '.' .
               substr($cpf_limpo, 3, 3) . '.' .
               substr($cpf_limpo, 6, 3) . '-' .
               substr($cpf_limpo, 9, 2);
    }
    
    return $cpf;
}

// Atualizar localização do prestador (latitude e longitude)
function atualizarLocalizacaoPrestador($conn, $pres_codigo, $latitude, $longitude) {
    $sql = "UPDATE prestadores SET pres_latitude = ?, pres_longitude = ? WHERE pres_codigo = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ddi", $latitude, $longitude, $pres_codigo);
    
    return mysqli_stmt_execute($stmt);
}

// Buscar avaliações do prestador
function buscarAvaliacoesPrestador($conn, $pres_codigo) {
    $sql = "SELECT a.avl_codigo, a.avl_data, a.avl_nota, a.avl_comentario,
                   c.cli_nome, c.cli_foto_url
            FROM avaliacao a
            INNER JOIN clientes c ON a.cli_codigo = c.cli_codigo
            WHERE a.pres_codigo = ?
            ORDER BY a.avl_data DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $avaliacoes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $avaliacoes[] = $row;
    }
    
    return $avaliacoes;
}

// Calcular média de avaliações do prestador
function calcularMediaAvaliacoes($conn, $pres_codigo) {
    $sql = "SELECT AVG(avl_nota) as media, COUNT(*) as total
            FROM avaliacao
            WHERE pres_codigo = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $pres_codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return [
            'media' => round($row['media'], 1),
            'total' => $row['total']
        ];
    }
    
    return ['media' => 0, 'total' => 0];
}

?>