<?php

// Garante sessão iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verifica chave sem gerar notice/warning
if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('Location: ../../index.html');
    exit();
}

?>
