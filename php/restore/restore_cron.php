<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
include('/opt/lampp/htdocs/safekup/php/conexao/conexao.php');
include('/opt/lampp/htdocs/safekup/php/backup/funcoesPhp.php');
require_once('/opt/lampp/htdocs/safekup/php/include/encryption.inc.php');
require_once('/opt/lampp/htdocs/safekup/php/include/emailsender.inc.php');
require_once('/opt/lampp/htdocs/safekup/php/include/sendticketglpi.inc.php');
require_once('/opt/lampp/htdocs/safekup/vendor/autoload.php');

$encryption = new Encryption();

// Função para obter a lista de bancos para restore
function getRestoreList($conexao)
{
    $query = "
        SELECT A.bd_id, A.bd_ip, B.restore_nome, B.restore_ip, B.restore_user, B.restore_senha_acesso, 
               A.bd_nome_usuario, A.bd_servidor_backup, A.bd_tipo, C.tipo_nome
        FROM db_management A
        INNER JOIN restores B ON A.bd_id_restore = B.restore_id
        INNER JOIN tipo C ON A.bd_tipo = C.tipo_id
        WHERE A.bd_backup_ativo = 'SIM' AND A.bd_id_restore > 0 AND A.bd_tipo IN (1, 2)
    ";
    $result = mysqli_query($conexao, $query);
    if (!$result) {
        die("ERRO: Não foi possível executar a consulta\n" . mysqli_error($conexao));
    }
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Obter arquivo mais recente
function getLatestBackupFile($backupDir)
{
    $files = glob("$backupDir/*.zst");
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return $files[0] ?? null;
}

// Descompactar arquivo .zst
function decompressZstd($zstFile)
{
    $outputFile = preg_replace('/\.zst$/', '', $zstFile);
    $escapedZstFile = escapeshellarg($zstFile);

    // Comando com log detalhado
    $command = "unzstd -f $escapedZstFile 2>&1";
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        logAndDisplay("❌ Falha ao descompactar: $zstFile");
        logAndDisplay("Comando executado: $command");
        logAndDisplay("Código de retorno: $returnVar");

        if (!empty($output)) {
            logAndDisplay("Saída do unzstd:");
            foreach ($output as $line) {
                logAndDisplay($line);
            }
        } else {
            logAndDisplay("Nenhuma saída retornada pelo unzstd.");
        }

        return false;
    }

    return $outputFile;
}


// Inserir histórico
function inserirHistoricoRestore($conexao, $bd_nome_usuario, $bd_ip, $bd_id, $status, $arquivo_restore, $tempo_decorrido, $tamanho_arquivo, $descricao = null)
{
    $bd_nome_usuario = mysqli_real_escape_string($conexao, $bd_nome_usuario);
    $bd_ip = mysqli_real_escape_string($conexao, $bd_ip);
    $status = mysqli_real_escape_string($conexao, $status);
    $arquivo_restore = mysqli_real_escape_string($conexao, $arquivo_restore);
    $descricao = $descricao ? mysqli_real_escape_string($conexao, $descricao) : null;

    $sql = "
        INSERT INTO historico_restores 
        (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_restore, tempo_decorrido, tamanho_arquivo, descricao) 
        VALUES (
            '$bd_nome_usuario', 
            '$bd_ip', 
            $bd_id, 
            NOW(), 
            '$status', 
            '$arquivo_restore', 
            $tempo_decorrido, 
            $tamanho_arquivo, 
            " . ($descricao ? "'$descricao'" : "NULL") . "
        )
    ";

    mysqli_query($conexao, $sql);
}

// Processar restore
$restoreList = getRestoreList($conexao);

