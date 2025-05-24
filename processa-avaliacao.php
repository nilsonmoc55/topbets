<?php
session_start();
require 'includes/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']));
}

// Verifica se os dados necessários foram enviados
if (!isset($_POST['bet_id'], $_POST['categoria'], $_POST['avaliacao'])) {
    die(json_encode(['status' => 'error', 'message' => 'Dados incompletos']));
}

// Prepara os dados
$bet_id = intval($_POST['bet_id']);
$usuario_id = $_SESSION['usuario_id'];
$categoria = $conn->real_escape_string($_POST['categoria']);
$problema = isset($_POST['problema']) ? $conn->real_escape_string($_POST['problema']) : null;
$avaliacao = json_encode($_POST['avaliacao']); // Converte o array para JSON
$data_avaliacao = date('Y-m-d H:i:s');

// Inicia transação
$conn->begin_transaction();

try {
    // Insere a avaliação principal
    $sql = "INSERT INTO avaliacoes 
            (bet_id, usuario_id, categoria, avaliacao, problema, data_avaliacao) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $bet_id, $usuario_id, $categoria, $avaliacao, $problema, $data_avaliacao);
    $stmt->execute();
    $avaliacao_id = $conn->insert_id;

    // Atualiza as médias da casa de apostas
    $sql_update = "UPDATE bets SET 
                  media_nota = (SELECT AVG(nota_geral) FROM avaliacoes WHERE bet_id = ?),
                  total_avaliacoes = (SELECT COUNT(*) FROM avaliacoes WHERE bet_id = ?)
                  WHERE id = ?";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iii", $bet_id, $bet_id, $bet_id);
    $stmt->execute();

    // Confirma a transação
    $conn->commit();

    echo json_encode(['status' => 'success', 'avaliacao_id' => $avaliacao_id]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $conn->rollback();
    error_log("Erro ao processar avaliação: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar avaliação']);
}
?>