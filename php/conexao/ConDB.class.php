<?php
// Carrega o autoload do Composer para carregar as dependências, incluindo o phpdotenv
require_once __DIR__ . '/../../vendor/autoload.php';

// Inclui o arquivo de configuração que carrega o phpdotenv e as variáveis de ambiente
$config = require_once __DIR__ . '/../../data/config.php';

// Classe abstrata para conexão com o banco de dados
abstract class ConDB {
    // Variável estática para armazenar a conexão PDO
    private static $conexao;

    private function setCon() {
        // Se a conexão ainda não foi estabelecida, cria a conexão utilizando as variáveis do arquivo de configuração
        if (!self::$conexao) {
            $dbHost = $_ENV['DB_HOST'];
            $dbName = $_ENV['DB_NAME'];
            $dbUser = $_ENV['DB_USER'];
            $dbPass = $_ENV['DB_PASS'];

            try {
                self::$conexao = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
                self::$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Erro ao conectar ao banco de dados: " . $e->getMessage());
            }
        }

        return self::$conexao;
    }

    public function getCon() {
        return $this->setCon();
    }
}
?>
