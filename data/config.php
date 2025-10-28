<?php
// Carrega o autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega as variáveis de ambiente do arquivo /etc/safekup/.env
$dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
$dotenv->load();

// Retorna um array com as configurações do banco de dados
return [
    'DB_HOST' => $_ENV['DB_HOST'],
    'DB_NAME' => $_ENV['DB_NAME'],
    'DB_USER' => $_ENV['DB_USER'],
    'DB_PASS' => $_ENV['DB_PASS'],
];
