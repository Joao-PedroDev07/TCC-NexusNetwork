    <?php
session_start();
include "conexao.php";

$chat = intval($_POST['chat']);
$mensagem = trim($_POST['mensagem']);
$remetente = "cliente"; // ou "prestador", dependendo de quem está logado

if ($mensagem !== "") {
    $sql = "INSERT INTO mensagens (chat_codigo, remetente, mensagem) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $chat, $remetente, $mensagem);
    $stmt->execute();
}
?>