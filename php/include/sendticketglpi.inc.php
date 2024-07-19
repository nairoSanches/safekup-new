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
        $dotenvPath = __DIR__ . '/../../';

        if (!file_exists($dotenvPath . '.env')) {

            throw new Exception("Arquivo .env não encontrado no caminho: $dotenvPath");
        }

        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->load();

        $this->glpi_url = $_ENV['GLPI_URL'];
        $this->app_token = $_ENV['GLPI_API_TOKEN'];
        $this->username = $_ENV['GLPI_USERNAME'];
        $this->password = $_ENV['GLPI_PASSWORD'];
    }

    private function initSession()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->glpi_url . 'initSession/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
                'App-Token: ' . $this->app_token,
                'Content-Type: application/json'
            ),
        )
        );

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

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
                "itilcategories_id" => 1053, //N1 Categoria ITIL (verifique no GLPI)
                "urgency" => 4, // Urgência (1-5)
                "impact" => 3, // Impacto (1-5)
                "priority" => 3, // Prioridade (1-5)
                "requesttypes_id" => 1, // Tipo de solicitação (verifique no GLPI)
                "status" => 1 // Status inicial do chamado
               
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
        } else {
            $responseData = json_decode($response, true);
            if (isset($responseData['id'])) {
                return $responseData['id'];
            } else {
                throw new Exception('Falha ao criar o ticket.');
            }
        }
    }
}
/*
// Exemplo de uso
try {
    $glpiClient = new GlpiClient();

    // Criar um novo chamado
    $ticketId = $glpiClient->openTicket('Título do Chamado', 'Descrição detalhada do chamado');
    echo "Ticket criado com sucesso. ID: " . $ticketId . "\n";

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
    */

?>