foreach ($restoreList as $db) {
    $backupDir = "/DBbackup/{$db['bd_ip']}/{$db['tipo_nome']}/{$db['bd_nome_usuario']}";
    $latestBackup = getLatestBackupFile($backupDir);

    if ($latestBackup) {
        $restoreDate = date('Y_m_d_H-i-s');
        $restoreDbName = "RESTO_{$db['bd_nome_usuario']}";
        $startTime = microtime(true);

        logAndDisplay("Iniciando restore da base: {$db['bd_nome_usuario']} às $restoreDate");
        logAndDisplay("Backup: $latestBackup");

        $tempSqlFile = decompressZstd($latestBackup);

        if (!$tempSqlFile || !file_exists($tempSqlFile)) {

            inserirHistoricoRestore(
                $conexao,
                $db['bd_nome_usuario'],
                $db['bd_ip'],
                $db['bd_id'],
                'FALHA',
                $latestBackup,
                0,
                0,
                "Falha ao descompactar arquivo .zst"
            );


            logAndDisplay("**************");
            logAndDisplay("ERRO durante o restore da base: {$db['bd_nome_usuario']}");
            logAndDisplay("Arquivo de backup: $latestBackup");
            logAndDisplay("Arquivo de descompatado: $tempSqlFile");
            logAndDisplay("Motivo: Falha ao descompactar arquivo .zst");
            logAndDisplay("**************");

            continue;
        }


        if ((int) $db['bd_tipo'] === 1) { // MySQL
            $restoreConn = mysqli_connect($db['restore_ip'], $db['restore_user'], $encryption->decrypt($db['restore_senha_acesso']));
            if ($restoreConn) {
                mysqli_query($restoreConn, "DROP DATABASE IF EXISTS `$restoreDbName`");
                if (mysqli_query($restoreConn, "CREATE DATABASE `$restoreDbName`")) {
                    $restoreCommand = "mysql -h {$db['restore_ip']} -u {$db['restore_user']} -p'{$encryption->decrypt($db['restore_senha_acesso'])}' \"$restoreDbName\" < \"$tempSqlFile\" 2>&1";
                    exec($restoreCommand, $output, $returnVarRestore);
                }
            }
        } elseif ((int) $db['bd_tipo'] === 2) { // PostgreSQL
            $pass = $encryption->decrypt($db['restore_senha_acesso']);
            $dropCmd = "PGPASSWORD='$pass' psql -h {$db['restore_ip']} -U {$db['restore_user']} -d postgres -c 'DROP DATABASE IF EXISTS \"$restoreDbName\";' 2>&1";
            exec($dropCmd, $outputDrop, $retDrop);

            if ($retDrop === 0) {
                $createCmd = "PGPASSWORD='$pass' psql -h {$db['restore_ip']} -U {$db['restore_user']} -d postgres -c 'CREATE DATABASE \"$restoreDbName\";' 2>&1";
                exec($createCmd, $outputCreate, $retCreate);

                if ($retCreate === 0) {
                    $restoreCommand = "PGPASSWORD='$pass' pg_restore -h {$db['restore_ip']} -U {$db['restore_user']} -d \"$restoreDbName\" \"$tempSqlFile\" 2>&1";
                    exec($restoreCommand, $outputRestore, $returnVarRestore);
                }
            }
            exec($dropCmd, $outputDrop, $retDrop);
        }

        $horaFim = date('Y-m-d H:i:s');

        if (isset($returnVarRestore) && $returnVarRestore === 0) {
            $tempoDecorrido = round(microtime(true) - $startTime, 2);
            $tamanhoArquivo = filesize($tempSqlFile);
            inserirHistoricoRestore($conexao, $db['bd_nome_usuario'], $db['bd_ip'], $db['bd_id'], 'OK', $tempSqlFile, $tempoDecorrido, $tamanhoArquivo);

            logAndDisplay("Restore concluído com sucesso para {$db['bd_nome_usuario']}.");
            logAndDisplay("Tempo decorrido: $tempoDecorrido segundos.");
            logAndDisplay("Restore executado com sucesso em $horaFim");
            logAndDisplay("Restore finalizado em $horaFim");

            if (unlink($tempSqlFile)) {
                logAndDisplay("Arquivo temporário $tempSqlFile removido.");
            } else {
                logAndDisplay("ERRO: Não foi possível remover $tempSqlFile após restore.");
            }
        } else {
            logAndDisplay("ERRO: Falha no restore do banco $restoreDbName.");
            logAndDisplay("Restore encerrado com erro em $horaFim");
        }

    } else {
        logAndDisplay("ERRO: Nenhum backup encontrado para {$db['bd_nome_usuario']}");
    }
}

mysqli_close($conexao);
?>