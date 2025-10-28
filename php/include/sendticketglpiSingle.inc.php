<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

class GlpiClient
{
    private $glpi_url;
    private $app_token;
    private $username;
    private $password;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable('/etc/safekup', '.env');
        $dotenv->load();

        $this->glpi_url  = $_ENV['GLPI_URL'];
        $this->app_token = $_ENV['GLPI_API_TOKEN'];
        $this->username  = $_ENV['GLPI_USERNAME'];
        $this->password  = $_ENV['GLPI_PASSWORD'];
            
    }

    private function initSession()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->glpi_url . 'initSession/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
                'App-Token: ' . $this->app_token,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
      

        if ($response === false) {
            throw new Exception('Erro cURL: ' . curl_error($curl));
        }
        curl_close($curl);

        $data = json_decode($response, true);
        error_log("[GLPI API] Resposta initSession: " . $response);

       

        if (isset($data['session_token'])) {
            return $data['session_token'];
        } else {
            throw new Exception('Falha ao obter o token de sessão.');
        }
    }

    public function openTicket($name, $content)
    {
        $sessionToken = $this->initSession();

        $data = [
            "input" => [
                "name" => $name,
                "content" => $content,
                "itilcategories_id" => 1375,
                "urgency" => 1,
                "impact" => 1,
                "priority" => 1,
                "requesttypes_id" => 1,
                "locations_id" => 352,
                "groups_id_assign" => 7
                // Requerente será adicionado após a criação
            ]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->glpi_url . 'Ticket/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Session-Token: ' . $sessionToken,
            'App-Token: ' . $this->app_token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Erro na requisição: ' . curl_error($ch));
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['id'])) {
            $ticketId = $responseData['id'];
                        
            $this->addRequesterToTicket($ticketId, 2653, $sessionToken);

            return $ticketId;
        } else {
            throw new Exception('Falha ao criar o ticket. ||' . $response);
        }
    }

    private function addRequesterToTicket($ticketId, $userId, $sessionToken)
    {
        $data = [
            "input" => [
                "tickets_id" => $ticketId,
                "users_id" => $userId,
                "type" => 1 // 1 = requerente
            ]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->glpi_url . 'Ticket_User/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Session-Token: ' . $sessionToken,
            'App-Token: ' . $this->app_token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Erro ao adicionar requerente. HTTP $httpCode | Resposta: $response");
        }

        return true;
    }

   public function updateDatabase($databaseId, $newSize, $newDateLastBackup)
{
    $sessionToken = $this->initSession();

    $data = [
        "input" => [
            "id" => (int)$databaseId,
            "size" => (int)$newSize,
            "date_lastbackup" => $newDateLastBackup // "YYYY-MM-DD HH:MM:SS"
        ]
    ];

    $url = rtrim($this->glpi_url, "/") . "/Database/" . urlencode($databaseId);

    $ch = curl_init();
    $reqBody = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Capturar VERBOSE do cURL
    $verboseStream = fopen('php://temp', 'w+');

    // Capturar headers de resposta
    $respHeaders = [];
    $headerFn = function($ch, $header) use (&$respHeaders) {
        $len = strlen($header);
        $header = trim($header);
        if ($header !== '' && strpos($header, ':') !== false) {
            [$name, $value] = array_map('trim', explode(':', $header, 2));
            $respHeaders[$name][] = $value;
        }
        return $len;
    };

    // NUNCA logar tokens em texto claro
    $safeHeadersForLog = [
        'Session-Token' => substr($sessionToken, 0, 6) . '…',
        'App-Token'     => substr($this->app_token, 0, 6) . '…',
        'Content-Type'  => 'application/json'
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $reqBody,
        CURLOPT_HTTPHEADER     => [
            'Session-Token: ' . $sessionToken,
            'App-Token: ' . $this->app_token,
            'Content-Type: application/json'
        ],
        CURLOPT_HEADERFUNCTION => $headerFn,
        // Timeouts p/ evitar travas silenciosas
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        // Logs detalhados do cURL
        CURLOPT_VERBOSE        => true,
        CURLOPT_STDERR         => $verboseStream,
        // Se for ambiente interno com cert self-signed, descomente com cautela:
        CURLOPT_SSL_VERIFYPEER => false,
         CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $curlErrNo = curl_errno($ch);
    $curlErr   = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlInfo  = curl_getinfo($ch);

    // Pegar o VERBOSE
    rewind($verboseStream);
    $curlVerbose = stream_get_contents($verboseStream);
    fclose($verboseStream);

    curl_close($ch);

   // Log estruturado (use seu logger preferido)
   // error_log("[GLPI PUT] URL: {$url}");
   // error_log("[GLPI PUT] Request headers (safe): " . json_encode($safeHeadersForLog));
   // error_log("[GLPI PUT] Request body: " . $reqBody);
   // error_log("[GLPI PUT] cURL info: " . json_encode($curlInfo));
    if ($curlErrNo) {
        error_log("[GLPI PUT] cURL errno: {$curlErrNo} | error: {$curlErr}");
    }
   // error_log("[GLPI PUT] HTTP code: {$httpCode}");
   // error_log("[GLPI PUT] Response headers: " . json_encode($respHeaders));
   // error_log("[GLPI PUT] Response raw: " . ($response === false ? 'false' : $response));
   // error_log("[GLPI PUT] cURL VERBOSE:\n" . $curlVerbose);

    // Se veio corpo JSON, parseia
    $json = null;
    if (is_string($response) && $response !== '') {
        $json = json_decode($response, true);
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'http_code' => $httpCode,
            'response_json' => $json,
            'response_raw' => $response,
            'response_headers' => $respHeaders,
            'curl_info' => $curlInfo
        ];
    }

    // Tratar casos clássicos de HTTP 0
    if ($httpCode === 0) {
        // Dicas úteis pro log/diagnóstico
        $h2 = isset($curlInfo['http_version']) && (int)$curlInfo['http_version'] === CURL_HTTP_VERSION_2_0;
        $msg = "Erro ao atualizar o banco (sem resposta HTTP). Possíveis causas: DNS/rota, firewall, SSL/TLS, timeout, URL incorreta, listener do GLPI caído.";
        $msg .= " cURL errno={$curlErrNo} err=\"{$curlErr}\" http2=" . ($h2 ? 'yes' : 'no');
        throw new \Exception($msg);
    }

    throw new \Exception("Erro ao atualizar o banco. HTTP: {$httpCode} | Resposta: {$response}");
}

    public function createDatabase($name, $size, $databaseInstanceId, $isOnBackup = 1, $isActive = 1)
    {
        $sessionToken = $this->initSession();

        $data = [
            "input" => [
                "name" => $name,
                "size" => $size,
                "databaseinstances_id" => $databaseInstanceId,
                "is_onbackup" => $isOnBackup,
                "is_active" => $isActive
            ]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->glpi_url . 'Database/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Session-Token: ' . $sessionToken,
            'App-Token: ' . $this->app_token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception("Erro ao criar o banco de dados. Código HTTP: $httpCode | Resposta: $response");
        }
    }
}
