<?php
require 'includes/conexao.php';
session_start();

header('Content-Type: text/html; charset=UTF-8');

$avaliacao_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($avaliacao_id <= 0) {
    die('<div class="alert alert-danger">Avaliação inválida</div>');
}

// Busca os dados da avaliação com verificação de bet_id
$sql_avaliacao = "SELECT a.*, u.nome as usuario_nome, u.avatar as usuario_avatar,
                 b.nome as bet_nome, b.logo as bet_logo, b.id as bet_id,
                 (SELECT COUNT(*) FROM reacoes WHERE avaliacao_id = a.id) as total_likes,
                 (SELECT COUNT(*) FROM comentarios_avaliacoes WHERE avaliacao_id = a.id) as total_comentarios
                 FROM avaliacoes a
                 LEFT JOIN usuarios u ON a.usuario_id = u.id
                 LEFT JOIN bets b ON a.bet_id = b.id
                 WHERE a.id = ?";
$stmt_avaliacao = $conn->prepare($sql_avaliacao);
$stmt_avaliacao->bind_param("i", $avaliacao_id);
$stmt_avaliacao->execute();
$result_avaliacao = $stmt_avaliacao->get_result();
$avaliacao = $result_avaliacao->fetch_assoc();

if (!$avaliacao) {
    die('<div class="alert alert-danger">Avaliação não encontrada</div>');
}

// Busca comentários apenas para esta avaliação
$sql_comentarios = "SELECT c.*, u.nome as usuario_nome, u.avatar as usuario_avatar
                   FROM comentarios_avaliacoes c
                   LEFT JOIN usuarios u ON c.usuario_id = u.id
                   WHERE c.avaliacao_id = ?
                   ORDER BY 
                       CASE WHEN c.comentario_pai_id IS NULL THEN c.id ELSE c.comentario_pai_id END,
                       c.data_criacao ASC";

