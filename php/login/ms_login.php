<?php
// Inicia a sessão e redireciona para o Microsoft Entra ID (Azure AD)
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega .env do /etc/safekup
$dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
$dotenv->load();

$tenantId     = $_ENV['AZURE_TENANT_ID']     ?? '';
$clientId     = $_ENV['AZURE_CLIENT_ID']     ?? '';
$redirectUri  = $_ENV['AZURE_REDIRECT_URI']  ?? '';
$scopes       = $_ENV['AZURE_SCOPES']        ?? 'openid profile email offline_access User.Read';

if (!$tenantId || !$clientId || !$redirectUri) {
    http_response_code(500);
    echo 'Configuração Azure AD ausente. Defina AZURE_TENANT_ID, AZURE_CLIENT_ID e AZURE_REDIRECT_URI em /etc/safekup/.env';
    exit;
}

$authUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/authorize', rawurlencode($tenantId));

// Proteções: state e nonce
$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));
$_SESSION['msal_state'] = $state;
$_SESSION['msal_nonce'] = $nonce;

$params = [
    'client_id'     => $clientId,
    'response_type' => 'code',
    'redirect_uri'  => $redirectUri,
    'response_mode' => 'query',
    'scope'         => $scopes,
    'state'         => $state,
    'nonce'         => $nonce,
];

$location = $authUrl . '?' . http_build_query($params);
header('Location: ' . $location);
exit;
?>
