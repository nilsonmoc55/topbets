<?php
require 'includes/conexao.php';

// Inicia a sessão
session_start();

// Limpa erros anteriores
unset($_SESSION['erros_cadastro']);

// Recebe dados do formulário
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$termos = isset($_POST['termos']) ? true : false;

// Validações
$erros = [];

if (empty($nome)) {
    $erros['nome'] = "Por favor, informe seu nome completo";
}

if (empty($email)) {
    $erros['email'] = "Por favor, informe seu e-mail";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros['email'] = "Por favor, informe um e-mail válido";
} else {
    // Verifica se e-mail já existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $erros['email'] = "Este e-mail já está cadastrado!";
    }
}

if (empty($senha)) {
    $erros['senha'] = "Por favor, crie uma senha";
} elseif (strlen($senha) < 6) {
    $erros['senha'] = "A senha deve ter no mínimo 6 caracteres";
}

if ($senha !== $confirmar_senha) {
    $erros['confirmar_senha'] = "As senhas não coincidem";
}

if (!$termos) {
    $erros['termos'] = "Você deve concordar com os termos de uso";
}

// Se não houver erros, processa o cadastro
if (empty($erros)) {
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Insere no banco
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, ativo) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $nome, $email, $senha_hash);
    
    if ($stmt->execute()) {
        // Envia e-mail de boas-vindas
        require 'includes/enviar_email.php';
        enviarEmailBoasVindas($email, $nome);
        
        // Login automático
        $_SESSION['usuario_id'] = $stmt->insert_id;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_nivel'] = 1;
        
        // Redireciona para página anterior
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    } else {
        $erros['geral'] = "Erro ao cadastrar. Tente novamente mais tarde.";
    }
}

// Se houver erros, armazena na sessão e redireciona de volta ao formulário
$_SESSION['erros_cadastro'] = $erros;
$_SESSION['dados_cadastro'] = [
    'nome' => $nome,
    'email' => $email
];

// Força reabertura do modal de cadastro
$_SESSION['abrir_cadastro_modal'] = true;

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();
?>