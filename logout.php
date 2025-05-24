<?php
// logout.php

// Inicia a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroi todas as variáveis de sessão
$_SESSION = array();

// Se deseja destruir o cookie de sessão também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroi a sessão
session_destroy();

// Redireciona para a página inicial
header("Location: index.php");
exit();