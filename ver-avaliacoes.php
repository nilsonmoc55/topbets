<?php
require 'includes/conexao.php';
session_start();

// Verifica se o ID da casa de apostas foi passado
$bet_id = isset($_GET['bet_id']) ? intval($_GET['bet_id']) : 0;
if ($bet_id <= 0) {
    header("Location: index.php");
    exit();
}

// Busca dados da casa de apostas
$sql_bet = "SELECT id, nome, logo, url, descricao FROM bets WHERE id = ? AND ativo = 1";
$stmt_bet = $conn->prepare($sql_bet);
$stmt_bet->bind_param("i", $bet_id);
$stmt_bet->execute();
$result_bet = $stmt_bet->get_result();
$bet = $result_bet->fetch_assoc();

if (!$bet) {
    header("Location: index.php");
    exit();
}

// Busca avaliações moderadas
$sql_avaliacoes = "SELECT a.*, u.nome as usuario_nome, u.avatar as usuario_avatar,
                   (SELECT COUNT(*) FROM reacoes WHERE avaliacao_id = a.id AND tipo = 'like') as likes,
                   (SELECT COUNT(*) FROM comentarios_avaliacoes WHERE avaliacao_id = a.id) as total_comentarios
                   FROM avaliacoes a
                   LEFT JOIN usuarios u ON a.usuario_id = u.id
                   WHERE a.bet_id = ? AND a.moderado = 1
                   ORDER BY a.data_avaliacao DESC";
$stmt_avaliacoes = $conn->prepare($sql_avaliacoes);
$stmt_avaliacoes->bind_param("i", $bet_id);
$stmt_avaliacoes->execute();
$avaliacoes = $stmt_avaliacoes->get_result();

