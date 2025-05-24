<?php
session_start();
require_once 'conexao.php';

// Verifica se já está logado
if(isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Processa o formulário de login
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $lembrar = isset($_POST['lembrar']);
    $redirect = $_POST['redirect'] ?? 'index.php';

    // Busca usuário no banco
    $stmt = $conn->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verifica a senha
        if(password_verify($senha, $usuario['senha'])) {
            // Autenticação bem-sucedida
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            // Atualiza último login
            $conn->query("UPDATE usuarios SET ultimo_login = NOW() WHERE id = {$usuario['id']}");
            
            // Redireciona
            header("Location: $redirect");
            exit;
        }
    }

    // Se falhou
    $erro = "Email ou senha incorretos";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TopBets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Login</h2>
            
            <?php if(isset($erro)): ?>
                <div class="alert alert-danger">
                    <?= $erro ?>
                    <br>
                    <a href="recuperar-senha.php">Esqueceu sua senha?</a>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>
                <div class="alert alert-success">
                    Cadastro realizado com sucesso! Faça login para continuar.
                </div>
            <?php endif; ?>

            <form method="POST" action="login-bt-principal.php">
                <input type="hidden" name="redirect" value="<?= $_GET['redirect'] ?? 'index.php' ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar">
                    <label class="form-check-label" for="lembrar">Lembrar de mim</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>Não tem uma conta? <a href="cadastro.php?redirect=<?= urlencode($_GET['redirect'] ?? 'index.php') ?>">Cadastre-se</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Foco no campo de email ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
    </script>
</body>
</html>