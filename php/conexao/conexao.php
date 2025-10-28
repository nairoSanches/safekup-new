<?php
require_once __DIR__ . '/../../data/config.php';

use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable('/etc/safekup');
$dotenv->load();


$conexao = mysqli_connect(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);

if (!$conexao) {
    die("Erro na conexão com o banco: " . mysqli_connect_error());
}

mysqli_set_charset($conexao, "utf8mb4");
?>
