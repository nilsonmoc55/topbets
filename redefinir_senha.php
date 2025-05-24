<?php
require_once 'includes/conexao.php';

session_start();

$token = $_GET['token'] ?? '';
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if ($senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem!";
        $tipoMensagem = "danger";
    } else {
        // Verifica se o token é válido
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_validade > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Atualiza a senha e limpa o token
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_validade = NULL WHERE id = ?");
            $stmt->bind_param("si", $senha_hash, $usuario['id']);
            $stmt->execute();
            
            $mensagem = "Senha redefinida com sucesso! Você já pode fazer login.";
            $tipoMensagem = "success";
        } else {
            $mensagem = "Token inválido ou expirado. Solicite um novo link de recuperação.";
            $tipoMensagem = "danger";
        }
    }
} else {
    // Verifica se o token é válido (apenas para exibição do formulário)
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_validade > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $mensagem = "Token inválido ou expirado. Solicite um novo link de recuperação.";
        $tipoMensagem = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - TopBets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Redefinir Senha</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensagem): ?>
                            <div class="alert alert-<?= $tipoMensagem ?>"><?= $mensagem ?></div>
                            <?php if ($tipoMensagem === 'success'): ?>
                                <a href="login.php" class="btn btn-primary">Ir para Login</a>
                            <?php endif; ?>
                        <?php elseif ($token && empty($mensagem)): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                                </div>
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Redefinir Senha</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">Token inválido ou expirado.</div>
                            <a href="recuperar_senha.php" class="btn btn-primary">Solicitar Novo Link</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>