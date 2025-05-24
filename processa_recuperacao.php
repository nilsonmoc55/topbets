<?php
require_once 'includes/conexao.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email_recuperacao', FILTER_SANITIZE_EMAIL);
    
    // Verifica se o e-mail existe
    $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Gera token e data de expiração (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Atualiza no banco
        $stmt = $conn->prepare("UPDATE usuarios SET token_recuperacao = ?, token_validade = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiracao, $email);
        $stmt->execute();
        
        // Envia o e-mail de recuperação
        $assunto = "Recuperação de Senha - TopBets";
        $link = "https://seusite.com/redefinir_senha.php?token=$token";
        
        $mensagem = "
        <html>
        <head>
            <title>Recuperação de Senha</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .btn { background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Olá, {$usuario['nome']}!</h2>
                <p>Recebemos uma solicitação para redefinir sua senha no TopBets.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <p><a href='$link' class='btn'>Redefinir Senha</a></p>
                <p>Se você não solicitou esta alteração, ignore este e-mail.</p>
                <div class='footer'>
                    <p>Este link expira em 1 hora.</p>
                    <p>Equipe TopBets</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Cabeçalhos do e-mail
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: TopBets <no-reply@seusite.com>\r\n";
        $headers .= "Reply-To: suporte@seusite.com\r\n";
        
        // Envia o e-mail
        $enviado = mail($email, $assunto, $mensagem, $headers);
        
        if ($enviado) {
            echo json_encode(['success' => true, 'message' => 'E-mail de recuperação enviado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao enviar e-mail. Tente novamente.']);
        }
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'E-mail não encontrado em nosso sistema.']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
exit();