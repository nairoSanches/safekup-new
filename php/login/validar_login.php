<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega variáveis de ambiente de /etc/safekup/.env
$dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
$dotenv->load();

$ldap_host     = $_ENV['LDAP_HOST']      ?? 'ldap://localhost';
$ldap_port     = (int)($_ENV['LDAP_PORT'] ?? 389);
$base_dn       = $_ENV['LDAP_DN']        ?? '';
$netbios       = $_ENV['LDAP_NETBIOS']   ?? '';
$upn_suffix    = $_ENV['LDAP_UPN_SUFFIX']?? '';
$use_starttls  = filter_var($_ENV['LDAP_STARTTLS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$bind_dn       = $_ENV['LDAP_BIND_DN']   ?? '';
$bind_pass     = $_ENV['LDAP_BIND_PASS'] ?? '';
$search_attr   = $_ENV['LDAP_SEARCH_ATTR'] ?? 'sAMAccountName';
$debug_auth    = true;//filter_var($_ENV['AUTH_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
$debug_log     = $_ENV['AUTH_DEBUG_LOG'] ?? __DIR__ . '/../../log/auth.log';

function attemptBind($conn, $rdn, $pass)
{
    if (!$rdn || !$pass) return false;
    return @ldap_bind($conn, $rdn, $pass);
}

function authenticateLDAP($ldap_host, $ldap_port, $user, $pass, $base_dn, $netbios, $upn_suffix, $use_starttls, $bind_dn, $bind_pass, $search_attr)
{
    $conn = @ldap_connect($ldap_host, $ldap_port);
    if (!$conn) return false;

    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    if ($use_starttls) {
        @ldap_start_tls($conn);
    }

    // 1) Se houver bind de serviço, use para procurar o DN do usuário e depois faça bind com a senha do usuário
    if (!empty($bind_dn) && !empty($bind_pass)) {
        if (@ldap_bind($conn, $bind_dn, $bind_pass)) {
            // Determina filtro de busca conforme o formato informado
            $filter = '';
            if (strpos($user, '@') !== false) {
                $filter = sprintf('(userPrincipalName=%s)', ldap_escape($user, '', LDAP_ESCAPE_FILTER));
            } elseif (strpos($user, '\\') !== false) {
                // DOMAIN\user -> pegar parte após barra
                $parts = explode('\\\\', $user);
                $account = end($parts);
                $filter = sprintf('(%s=%s)', $search_attr, ldap_escape($account, '', LDAP_ESCAPE_FILTER));
            } else {
                $filter = sprintf('(%s=%s)', $search_attr, ldap_escape($user, '', LDAP_ESCAPE_FILTER));
            }

            // Se o base_dn é muito específico (com OU=), tenta primeiro ele, depois a raiz do domínio (apenas DC=...)
            $searchBases = [];
            if (!empty($base_dn)) {
                $searchBases[] = $base_dn;
                // Extrai somente DC=... como fallback
                if (stripos($base_dn, 'DC=') !== false) {
                    preg_match_all('/DC=[^,]*/i', $base_dn, $m);
                    if (!empty($m[0])) {
                        $searchBases[] = implode(',', $m[0]);
                    }
                }
            } else {
                $searchBases[] = '';
            }

            $dn = null;
            foreach ($searchBases as $sb) {
                $sr = @ldap_search($conn, $sb, $filter, ['dn']);
                if ($sr) {
                    $entries = ldap_get_entries($conn, $sr);
                    if ($entries && $entries['count'] > 0) {
                        $dn = $entries[0]['dn'];
                        break;
                    }
                }
            }
            if ($dn && attemptBind($conn, $dn, $pass)) {
                @ldap_unbind($conn);
                return true;
            }
            // Log de falha de bind com DN encontrado
            if (function_exists('ldap_error')) {
                $err = @ldap_error($conn);
                @file_put_contents($GLOBALS['debug_log'], sprintf("[%s] Falha bind com DN resolvido. filtro=%s bases=%s erro=%s\n", date('c'), $filter, implode(' | ', $searchBases), $err), FILE_APPEND);
            }
            // se não achou ou bind falhou, continuará no fallback abaixo
        }
        // se bind de serviço falhar, parte para fallback
    }

    // 2) Fallback: Monta candidatos de RDN para AD e tenta bind direto
    $candidates = [];
    $hasDomain = (strpos($user, '@') !== false || strpos($user, '\\') !== false);
    if ($hasDomain) {
        $candidates[] = $user; // já em formato UPN (user@dominio) ou DOMAIN\user
    } else {
        if (!empty($upn_suffix)) {
            $suffix = str_starts_with($upn_suffix, '@') ? $upn_suffix : ('@' . $upn_suffix);
            $candidates[] = $user . $suffix;
        }
        if (!empty($netbios)) {
            $candidates[] = $netbios . '\\' . $user;
        }
        // Fallback: tentar como está (caso o usuário informe um DN completo)
        $candidates[] = $user;
    }

    foreach ($candidates as $rdn) {
        if (attemptBind($conn, $rdn, $pass)) {
            @ldap_unbind($conn);
            return true;
        }
    }
    if (function_exists('ldap_error')) {
        $err = @ldap_error($conn);
        @file_put_contents($GLOBALS['debug_log'], sprintf("[%s] Falha bind com candidatos. tried=%s erro=%s\n", date('c'), implode(' | ', $candidates), $err), FILE_APPEND);
    }

    @ldap_unbind($conn);
    return false;
}

function isCacheValid($ldap_user, $ldap_pass)
{
    if (isset($_SESSION['cache'][$ldap_user])) {
        $cache = $_SESSION['cache'][$ldap_user];
        $cache_time = $cache['time'];
        $cache_pass_hash = $cache['pass_hash'];
        $current_time = time();
        if (($current_time - $cache_time) < 86400 && password_verify($ldap_pass, $cache_pass_hash)) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ldap_user = $_POST['login'] ?? '';
    $ldap_pass = $_POST['senha'] ?? '';

    if (!$ldap_user || !$ldap_pass) {
        die('dados_invalidos');
    }

    $ok = authenticateLDAP($ldap_host, $ldap_port, $ldap_user, $ldap_pass, $base_dn, $netbios, $upn_suffix, $use_starttls, $bind_dn, $bind_pass, $search_attr);
    if ($ok) {
        $_SESSION['login'] = $ldap_user;
        $_SESSION['cache'][$ldap_user] = [
            'time' => time(),
            'pass_hash' => password_hash($ldap_pass, PASSWORD_DEFAULT)
        ];
        echo 'true';
    } else {
        if (isCacheValid($ldap_user, $ldap_pass)) {
            $_SESSION['login'] = $ldap_user;
            echo 'true';
        } else {
            if ($debug_auth) {
                // Log básico sem senha
                $logDir = dirname($debug_log);
                if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
                $msg = sprintf(
                    "[%s] LDAP auth falhou para '%s' em host=%s port=%s base_dn='%s' netbios='%s' upn_suffix='%s' starttls=%s\n",
                    date('c'),
                    $ldap_user,
                    $ldap_host,
                    (string)$ldap_port,
                    $base_dn,
                    $netbios,
                    $upn_suffix,
                    $use_starttls ? 'true' : 'false'
                );
                @file_put_contents($debug_log, $msg, FILE_APPEND);
            }
            die('dados_invalidos');
        }
    }
}
?>