// Verifica se usuário está logado
$usuarioLogado = isset($_SESSION['usuario_id']);
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Função para verificar se usuário curtiu
function usuarioCurtiu($avaliacao_id, $usuario_id, $conn) {
    if (!$usuario_id) return false;
    
    $stmt = $conn->prepare("SELECT id FROM reacoes WHERE avaliacao_id = ? AND usuario_id = ? AND tipo = 'like'");
    $stmt->bind_param("ii", $avaliacao_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - <?= htmlspecialchars($bet['nome']) ?> | TopBets</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="/css/estilo.css">
    <style>
        .avaliacao-card {
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .avaliacao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .avaliacao-header {
            cursor: pointer;
            padding: 1.25rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .avaliacao-header:hover {
            background-color: #f1f1f1;
        }
        .avaliacao-body {
            padding: 1.25rem;
            display: none;
        }
        .avaliacao-expandida .avaliacao-body {
            display: block;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .star-rating {
            color: #ffc107;
        }
        .like-btn.active {
            color: #dc3545 !important;
        }
        .comentarios-section {
            margin-top: 1.5rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        .comentario {
            transition: all 0.3s ease;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .comentario:hover {
            background-color: #e9ecef;
        }
        .comentario-form {
            margin-top: 1rem;
        }
        .resposta-form {
            transition: all 0.3s ease;
        }
        .responder-btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .highlighted {
            animation: highlight 2s;
            border-left: 4px solid #0d6efd;
        }
        @keyframes highlight {
            0% { background-color: rgba(13, 110, 253, 0.1); }
            100% { background-color: transparent; }
        }
        .btn-avaliacao {
            transition: all 0.2s;
        }
        .btn-avaliacao:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-4">
        <div class="row">
            <div class="col-lg-8">
                <!-- Cabeçalho da Casa de Apostas -->
                <div class="d-flex align-items-center mb-4">
                    <img src="<?= htmlspecialchars($bet['logo'] ?? 'img/logos/default.png') ?>" 
                         alt="<?= htmlspecialchars($bet['nome']) ?>" 
                         class="img-fluid rounded me-3" style="max-height: 80px;">
                    <div>
                        <h1><?= htmlspecialchars($bet['nome']) ?></h1>
                        <a href="<?= htmlspecialchars($bet['url']) ?>" target="_blank" class="text-decoration-none">
                            <i class="fas fa-external-link-alt"></i> Visitar site oficial
                        </a>
                        <?php if (!empty($bet['descricao'])): ?>
                            <p class="mt-2 text-muted"><?= nl2br(htmlspecialchars($bet['descricao'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <h3 class="mb-3"><i class="fas fa-comments me-2"></i>Avaliações</h3>

                <?php if (isset($_GET['avaliacao_id'])): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <span>Você está visualizando uma avaliação específica.</span>
                        <a href="?bet_id=<?= $bet_id ?>" class="btn btn-sm btn-outline-info">Ver todas</a>
                    </div>
                <?php endif; ?>

                <!-- Lista de Avaliações -->
                <?php if ($avaliacoes->num_rows > 0): ?>
                    <?php while ($avaliacao = $avaliacoes->fetch_assoc()): ?>
                        <div class="card avaliacao-card <?= (isset($_GET['avaliacao_id']) && $_GET['avaliacao_id'] == $avaliacao['id']) ? 'highlighted avaliacao-expandida' : '' ?>" 
                             id="avaliacao-<?= $avaliacao['id'] ?>">
                            <!-- Cabeçalho da Avaliação (clicável) -->
                            <div class="avaliacao-header" onclick="toggleAvaliacao(this)">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($avaliacao['usuario_avatar'] ?? 'img/avatars/default.png') ?>" 
                                             alt="<?= htmlspecialchars($avaliacao['usuario_nome'] ?? 'Anônimo') ?>" 
                                             class="avatar me-3">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($avaliacao['usuario_nome'] ?? 'Anônimo') ?></h5>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="star-rating">
                                        <?= str_repeat('<i class="fas fa-star"></i>', round($avaliacao['nota'])) ?>
                                        <?= str_repeat('<i class="far fa-star"></i>', 5 - round($avaliacao['nota'])) ?>
                                        <span class="text-dark ms-2"><?= number_format($avaliacao['nota'], 1) ?>/5.0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Corpo da Avaliação (expandível) -->
                            <div class="avaliacao-body">
                                <?php if (!empty($avaliacao['comentario_geral'])): ?>
                                    <div class="mb-3">
                                        <h6>Comentário Geral</h6>
                                        <p><?= nl2br(htmlspecialchars($avaliacao['comentario_geral'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Detalhes da Avaliação -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Pontos Positivos</h6>
                                        <?php if (!empty($avaliacao['pontos_positivos'])): ?>
                                            <p><?= nl2br(htmlspecialchars($avaliacao['pontos_positivos'])) ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">Nenhum ponto positivo mencionado</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Pontos Negativos</h6>
                                        <?php if (!empty($avaliacao['pontos_negativos'])): ?>
                                            <p><?= nl2br(htmlspecialchars($avaliacao['pontos_negativos'])) ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">Nenhum ponto negativo mencionado</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Seção de interações -->
                                <div class="d-flex justify-content-between border-top pt-3">
                                    <div>
                                        <button class="btn btn-sm btn-avaliacao like-btn <?= $usuarioLogado && usuarioCurtiu($avaliacao['id'], $usuario_id, $conn) ? 'active' : '' ?>" 
                                                data-avaliacao-id="<?= $avaliacao['id'] ?>" <?= !$usuarioLogado ? 'disabled' : '' ?>>
                                            <i class="fas fa-heart me-1"></i>
                                            <span class="like-count"><?= $avaliacao['likes'] ?></span>
                                        </button>
                                        <span class="text-muted ms-3">
                                            <i class="fas fa-comment me-1"></i> <?= $avaliacao['total_comentarios'] ?> comentários
                                        </span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-secondary btn-avaliacao share-btn" 
                                                data-share-url="<?= htmlspecialchars("ver-avaliacoes.php?bet_id=$bet_id&avaliacao_id=" . $avaliacao['id'] . "#avaliacao-" . $avaliacao['id']) ?>">
                                            <i class="fas fa-share-alt me-1"></i> Compartilhar
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Seção de Comentários -->
                                <div class="comentarios-section">
                                    <h6><i class="fas fa-comments me-1"></i> Comentários</h6>
                                    
                                    <!-- Lista de Comentários (carregada via AJAX) -->
                                    <div class="comentarios-lista" id="comentarios-<?= $avaliacao['id'] ?>">
                                        <div class="text-center py-2">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Carregando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Formulário para novo comentário -->
                                    <?php if ($usuarioLogado): ?>
                                        <form class="comentario-form" data-avaliacao-id="<?= $avaliacao['id'] ?>">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Adicione um comentário..." required>
                                                <button class="btn btn-primary btn-avaliacao" type="submit">Enviar</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info mt-3">
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Faça login</a> para comentar.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Nenhuma avaliação aprovada ainda para esta casa de apostas.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações</h5>
                    </div>
                    <div class="card-body">
                        <a href="<?= htmlspecialchars($bet['url']) ?>" target="_blank" class="btn btn-outline-primary w-100 mb-3 btn-avaliacao">
                            <i class="fas fa-external-link-alt me-1"></i> Visitar Site
                        </a>
                        
                        <?php if ($usuarioLogado): ?>
                            <button class="btn btn-success w-100 mb-3 btn-avaliacao" data-bs-toggle="modal" data-bs-target="#modalAvaliar">
                                <i class="fas fa-edit me-1"></i> Avaliar
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success w-100 mb-3 btn-avaliacao" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-1"></i> Login para Avaliar
                            </button>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-outline-secondary w-100 btn-avaliacao">
                            <i class="fas fa-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Compartilhar -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-share-alt me-2"></i>Compartilhar Avaliação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Link para compartilhar</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareLink" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyShareLink">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around">
                        <a href="#" class="btn btn-outline-primary share-facebook"><i class="fab fa-facebook-f me-2"></i>Facebook</a>
                        <a href="#" class="btn btn-outline-info share-twitter"><i class="fab fa-twitter me-2"></i>Twitter</a>
                        <a href="#" class="btn btn-outline-success share-whatsapp"><i class="fab fa-whatsapp me-2"></i>WhatsApp</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Avaliação -->
    <div class="modal fade" id="modalAvaliar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Avaliar <?= htmlspecialchars($bet['nome']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="processa_avaliacao.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="bet_id" value="<?= $bet_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Nota Geral</label>
                            <div class="star-rating">
                                <input type="hidden" name="nota" id="nota" value="0">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="far fa-star star-icon" data-value="<?= $i ?>" style="font-size: 2rem; cursor: pointer;"></i>
                                <?php endfor; ?>
                                <span class="ms-2" id="nota-selecionada">0/5</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comentario_geral" class="form-label">Comentário Geral</label>
                            <textarea class="form-control" id="comentario_geral" name="comentario_geral" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pontos_positivos" class="form-label">Pontos Positivos</label>
                                    <textarea class="form-control" id="pontos_positivos" name="pontos_positivos" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pontos_negativos" class="form-label">Pontos Negativos</label>
                                    <textarea class="form-control" id="pontos_negativos" name="pontos_negativos" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Rolagem para avaliação específica se houver no link
        <?php if (isset($_GET['avaliacao_id'])): ?>
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('#avaliacao-<?= $_GET['avaliacao_id'] ?>').offset().top - 100
                }, 1000);
                
                // Expande a avaliação automaticamente
                $('#avaliacao-<?= $_GET['avaliacao_id'] ?>').addClass('avaliacao-expandida');
                
                // Carrega os comentários
                carregarComentarios(<?= $_GET['avaliacao_id'] ?>);
            }, 500);
        <?php endif; ?>

        // Função para expandir/recolher avaliação
        window.toggleAvaliacao = function(element) {
            const card = $(element).closest('.card');
            card.toggleClass('avaliacao-expandida');
            
            // Se estiver expandindo, carrega os comentários
            if (card.hasClass('avaliacao-expandida')) {
                const avaliacaoId = card.attr('id').replace('avaliacao-', '');
                carregarComentarios(avaliacaoId);
            }
        };

        // Carrega comentários via AJAX
        function carregarComentarios(avaliacaoId) {
            const container = $('#comentarios-' + avaliacaoId);
            
            // Verifica se já foi carregado
            if (container.data('loaded')) return;
            
            $.ajax({
                url: 'carrega_comentarios.php',
                type: 'GET',
                data: { avaliacao_id: avaliacaoId },
                success: function(response) {
                    container.html(response);
                    container.data('loaded', true);
                },
                error: function() {
                    container.html('<div class="alert alert-danger">Erro ao carregar comentários</div>');
                }
            });
        }

        // Sistema de curtidas
        $(document).on('click', '.like-btn:not(:disabled)', function() {
            const btn = $(this);
            const avaliacaoId = btn.data('avaliacao-id');
            const action = btn.hasClass('active') ? 'unlike' : 'like';
            
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
                    }
                },
                error: function() {
                    alert('Erro ao processar sua curtida');
                }
            });
        });

        // Enviar comentário
        $(document).on('submit', '.comentario-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const avaliacaoId = form.data('avaliacao-id');
            const comentario = form.find('input').val().trim();
            
            if (!comentario) return;
            
            $.ajax({
                url: 'adiciona_comentario.php',
                type: 'POST',
                data: {
                    avaliacao_id: avaliacaoId,
                    comentario: comentario
                },
                success: function(response) {
                    if (response.success) {
                        form.find('input').val('');
                        // Recarrega os comentários
                        $('#comentarios-' + avaliacaoId).data('loaded', false);
                        carregarComentarios(avaliacaoId);
                    }
                },
                error: function() {
                    alert('Erro ao enviar comentário');
                }
            });
        });

        // Sistema de respostas a comentários
        $(document).on('click', '.responder-btn', function() {
            const comentarioId = $(this).data('comentario-id');
            $(this).closest('.comentario').find('.resposta-form').slideToggle();
        });

        $(document).on('submit', '.resposta-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const comentarioId = form.data('comentario-id');
            const avaliacaoId = form.find('[name="avaliacao_id"]').val();
            const resposta = form.find('[name="resposta"]').val().trim();
            
            if (!resposta) return;
            
            $.ajax({
                url: 'adiciona_resposta.php',
                type: 'POST',
                data: {
                    comentario_id: comentarioId,
                    avaliacao_id: avaliacaoId,
                    resposta: resposta
                },
                success: function(response) {
                    if (response.success) {
                        form.find('[name="resposta"]').val('');
                        form.slideUp();
                        
                        // Adiciona a nova resposta abaixo do comentário original
                        $(response.html).insertAfter(form.closest('.comentario'));
                    }
                },
                error: function() {
                    alert('Erro ao enviar resposta');
                }
            });
        });

        // Compartilhamento
        $(document).on('click', '.share-btn', function() {
            const shareUrl = window.location.origin + '/' + $(this).data('share-url');
            
            $('#shareLink').val(shareUrl);
            
            // Prepara links para redes sociais
            $('.share-facebook').attr('href', 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl));
            $('.share-twitter').attr('href', 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + '&text=Confira%20esta%20avaliação');
            $('.share-whatsapp').attr('href', 'https://wa.me/?text=' + encodeURIComponent('Confira esta avaliação: ' + shareUrl));
            
            // Mostra o modal
            new bootstrap.Modal(document.getElementById('shareModal')).show();
        });

        // Copiar link
        $('#copyShareLink').on('click', function() {
            const copyText = document.getElementById("shareLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            const originalText = $(this).html();
            $(this).html('<i class="fas fa-check"></i> Copiado!');
            setTimeout(function() {
                $('#copyShareLink').html(originalText);
            }, 2000);
        });

        // Sistema de avaliação por estrelas
        $('.star-icon').on('click', function() {
            const value = $(this).data('value');
            $('#nota').val(value);
            $('#nota-selecionada').text(value + '/5');
            
            // Atualiza visualização das estrelas
            $('.star-icon').each(function() {
                if ($(this).data('value') <= value) {
                    $(this).removeClass('far').addClass('fas');
                } else {
                    $(this).removeClass('fas').addClass('far');
                }
            });
        });

        // Hover nas estrelas
        $('.star-icon').hover(
            function() {
                const hoverValue = $(this).data('value');
                $('.star-icon').each(function() {
                    if ($(this).data('value') <= hoverValue) {
                        $(this).removeClass('far').addClass('fas');
                    }
                });
            },
            function() {
                const selectedValue = $('#nota').val();
                $('.star-icon').each(function() {
                    if ($(this).data('value') > selectedValue) {
                        $(this).removeClass('fas').addClass('far');
                    }
                });
            }
        );
    });
    </script>
</body>
</html>