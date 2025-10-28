<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
$dotenv->load();
 

$ldap_host = $_ENV['LDAP_HOST'];
$ldap_port = $_ENV['LDAP_PORT'];
$base_dn   = $_ENV['LDAP_DN'];

// Função para verificar se o usuário existe no LDAP
function authenticateLDAP($ldap_host, $ldap_port, $ldap_user, $ldap_pass, $base_dn)
{
    // Conectar ao servidor LDAP
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) {
        return false; // Não foi possível conectar ao servidor LDAP
    }

    // Configurar opções LDAP
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // Tentar autenticar no servidor LDAP
    if (!ldap_bind($ldap_conn, $ldap_user, $ldap_pass)) {
        return false;
    } else {
        return true;
    }

    // Desconectar do servidor LDAP em caso de falha
    ldap_unbind($ldap_conn);
    return false;
}

// Função para verificar se o cache é válido
function isCacheValid($ldap_user, $ldap_pass)
{
    if (isset($_SESSION['cache'][$ldap_user])) {
        $cache = $_SESSION['cache'][$ldap_user];
        $cache_time = $cache['time'];
        $cache_pass_hash = $cache['pass_hash'];
        $current_time = time();
        // Verifica se o cache é válido por tempo e se a senha corresponde
        if (($current_time - $cache_time) < 86400 && password_verify($ldap_pass, $cache_pass_hash)) {
            return true;
        }
    }
    return false;
}

// Verificar se os dados de login foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Receber dados do formulário
    $ldap_user = $_POST['login'];
    $ldap_pass = $_POST['senha'];

    // Tentar autenticar no LDAP
    if (authenticateLDAP($ldap_host, $ldap_port, $ldap_user, $ldap_pass, $base_dn)) {
        // Se a autenticação for bem-sucedida, cria a sessão e armazena no cache
        $_SESSION['login'] = $ldap_user;
        $_SESSION['cache'][$ldap_user] = [
            'time' => time(),
            'pass_hash' => password_hash($ldap_pass, PASSWORD_DEFAULT)
        ];
        echo "true";
    } else {
        // Se a autenticação falhar, verifica se o cache é válido
        if (isCacheValid($ldap_user, $ldap_pass)) {
            $_SESSION['login'] = $ldap_user;
            echo "true";
        } else {
            // Se o cache não for válido, retornar mensagem de erro
            die("dados_invalidos");
        }
    }
}
?>
