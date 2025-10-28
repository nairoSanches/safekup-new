<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('/opt/lampp/htdocs/safekup/php/conexao/conexao.php');
require_once('/opt/lampp/htdocs/safekup/php/include/encryption.inc.php');
require_once('/opt/lampp/htdocs/safekup/php/backup/funcoesPhp.php');

$encryption = new Encryption();

$argBD = [
    [13, 'cme'],
    [13, 'cmsg'],
    [13, 'flexibilizacacao'],
    [13, 'gases_medicinais'],
    [13, 'laboratorio'],
    [13, 'nutricao'],
    [13, 'pagamento_refeicoes'],
    [13, 'patologia'],
    [13, 'prescricao'],
    [13, 'public'],
    [13, 'rbac'],
    [13, 'spp'],
    [13, 'tocoginecologia'],
    [48, 'public']
];


foreach ($argBD as [$bd_id, $schema]) {
    $sql = "
        SELECT 
            A.bd_id,
            A.bd_nome_usuario,
            A.bd_ip,
            A.bd_porta,
            B.restore_ip,
            B.restore_user,
            B.restore_senha_acesso,
            C.tipo_nome
        FROM db_management A
        JOIN restores B ON A.bd_id_restore = B.restore_id
        JOIN tipo C ON A.bd_tipo = C.tipo_id
        WHERE A.bd_id = $bd_id
    ";

    $result = mysqli_query($conexao, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        logAndDisplay("Banco ID $bd_id não encontrado.");
        continue;
    }

    $db = mysqli_fetch_assoc($result);
    $tipo = $db['tipo_nome'];
    $nome_base = $db['bd_nome_usuario'];
    $origem_ip = $db['bd_ip'];
    $backup_dir = "/DBbackup/{$origem_ip}/{$tipo}/{$nome_base}";

    $files = glob("$backup_dir/*.zst");
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    $arquivo_backup = $files[0] ?? null;

    if (!$arquivo_backup || !file_exists($arquivo_backup)) {
        logAndDisplay("Backup não encontrado para $nome_base.");
        continue;
    }

    $pass = $encryption->decrypt($db['restore_senha_acesso']);
    $usuario = $db['restore_user'];
    $host = $db['restore_ip'];
    $schema_escaped = escapeshellarg($schema);
    $inicio = time();

    $banco_destino = $nome_base;
    $banco_temp = "temp_{$nome_base}";
    logAndDisplay("============================================================================" );
    logAndDisplay("===> Iniciando replicação do schema '$schema' para banco '$banco_destino'");

    // Criação banco temporário
    $kill_connections = "
    SELECT pg_terminate_backend(pid)
    FROM pg_stat_activity
    WHERE datname = '$banco_temp' AND pid <> pg_backend_pid();
";

    $kill_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d postgres -c \"$kill_connections\" 2>&1";
    exec($kill_cmd, $outKill, $retKill);
    logAndDisplay(" XXXXXXXXXX Encerrando conexões no banco temporário XXXXXXXXXX");
    // logAndDisplay($kill_cmd);
    // logAndDisplay($outKill);
    //logAndDisplay($retKill);
 
    // Drop banco temporário (forçado após encerramento)
    $drop_temp_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d postgres -c 'DROP DATABASE IF EXISTS \"$banco_temp\"' 2>&1";
    exec($drop_temp_cmd, $outDrop, $retDrop);
    logAndDisplay(" X Drop banco temporário:");
    // logAndDisplay($drop_temp_cmd);
    // logAndDisplay($outDrop);
    // logAndDisplay($retDrop);

    // Cria banco temporário
    $create_temp_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d postgres -c 'CREATE DATABASE \"$banco_temp\" OWNER $usuario' 2>&1";

    exec($create_temp_cmd, $outCreate, $retCreate);
    logAndDisplay("# Create banco temporário:");
    // logAndDisplay($create_temp_cmd);
    // logAndDisplay($outCreate);
    //  logAndDisplay($retCreate);
    // logAndDisplay("Ajustando owner do schema 'public' no banco temp:");


    $alter_schema_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d \"$banco_temp\" -c 'ALTER SCHEMA public OWNER TO $usuario'";
    exec($alter_schema_cmd, $outAlter, $retAlter);

    //logAndDisplay($alter_schema_cmd);
    // logAndDisplay($outAlter);
    //  logAndDisplay($retAlter);

    if ($retAlter !== 0) {
        logAndDisplay("@@@@ Falha ao ajustar o owner do schema no banco temporário. Abortando.");
        continue;
    }

    if ($retCreate !== 0) {
        logAndDisplay("@@@@ Falha ao criar banco temporário $banco_temp.");
        continue;
    }

    // Caminhos temporários
    $lista_objetos = "/tmp/safekup-tmp/restore_{$schema}_list.txt";
    $lista_filtrada = "/tmp/safekup-tmp/restore_{$schema}_filtered.txt";

    //Gerar a lista de objetos
    $generate_list_cmd = "zstd -d -c " . escapeshellarg($arquivo_backup) .
        " | pg_restore -l > " . escapeshellarg($lista_objetos);
    exec($generate_list_cmd, $outList, $retList);

    if ($retList !== 0) {
        logAndDisplay("@@@@ Erro ao gerar lista de objetos do dump.");
        continue;
    }

    //Remover USER MAPPING da lista
    $conteudo = file_get_contents($lista_objetos);
    $linhas_filtradas = array_filter(explode("\n", $conteudo), function ($linha) {
        return stripos($linha, 'USER MAPPING') === false;
    });
    file_put_contents($lista_filtrada, implode("\n", $linhas_filtradas));

    //Fazer o restore usando --use-list
    $restore_cmd = "zstd -d -c " . escapeshellarg($arquivo_backup) . " | " .
        "PGPASSWORD='$pass' pg_restore --no-owner --no-privileges " .
        "--use-list=" . escapeshellarg($lista_filtrada) . " -U $usuario -h $host -d \"$banco_temp\" 2>&1";

    logAndDisplay("Restaurando backup em banco temporário $banco_temp...");
    exec($restore_cmd, $outRestore, $retRestore);

    if ($retRestore !== 0) {
        logAndDisplay("@@@@ Erro ao restaurar no banco temporário: $banco_temp");
        continue;
    }

    // Exportar apenas o schema do banco temporário
    $dump_schema_cmd = "PGPASSWORD='$pass' pg_dump --no-owner --no-privileges -n $schema -U $usuario -h $host -d \"$banco_temp\" -Fc -f /tmp/safekup-tmp/schema_$schema.dump 2>&1";
    exec($dump_schema_cmd, $outDump, $retDump);

    if ($retDump !== 0 || !file_exists("/tmp/safekup-tmp/schema_$schema.dump")) {
        logAndDisplay("@@@@ Falha ao gerar dump do schema '$schema' do banco temporário.");
        continue;
    }

    // Restore do schema no banco real (destino)
    // Drop do schema antigo no banco real
    $drop_schema_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d \"$banco_destino\" -c 'DROP SCHEMA IF EXISTS \"$schema\" CASCADE' 2>&1";
    exec($drop_schema_cmd, $outDropSchema, $retDropSchema);
    logAndDisplay(" Drop schema atual do banco destino:");
    //logAndDisplay($drop_schema_cmd);
    //logAndDisplay($outDropSchema);
    //logAndDisplay($retDropSchema);

    if ($retDropSchema !== 0) {
        logAndDisplay("@@@@ Erro ao dropar o schema '$schema' do banco real.");
        continue;
    }
    $create_schema_cmd = "PGPASSWORD='$pass' psql -U $usuario -h $host -d \"$banco_destino\" -c 'CREATE SCHEMA \"$schema\" AUTHORIZATION $usuario' 2>&1";
    exec($create_schema_cmd, $outCreateSchema, $retCreateSchema);
    logAndDisplay(" Recriando schema '$schema' antes do restore:");
    //logAndDisplay($create_schema_cmd);
    //logAndDisplay($outCreateSchema);
    logAndDisplay("Exit code: $retCreateSchema");

    if ($retCreateSchema !== 0) {
        logAndDisplay("@@@@ Falha ao recriar o schema '$schema'. Abortando.");
        continue;
    }

    $restore_real_cmd = "PGPASSWORD='$pass' pg_restore --no-owner --no-privileges --schema=$schema -U $usuario -h $host -d \"$banco_destino\" /tmp/safekup-tmp/schema_$schema.dump 2>&1";
    logAndDisplay("Aplicando dump do schema '$schema' no banco '$banco_destino'");
    exec($restore_real_cmd, $outFinal, $retFinal);
    logAndDisplay("Exit code: $retFinal");

    if ($retFinal !== 0) {
        $warnings = implode("\n", $outFinal);

        $ignorarErros = (
            str_contains($warnings, 'operator class') ||
            str_contains($warnings, 'violates foreign key constraint') ||
            str_contains($warnings, 'errors ignored on restore')
        );

        $temErroGrave = preg_match('/pg_restore: error: (?!.*(operator class|violates foreign key constraint))/', $warnings);

        if ($ignorarErros && !$temErroGrave) {
            logAndDisplay("!!!!!! Erros toleráveis ignorados no restore do schema '$schema'.");
        } else {
            logAndDisplay("@@@@ Falha ao restaurar schema '$schema' no banco real.");
            continue;
        }
    }

    // Remoção do banco temporário
    $drop_temp_cmd2 = "PGPASSWORD='$pass' psql -U $usuario -h $host -d postgres -c 'DROP DATABASE IF EXISTS \"$banco_temp\"' 2>&1";
    exec($drop_temp_cmd2);

    $fim = time();
    $tempo = $fim - $inicio;
    $tamanho = filesize($arquivo_backup);

    mysqli_query($conexao, "
        INSERT INTO historico_restores 
        (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_restore, tempo_decorrido, tamanho_arquivo, descricao)
        VALUES (
            '" . mysqli_real_escape_string($conexao, $nome_base) . "',
            '" . mysqli_real_escape_string($conexao, $host) . "',
            $bd_id,
            NOW(),
            'OK',
            '" . mysqli_real_escape_string($conexao, $arquivo_backup) . "',
            $tempo,
            $tamanho,
            'replicacao_com_temp'
        )
    ");

    logAndDisplay("### Replicação do schema '$schema' concluída com sucesso!");
}

mysqli_close($conexao);
?>