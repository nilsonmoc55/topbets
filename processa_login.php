<?php
require_once 'includes/conexao.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email_login', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha_login'];
    
    $stmt = $conn->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];
            
            echo json_encode(['success' => true]);
            exit();
        }
    }
    
    // Se chegou aqui, login falhou
    echo json_encode([
        'success' => false,
        'errors' => [
            'email' => 'E-mail ou senha incorretos',
            'senha' => 'E-mail ou senha incorretos'
        ],
        'message' => 'Credenciais inválidas'
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Método não permitido'
]);
exit();
?>