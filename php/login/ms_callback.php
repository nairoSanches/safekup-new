<?php
// Callback do Microsoft Entra ID (Azure AD) para concluir o login
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
$dotenv->load();

$tenantId     = $_ENV['AZURE_TENANT_ID']     ?? '';
$clientId     = $_ENV['AZURE_CLIENT_ID']     ?? '';
$clientSecret = $_ENV['AZURE_CLIENT_SECRET'] ?? '';
$redirectUri  = $_ENV['AZURE_REDIRECT_URI']  ?? '';

if (!$tenantId || !$clientId || !$clientSecret || !$redirectUri) {
    http_response_code(500);
    echo 'Configuração Azure AD ausente. Defina AZURE_TENANT_ID, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET e AZURE_REDIRECT_URI em /etc/safekup/.env';
    exit;
}

// Verifica state
if (!isset($_GET['state']) || !isset($_SESSION['msal_state']) || $_GET['state'] !== $_SESSION['msal_state']) {
    http_response_code(400);
    echo 'State inválido';
    exit;
}

if (!isset($_GET['code'])) {
    http_response_code(400);
    echo 'Código de autorização ausente';
    exit;
}

$code = $_GET['code'];

// Troca o code por tokens
$tokenUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', rawurlencode($tenantId));

$post = [
    'grant_type'    => 'authorization_code',
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'code'          => $code,
    'redirect_uri'  => $redirectUri,
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http < 200 || $http >= 300 || !$resp) {
    http_response_code(401);
    echo 'Falha ao trocar o código por token.';
    exit;
}

$data = json_decode($resp, true);
$accessToken = $data['access_token'] ?? null;

if (!$accessToken) {
    http_response_code(401);
    echo 'Token não recebido.';
    exit;
}

// Busca dados do usuário no Graph
$ch = curl_init('https://graph.microsoft.com/v1.0/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
$meResp = curl_exec($ch);
$meHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($meHttp < 200 || $meHttp >= 300 || !$meResp) {
    http_response_code(401);
    echo 'Falha ao obter usuário no Graph.';
    exit;
}

$me = json_decode($meResp, true);
$upn = $me['userPrincipalName'] ?? ($me['mail'] ?? null);

if (!$upn) {
    http_response_code(401);
    echo 'Não foi possível identificar o usuário.';
    exit;
}

// Login concluído — cria sessão compatível com o sistema atual
$_SESSION['login'] = $upn;

// Limpa state/nonce
unset($_SESSION['msal_state'], $_SESSION['msal_nonce']);

// Redireciona para o painel
header('Location: ../painel/painel.php');
exit;
?>
