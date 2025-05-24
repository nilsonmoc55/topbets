<?php
require_once __DIR__ . '/../includes/conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Dados básicos
$bet_id = (int)$_POST['bet_id'];
$nome_avaliador = trim($_POST['nome_avaliador']);
$email_avaliador = trim($_POST['email_avaliador']);

// Validações básicas
if (empty($bet_id) || empty($nome_avaliador) || empty($email_avaliador)) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios faltando']);
    exit;
}

// Busca campos obrigatórios do formulário
$campos_obrigatorios = $conn->query("
    SELECT nome_campo FROM formulario_campos 
    WHERE ativo = 1 AND obrigatorio = 1
");

while($campo = $campos_obrigatorios->fetch_assoc()) {
    $nome_campo = $campo['nome_campo'];
    if (!isset($_POST[$nome_campo]) || empty(trim($_POST[$nome_campo]))) {
        echo json_encode(['success' => false, 'message' => "O campo {$nome_campo} é obrigatório"]);
        exit;
    }
}

// Prepara dados para inserção
$dados_campos = [];
$campos_dinamicos = $conn->query("SELECT * FROM formulario_campos WHERE ativo = 1");

while($campo = $campos_dinamicos->fetch_assoc()) {
    $nome_campo = $campo['nome_campo'];
    
    if (isset($_POST[$nome_campo])) {
        $valor = is_array($_POST[$nome_campo]) ? 
                 json_encode($_POST[$nome_campo]) : 
                 trim($_POST[$nome_campo]);
        
        $dados_campos[$nome_campo] = $valor;
    }
}

// Insere no banco de dados
try {
    // Começa a transação
    $conn->begin_transaction();
    
    // Insere a avaliação básica
    $stmt = $conn->prepare("
        INSERT INTO avaliacoes 
        (bet_id, nome_avaliador, email_avaliador, status, data_avaliacao) 
        VALUES (?, ?, ?, 'pendente', NOW())
    ");
    $stmt->bind_param("iss", $bet_id, $nome_avaliador, $email_avaliador);
    $stmt->execute();
    $avaliacao_id = $conn->insert_id;
    
    // Insere os campos dinâmicos
    foreach ($dados_campos as $campo => $valor) {
        $stmt = $conn->prepare("
            INSERT INTO avaliacao_campos 
            (avaliacao_id, nome_campo, valor_campo) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $avaliacao_id, $campo, $valor);
        $stmt->execute();
    }
    
    // Confirma a transação
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Avaliação enviada com sucesso!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar avaliação: ' . $e->getMessage()]);
}