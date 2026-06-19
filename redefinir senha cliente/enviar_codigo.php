<?php
require_once('src/PHPMailer.php');
require_once('src/SMTP.php');
require_once('src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// RECEBE DADOS DO FORMULÁRIO
$email = $_POST['email'];
$codigo = rand(100000, 999999);
$expiracao = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// CONEXÃO COM BANCO DE DADOS
$conn = new mysqli("localhost", "root", "", "nexus network");

// INSERE O CÓDIGO NA TABELA reset_codigos_clientes
$stmt = $conn->prepare("INSERT INTO reset_codigos_clientes (cli_email, codigo, expiracao) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $codigo, $expiracao);
$stmt->execute();

// ENVIO DO CÓDIGO POR E-MAIL USANDO PHPMailer
$mail = new PHPMailer(true);

// Limpa códigos expirados antes de verificar
$conn->query("DELETE FROM reset_codigos_clientes WHERE expiracao < NOW()");

$stmt = $conn->prepare("SELECT * FROM reset_codigos_clientes WHERE cli_email = ? AND codigo = ? AND expiracao > NOW()");
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();


try {
    // $mail->SMTPDebug = 2; // Ative para debug

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nexustcc5@gmail.com';
    $mail->Password = 'qeve fysc jqlc etfv'; // Use senha de app do Gmail!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // SSL flexível (útil em localhost)
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    // REMETENTE E DESTINATÁRIO
    $mail->setFrom('nexustcc5@gmail.com', 'Nexus Network - Recuperação de Senha');
    $mail->addAddress($email); // Email do usuário

    // CONTEÚDO DO EMAIL
    $mail->isHTML(true);
    $mail->Subject = "Código de Verificação - Nexus Network";
    $mail->Body    = "<p>Código de Verificação: <strong>$codigo</strong></p><p>Válido apenas por 10 minutos.</p>";
    $mail->AltBody = "Código de Verificação: $codigo. Válido apenas por 10 minutos.";

    $mail->send();
    header("Location: verificar_codigo1.php?email=" . urlencode($email));
    exit();
} catch (Exception $e) {
    echo "Erro ao enviar o e-mail: {$mail->ErrorInfo}";
}