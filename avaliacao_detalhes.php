<?php
require 'includes/conexao.php';
session_start();

$avaliacao_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($avaliacao_id <= 0) {
    header("Location: index.php");
    exit();
}

// Busca detalhes completos da avaliação
$sql = "SELECT a.*, u.nome as usuario_nome, u.avatar as usuario_avatar, b.nome as bet_nome, b.logo as bet_logo,
       (SELECT COUNT(*) FROM reacoes WHERE avaliacao_id = a.id AND tipo = 'like') as likes,
       (SELECT COUNT(*) FROM comentarios_avaliacoes WHERE avaliacao_id = a.id) as total_comentarios
       FROM avaliacoes a
       JOIN usuarios u ON a.usuario_id = u.id
       JOIN bets b ON a.bet_id = b.id
       WHERE a.id = ? AND a.moderado = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $avaliacao_id);
$stmt->execute();
$avaliacao = $stmt->get_result()->fetch_assoc();

if (!$avaliacao) {
    header("Location: index.php");
    exit();
}

// Verifica se usuário está logado
$usuarioLogado = isset($_SESSION['usuario_id']);
$usuario_id = $_SESSION['usuario_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação - <?= htmlspecialchars($avaliacao['bet_nome']) ?> | TopBets</title>
    <!-- Inclua os mesmos estilos do arquivo anterior -->
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Detalhes da Avaliação</h4>
                            <a href="bet_avaliacoes.php?bet_id=<?= $avaliacao['bet_id'] ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Voltar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Exiba todos os detalhes da avaliação aqui -->
                        <!-- Similar ao card de avaliação do arquivo anterior, mas com mais detalhes -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>