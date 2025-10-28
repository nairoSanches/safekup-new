<?php

// Incluir o arquivo de configuração
require_once __DIR__ . '/../../data/config.php';

function conectar(){
    try {
   
        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];

        $conexao = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados1: " . $e->getMessage());
    }
    return $conexao;
}

?>
