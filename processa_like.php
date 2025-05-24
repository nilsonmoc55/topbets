<?php
require 'includes/conexao.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

$avaliacao_id = isset($_POST['avaliacao_id']) ? intval($_POST['avaliacao_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$usuario_id = $_SESSION['usuario_id'];

if ($avaliacao_id <= 0 || !in_array($action, ['like', 'unlike'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    $conn->begin_transaction();
    
    if ($action === 'like') {
        // Verifica se já curtiu
        $stmt = $conn->prepare("SELECT id FROM reacoes WHERE avaliacao_id = ? AND usuario_id = ? AND tipo = 'like'");
        $stmt->bind_param("ii", $avaliacao_id, $usuario_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO reacoes (avaliacao_id, usuario_id, tipo) VALUES (?, ?, 'like')");
            $stmt->bind_param("ii", $avaliacao_id, $usuario_id);
            $stmt->execute();
        }
    } else {
        // Remove like
        $stmt = $conn->prepare("DELETE FROM reacoes WHERE avaliacao_id = ? AND usuario_id = ? AND tipo = 'like'");
        $stmt->bind_param("ii", $avaliacao_id, $usuario_id);
        $stmt->execute();
    }
    
    // Conta os likes atualizados
    $stmt = $conn->prepare("SELECT COUNT(*) as likes FROM reacoes WHERE avaliacao_id = ? AND tipo = 'like'");
    $stmt->bind_param("i", $avaliacao_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $likes = $result->fetch_assoc()['likes'];
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'likes' => $likes]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}
?>