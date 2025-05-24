<?php
include 'includes/conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Registrar clique
    $conn->query("UPDATE anuncios SET cliques = cliques + 1 WHERE id = $id");
    
    // Redirecionar para a URL do anúncio
    $result = $conn->query("SELECT url FROM anuncios WHERE id = $id AND tipo = 'propio'");
    if ($result && $anuncio = $result->fetch_assoc()) {
        header("Location: " . $anuncio['url']);
        exit();
    }
}

// Se algo der errado, redireciona para a home
header('Location: /');
exit();