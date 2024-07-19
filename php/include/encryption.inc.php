<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

class Encryption {
    private $encryptionKey;

    public function __construct() {
        $dotenvPath = __DIR__ . '/../../'; 
        if (!file_exists($dotenvPath . '.env')) {
            throw new Exception("Arquivo .env não encontrado no caminho: $dotenvPath");
        }

        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->load();

        if (isset($_ENV['ENCRYPTION_KEY'])) {
            $this->encryptionKey = base64_decode($_ENV['ENCRYPTION_KEY']);
        } else {
            throw new Exception("Chave de criptografia não encontrada em .env");
        }
    }

    public function encrypt($plaintext) {
        $iv_length = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = openssl_random_pseudo_bytes($iv_length);
        $ciphertext = openssl_encrypt($plaintext, $cipher, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $ciphertext);
    }

    public function decrypt($ciphertext) {
        $ciphertext = base64_decode($ciphertext);
        $iv_length = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = substr($ciphertext, 0, $iv_length);
        $ciphertext = substr($ciphertext, $iv_length);
        $plaintext = openssl_decrypt($ciphertext, $cipher, $this->encryptionKey, OPENSSL_ZERO_PADDING, $iv);
        $plaintext = rtrim($plaintext, "\x00..\x1F");
        return $plaintext;
    }
}

// Testando a classe Encryption
try {
    $encryption = new Encryption();
  
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