$stmt_comentarios = $conn->prepare($sql_comentarios);
$stmt_comentarios->bind_param("i", $avaliacao_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result();

$usuarioLogado = isset($_SESSION['usuario_id']);
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Função auxiliar para verificar se usuário curtiu
function usuarioCurtiu($avaliacao_id, $usuario_id, $conn) {
    if (!$usuario_id) return false;
    
    $stmt = $conn->prepare("SELECT id FROM reacoes WHERE avaliacao_id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $avaliacao_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>

<div class="avaliacao-detalhes">
    <!-- Cabeçalho da avaliação -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div class="d-flex align-items-center">
            <img src="<?= htmlspecialchars($avaliacao['bet_logo'] ?? 'img/logos/default.png') ?>" 
                 class="rounded me-3" width="80" alt="<?= htmlspecialchars($avaliacao['bet_nome']) ?>">
            <div>
                <h4><?= htmlspecialchars($avaliacao['bet_nome']) ?></h4>
                <div class="text-warning mb-2">
                    <?= str_repeat('<i class="fas fa-star"></i>', round($avaliacao['nota'])) ?>
                    <?= str_repeat('<i class="far fa-star"></i>', 5 - round($avaliacao['nota'])) ?>
                    <span class="text-dark ms-2"><?= number_format($avaliacao['nota'], 1) ?>/5.0</span>
                </div>
            </div>
        </div>
        <div class="text-end">
            <small class="text-muted d-block">
                Avaliado em: <?= date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])) ?>
            </small>
            <?php if (!empty($avaliacao['usuario_nome'])): ?>
                <small class="text-muted">Por: <?= htmlspecialchars($avaliacao['usuario_nome']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumo da Avaliação -->
    <div class="resumo-avaliacao mb-4 p-3 bg-light rounded">
        <h5><i class="fas fa-info-circle me-2"></i> Resumo da Avaliação</h5>
        
        <div class="row mt-3">
            <!-- Bônus -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-<?= $avaliacao['tem_bonus'] ? 'success' : 'secondary' ?> me-2">
                        <?= $avaliacao['tem_bonus'] ? 'COM BÔNUS' : 'SEM BÔNUS' ?>
                    </span>
                    <?php if ($avaliacao['tem_bonus']): ?>
                        <span class="small">
                            <?= $avaliacao['bonus'] > 0 ? 'R$ ' . number_format($avaliacao['bonus'], 2) : 'Bônus disponível' ?>
                            <?= $avaliacao['usou_bonus'] ? '(Utilizado)' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($avaliacao['bonus_comentario'])): ?>
                    <p class="mt-1 mb-0 small">"<?= htmlspecialchars($avaliacao['bonus_comentario']) ?>"</p>
                <?php endif; ?>
            </div>
            
            <!-- Saque -->
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                    <span class="badge bg-<?= $avaliacao['saque_rapido'] ? 'success' : 'warning' ?> me-2">
                        SAQUE <?= $avaliacao['saque_rapido'] ? 'RÁPIDO' : 'DEMORADO' ?>
                    </span>
                    <?php if ($avaliacao['saque_nota']): ?>
                        <span class="small">Nota: <?= $avaliacao['saque_nota'] ?>/5</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($avaliacao['saque_comentario'])): ?>
                    <p class="mt-1 mb-0 small">"<?= htmlspecialchars($avaliacao['saque_comentario']) ?>"</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Comentário Geral -->
    <div class="comentario-geral mb-4 p-3 bg-light rounded">
        <h5><i class="fas fa-comment me-2"></i> Comentário Geral</h5>
        <p><?= !empty($avaliacao['comentario_geral']) ? nl2br(htmlspecialchars($avaliacao['comentario_geral'])) : 'Nenhum comentário geral fornecido.' ?></p>
    </div>

    <!-- Seção de Interações -->
    <div class="interacoes border-top border-bottom py-3 mb-4">
        <div class="d-flex justify-content-around text-center">
            <div>
                <button class="btn btn-lg like-btn <?= $usuarioLogado && usuarioCurtiu($avaliacao_id, $usuario_id, $conn) ? 'text-danger' : 'text-muted' ?>" 
                        data-avaliacao-id="<?= $avaliacao_id ?>" <?= !$usuarioLogado ? 'disabled' : '' ?>>
                    <i class="fas fa-heart"></i>
                </button>
                <div class="small"><?= $avaliacao['total_likes'] ?> curtidas</div>
            </div>
            <div>
                <span class="text-muted">
                    <i class="fas fa-comment"></i> <?= $avaliacao['total_comentarios'] ?> comentários
                </span>
            </div>
        </div>
    </div>

    <!-- Comentários -->
    <h5 class="mb-3"><i class="fas fa-comments me-2"></i> Comentários</h5>
    <div class="comentarios-list mb-4">
        <?php if ($comentarios->num_rows > 0): ?>
            <?php 
            $comentarios_array = $comentarios->fetch_all(MYSQLI_ASSOC);
            $comentarios_principais = array_filter($comentarios_array, function($c) { 
                return $c['comentario_pai_id'] === null; 
            });
            
            foreach ($comentarios_principais as $comentario): 
                $respostas = array_filter($comentarios_array, function($c) use ($comentario) { 
                    return $c['comentario_pai_id'] == $comentario['id']; 
                });
            ?>
                <div class="comentario mb-3 p-3 bg-white rounded shadow-sm">
                    <div class="d-flex align-items-center mb-2">
                        <img src="<?= htmlspecialchars($comentario['usuario_avatar'] ?? 'img/avatars/default.png') ?>" 
                             class="rounded-circle me-2" width="40">
                        <div>
                            <strong><?= htmlspecialchars($comentario['usuario_nome']) ?></strong>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($comentario['data_criacao'])) ?>
                            </small>
                        </div>
                    </div>
                    <p class="mb-2"><?= htmlspecialchars($comentario['comentario']) ?></p>
                    
                    <?php if ($usuarioLogado): ?>
                        <button class="btn btn-sm btn-outline-secondary btn-responder" 
                                data-comentario-id="<?= $comentario['id'] ?>">
                            <i class="fas fa-reply me-1"></i> Responder
                        </button>
                    <?php endif; ?>

                    <!-- Respostas -->
                    <?php if (!empty($respostas)): ?>
                        <div class="respostas mt-2 ms-4">
                            <?php foreach ($respostas as $resposta): ?>
                                <div class="resposta mb-2 p-2 border-start border-3 ps-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <img src="<?= htmlspecialchars($resposta['usuario_avatar'] ?? 'img/avatars/default.png') ?>" 
                                             class="rounded-circle me-2" width="32">
                                        <div>
                                            <strong><?= htmlspecialchars($resposta['usuario_nome']) ?></strong>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($resposta['data_criacao'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?= htmlspecialchars($resposta['comentario']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info py-2">
                Nenhum comentário ainda. Seja o primeiro a comentar!
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulário de comentário -->
    <?php if ($usuarioLogado): ?>
        <form class="form-comentario mb-4" data-avaliacao-id="<?= $avaliacao_id ?>">
            <div class="input-group">
                <input type="text" name="comentario" class="form-control" placeholder="Adicione um comentário..." required>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
            <input type="hidden" name="avaliacao_id" value="<?= $avaliacao_id ?>">
        </form>
    <?php else: ?>
        <div class="alert alert-info py-2">
            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Faça login</a> para comentar.
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Sistema de Curtidas
    $(document).on('click', '.like-btn', function() {
        var btn = $(this);
        var avaliacaoId = btn.data('avaliacao-id');
        var isActive = btn.hasClass('active');
        var action = isActive ? 'unlike' : 'like';
        
        $.ajax({
            url: 'processa_like.php',
            type: 'POST',
            data: {
                avaliacao_id: avaliacaoId,
                action: action
            },
            success: function(response) {
                if (response.success) {
                    btn.toggleClass('active');
                    btn.find('.like-count').text(response.likes);
                } else {
                    alert(response.message || 'Erro ao curtir');
                }
            },
            error: function() {
                alert('Erro na comunicação');
            }
        });
    });

    // Sistema de Comentários
    $(document).on('submit', '#form-comentario', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(form[0]);
        var avaliacaoId = form.data('avaliacao-id');
        
        $.ajax({
            url: 'adiciona_comentario.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    form[0].reset();
                    // Recarrega os comentários
                    $('#comentarios-container').load('carrega_comentarios.php?avaliacao_id=' + avaliacaoId);
                } else {
                    alert(response.message || 'Erro ao comentar');
                }
            },
            error: function() {
                alert('Erro na comunicação');
            }
        });
    });
});
</script>