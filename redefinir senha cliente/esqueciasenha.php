<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="../assets/css/esqueciasenha.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1>Esqueceu sua senha?</h1>
            <p class="subtitle">Sem problemas! Digite seu e-mail e enviaremos um código para redefinir sua senha.</p>
        </div>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo '<div class="error-message">E-mail inválido.</div>';
            } else if (preg_match('/^\d+@/', $email)) {
                echo '<div class="error-message">Endereço de e-mail suspeito. Informe um e-mail real.</div>';
            } else {
                $dominio = substr(strrchr($email, "@"), 1);
                $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com'];
                
                if (!in_array($dominio, $dominiosPermitidos)) {
                    echo '<div class="error-message">Domínio de e-mail não aceito.</div>';
                } else if (!getmxrr($dominio, $mxhosts)) {
                    echo '<div class="error-message">Domínio de e-mail inválido.</div>';
                } else {
                    echo '<div class="success-message">E-mail válido! Enviando código para: ' . htmlspecialchars($email) . '</div>';
                }
            }
        }
        ?>

        <form action="enviar_codigo.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="seu@email.com">
            </div>
            <button type="submit">Enviar Código</button>
        </form>

        <div class="back-link">
            <a href="../login.php">← Voltar</a>
        </div>
    </div>
</body>
</html>