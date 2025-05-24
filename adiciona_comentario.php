<?php
require 'includes/conexao.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

$avaliacao_id = isset($_POST['avaliacao_id']) ? intval($_POST['avaliacao_id']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

if ($avaliacao_id <= 0 || empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO comentarios_avaliacoes (avaliacao_id, usuario_id, comentario) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $avaliacao_id, $_SESSION['usuario_id'], $comentario);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar comentário']);
}
?>