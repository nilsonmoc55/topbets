<?php
session_start();

header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user_name' => ''
];

if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    $response['logged_in'] = true;
    $response['user_name'] = $_SESSION['nome'] ?? 'Usuário';
}

echo json_encode($response);