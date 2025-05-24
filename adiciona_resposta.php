<?php
require 'includes/conexao.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

$comentario_id = isset($_POST['comentario_id']) ? intval($_POST['comentario_id']) : 0;
$avaliacao_id = isset($_POST['avaliacao_id']) ? intval($_POST['avaliacao_id']) : 0;
$resposta = isset($_POST['resposta']) ? trim($_POST['resposta']) : '';

if ($comentario_id <= 0 || $avaliacao_id <= 0 || empty($resposta)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO respostas_comentarios (comentario_id, avaliacao_id, usuario_id, comentario) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $comentario_id, $avaliacao_id, $_SESSION['usuario_id'], $resposta);
    $stmt->execute();
    
    // Obter dados do usuário para a resposta
    $resposta_id = $stmt->insert_id;
    $sql = "SELECT r.*, u.nome as usuario_nome, u.avatar as usuario_avatar 
            FROM respostas_comentarios r
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resposta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $nova_resposta = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'html' => obterHtmlResposta($nova_resposta, 1, $avaliacao_id)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar resposta: '.$e->getMessage()]);
}

function obterHtmlResposta($resposta, $nivel, $avaliacao_id) {
    ob_start();
    exibirComentario($resposta, $nivel, $avaliacao_id);
    return ob_get_clean();
}

function exibirComentario($comentario, $nivel, $avaliacao_id) {
    $margin = $nivel * 20;
    $border = $nivel > 0 ? 'border-start: 3px solid #dee2e6;' : '';
    
    echo '<div class="comentario mb-2" style="margin-left: '.$margin.'px; '.$border.' padding-left: 10px;" id="comentario-'.$comentario['id'].'">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<img src="'.htmlspecialchars($comentario['usuario_avatar'] ?? 'img/avatars/default.png').'" class="avatar me-2">';
    echo '<strong>'.htmlspecialchars($comentario['usuario_nome'] ?? 'Anônimo').'</strong>';
    echo '<small class="text-muted ms-2">'.date('d/m/Y H:i', strtotime($comentario['data_criacao'])).'</small>';
    echo '</div>';
    echo '<p class="mb-2">'.nl2br(htmlspecialchars($comentario['comentario'])).'</p>';
    
    // Botões de ação
    echo '<div class="d-flex">';
    echo '<button class="btn btn-sm btn-outline-secondary me-2 responder-btn" data-comentario-id="'.$comentario['id'].'">';
    echo '<i class="fas fa-reply me-1"></i> Responder';
    echo '</button>';
    echo '</div>';
    
    // Formulário de resposta (inicialmente oculto)
    echo '<form class="resposta-form mt-2 mb-3" data-comentario-id="'.$comentario['id'].'" style="display: none;">';
    echo '<input type="hidden" name="avaliacao_id" value="'.$avaliacao_id.'">';
    echo '<div class="input-group">';
    echo '<input type="text" class="form-control" name="resposta" placeholder="Escreva sua resposta..." required>';
    echo '<button class="btn btn-primary" type="submit">Enviar</button>';
    echo '</div>';
    echo '</form>';
    
    echo '</div>';
}
?>