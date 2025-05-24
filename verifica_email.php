<?php
require_once 'includes/conexao.php';

header('Content-Type: application/json');

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    echo json_encode([
        'exists' => $stmt->num_rows > 0
    ]);
    
    $stmt->close();
} else {
    echo json_encode([
        'error' => 'E-mail não fornecido'
    ]);
}