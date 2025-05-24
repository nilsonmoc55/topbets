<?php
require 'includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações
    $erro = null;
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter no mínimo 6 caracteres!";
    } else {
        // Verifica se e-mail já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $erro = "Este e-mail já está cadastrado!";
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Insere no banco (conta já ativa)
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, ativo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $nome, $email, $senha_hash);
            
            if ($stmt->execute()) {
                // Envia e-mail de boas-vindas (sem ativação)
                require 'includes/enviar_email.php';
                enviarEmailBoasVindas($email, $nome);
                
                // Login automático
                session_start();
                $_SESSION['usuario_id'] = $stmt->insert_id;
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_nivel'] = 1; // Nível padrão
                
                header("Location: index.php?cadastro=sucesso");
                exit();
            } else {
                $erro = "Erro ao cadastrar. Tente novamente mais tarde.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php include 'includes/head.php'; ?>
    <title>Cadastre-se - TopBets</title>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i>Criar Conta</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($erro)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Campos do formulário mantidos iguais -->
                            <!-- ... -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>