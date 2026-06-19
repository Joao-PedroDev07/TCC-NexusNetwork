<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Avaliação</title>
    <link rel="stylesheet" href="assests/css/avaliacao.css">
</head>
<body>

<?php
session_start();

// Processar formulário se foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prestador_id'])) {
    
    // Sua conexão existente - ajuste conforme necessário
    // include_once 'config/database.php';
    
    $prestador_id = isset($_POST['prestador_id']) ? intval($_POST['prestador_id']) : 0;
    $nota = isset($_POST['nota']) ? intval($_POST['nota']) : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($prestador_id <= 0) {
        $_SESSION['erro'] = 'ID do prestador inválido';
    } elseif ($nota < 1 || $nota > 5) {
        $_SESSION['erro'] = 'Nota deve ser entre 1 e 5 estrelas';
    } else {
        try {
            $_SESSION['sucesso'] = "Avaliação enviada! Prestador ID: $prestador_id, Nota: $nota, Comentário: $comentario";
            
        } catch(Exception $e) {
            $_SESSION['erro'] = 'Erro ao enviar avaliação';
        }
    }
}

// Exibir mensagens
if (isset($_SESSION['sucesso'])) {
    echo '<div class="alert alert-success">' . $_SESSION['sucesso'] . '</div>';
    unset($_SESSION['sucesso']);
}

if (isset($_SESSION['erro'])) {
    echo '<div class="alert alert-error">' . $_SESSION['erro'] . '</div>';
    unset($_SESSION['erro']);
}
?>

<h1>Sistema de Avaliação por Estrelas</h1>

<!-- Exemplo de prestadores -->
<div class="prestador-item">
    <h4>João Silva - Eletricista</h4>
    <p>Clique nas estrelas para avaliar:</p>
    <div class="rating" data-pres-id="1">
        <span class="star" data-star="1">★</span>
        <span class="star" data-star="2">★</span>
        <span class="star" data-star="3">★</span>
        <span class="star" data-star="4">★</span>
        <span class="star" data-star="5">★</span>
    </div>
</div>

<div class="prestador-item">
    <h4>Maria Santos - Encanadora</h4>
    <p>Clique nas estrelas para avaliar:</p>
    <div class="rating" data-pres-id="2">
        <span class="star" data-star="1">★</span>
        <span class="star" data-star="2">★</span>
        <span class="star" data-star="3">★</span>
        <span class="star" data-star="4">★</span>
        <span class="star" data-star="5">★</span>
    </div>
</div>

<div class="prestador-item">
    <h4>Pedro Lima - Pintor</h4>
    <p>Clique nas estrelas para avaliar:</p>
    <div class="rating" data-pres-id="3">
        <span class="star" data-star="1">★</span>
        <span class="star" data-star="2">★</span>
        <span class="star" data-star="3">★</span>
        <span class="star" data-star="4">★</span>
        <span class="star" data-star="5">★</span>
    </div>
</div>

<!-- Modal de Avaliação -->
<div id="avaliacaoModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Avaliar Prestador</h3>
            <button id="fecharModal" class="close-btn">&times;</button>
        </div>
        
        <form id="formAvaliacao" method="POST">
            <div class="modal-body">
                <div class="rating-section">
                    <label>Sua Avaliação:</label>
                    <div id="modalStars" class="rating">
                        <span class="star" data-star="1">★</span>
                        <span class="star" data-star="2">★</span>
                        <span class="star" data-star="3">★</span>
                        <span class="star" data-star="4">★</span>
                        <span class="star" data-star="5">★</span>
                    </div>
                </div>
                
                <div class="comment-section">
                    <label for="comentario">Comentário (opcional):</label>
                    <textarea id="comentario" name="comentario" rows="4" placeholder="Deixe seu comentário sobre o serviço..."></textarea>
                </div>
                
                <input type="hidden" id="modalPresId" name="prestador_id" value="">
                <input type="hidden" id="notaSelecionada" name="nota" value="0">
            </div>
            
            <div class="modal-footer">
                <button type="button" id="cancelarModal" class="btn-cancelar">Cancelar</button>
                <button type="submit" class="btn-avaliar">Enviar Avaliação</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assests/js/avalicao.js"></script>
<script src="assets/js/header.js"></script>

</body>
</html>