<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/opt/lampp/htdocs/safekup/php/include/sendticketglpiSingle.inc.php');

 
try {
    $glpiClient = new GlpiClient();

    // ID da instância para HMG
    $databaseInstanceId = 8;

    $bancos = [
        'aahu','cpl','glpi_comunicacao','glpi_informatica_homolog','glpi_sif','numae','patrimonio'
    ];

    foreach ($bancos as $banco) {
        $nomePadronizado = "{$banco} - (Hmg)";

        $resultado = $glpiClient->createDatabase(
            $nomePadronizado,
            0,                  // tamanho
            $databaseInstanceId,
            0,                  // NÃO tem backup
            1                   // está ativo
        );

        echo "✅ Criado: {$nomePadronizado} | ID: " . $resultado['id'] . '<br>';
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . PHP_EOL;
}
