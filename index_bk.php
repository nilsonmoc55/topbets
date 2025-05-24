<?php
include 'includes/conexao.php';
session_start();

// Verifica se usuário está logado
$usuarioLogado = isset($_SESSION['usuario_id']);

// Ordenação padrão (melhor pontuação)
$ordenacao = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'melhor';
$filtro_bonus = isset($_GET['bonus']) ? $_GET['bonus'] : 'todos';

// Query dinâmica
switch ($ordenacao) {
    case 'pior':
        $order_sql = "ORDER BY media_nota ASC";
        break;
    case 'melhor-bonus':
        $order_sql = "ORDER BY bonus DESC";
        break;
    case 'pior-bonus':
        $order_sql = "ORDER BY bonus ASC";
        break;
    default:
        $order_sql = "ORDER BY media_nota DESC";
}

// Filtro de bônus
$where_bonus = "";
if ($filtro_bonus == 'com-bonus') {
    $where_bonus = "AND bonus > 0";
} elseif ($filtro_bonus == 'sem-bonus') {
    $where_bonus = "AND bonus = 0";
}

// Busca bets cadastradas
$sql = "
    SELECT 
        b.id,
        b.nome,
        b.url,
        b.logo,
        COALESCE(AVG(a.nota), 0) as media_nota,
        COUNT(a.id) as total_avaliacoes,
        MAX(a.bonus) as bonus
    FROM bets b
    LEFT JOIN avaliacoes a ON b.id = a.bet_id
    WHERE b.ativo = 1 $where_bonus
    GROUP BY b.id
    $order_sql
    LIMIT 12
";
$bets = $conn->query($sql);

// Dados para o carrossel (destaques)
$destaques = $conn->query("SELECT * FROM anuncios WHERE ativo = 1 LIMIT 5");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TopBets - Melhores Sites de Apostas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="css/carrossel.css">
    <style>
        .btn-avaliacao {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .btn-group-avaliacao {
            display: flex;
            gap: 8px;
        }
        @media (max-width: 576px) {
            .btn-group-avaliacao {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/carrossel.php'; ?>
    
    <?php include 'includes/filtros.php'; ?>
    
    <?php include 'includes/lista-bets.php'; ?>
    
    <?php include 'includes/modais.php'; ?>
    
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS + Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php include 'includes/scripts.php'; ?>
</body>
</html>