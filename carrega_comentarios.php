<?php
require 'includes/conexao.php';

$avaliacao_id = isset($_GET['avaliacao_id']) ? intval($_GET['avaliacao_id']) : 0;

if ($avaliacao_id <= 0) {
    echo '<div class="alert alert-danger">ID inválido</div>';
    exit();
}

// Busca comentários da avaliação (incluindo respostas)
$sql = "SELECT c.*, u.nome as usuario_nome, u.avatar as usuario_avatar 
        FROM comentarios_avaliacoes c
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.avaliacao_id = ? 
        ORDER BY c.data_criacao ASC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo '<div class="alert alert-danger">Erro na preparação da consulta: '.$conn->error.'</div>';
    exit();
}

$stmt->bind_param("i", $avaliacao_id);
$stmt->execute();
$result = $stmt->get_result();

$comentarios = [];
while ($row = $result->fetch_assoc()) {
    $comentarios[$row['id']] = $row;
    $comentarios[$row['id']]['respostas'] = [];
}

// Busca respostas para os comentários
$sql_respostas = "SELECT r.*, u.nome as usuario_nome, u.avatar as usuario_avatar 
                 FROM respostas_comentarios r
                 LEFT JOIN usuarios u ON r.usuario_id = u.id
                 WHERE r.comentario_id IN (SELECT id FROM comentarios_avaliacoes WHERE avaliacao_id = ?)
                 ORDER BY r.data_criacao ASC";
$stmt_respostas = $conn->prepare($sql_respostas);
$stmt_respostas->bind_param("i", $avaliacao_id);
$stmt_respostas->execute();
$respostas = $stmt_respostas->get_result();

while ($resposta = $respostas->fetch_assoc()) {
    if (isset($comentarios[$resposta['comentario_id']])) {
        $comentarios[$resposta['comentario_id']]['respostas'][] = $resposta;
    }
}

function exibirComentario($comentario, $nivel = 0, $avaliacao_id) {
    $usuarioLogado = isset($_SESSION['usuario_id']);
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
    if ($usuarioLogado) {
        echo '<button class="btn btn-sm btn-outline-secondary me-2 responder-btn" data-comentario-id="'.$comentario['id'].'">';
        echo '<i class="fas fa-reply me-1"></i> Responder';
        echo '</button>';
    }
    echo '</div>';
    
    // Formulário de resposta (inicialmente oculto)
    if ($usuarioLogado) {
        echo '<form class="resposta-form mt-2 mb-3" data-comentario-id="'.$comentario['id'].'" style="display: none;">';
        echo '<input type="hidden" name="avaliacao_id" value="'.$avaliacao_id.'">';
        echo '<div class="input-group">';
        echo '<input type="text" class="form-control" name="resposta" placeholder="Escreva sua resposta..." required>';
        echo '<button class="btn btn-primary" type="submit">Enviar</button>';
        echo '</div>';
        echo '</form>';
    }
    
    // Exibir respostas
    if (!empty($comentario['respostas'])) {
        foreach ($comentario['respostas'] as $resposta) {
            exibirComentario($resposta, $nivel + 1, $avaliacao_id);
        }
    }
    
    echo '</div>';
}

if (!empty($comentarios)) {
    foreach ($comentarios as $comentario) {
        if (empty($comentario['comentario_id'])) { // Mostra apenas comentários principais (não são respostas)
            exibirComentario($comentario, 0, $avaliacao_id);
        }
    }
} else {
    echo '<div class="alert alert-info">Nenhum comentário ainda. Seja o primeiro a comentar!</div>';
}
?>