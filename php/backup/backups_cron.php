<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
include('/opt/lampp/htdocs/safekup/php/conexao/conexao.php');
include('/opt/lampp/htdocs/safekup/php/backup/funcoesPhp.php');
require_once('/opt/lampp/htdocs/safekup/php/include/encryption.inc.php');
require_once('/opt/lampp/htdocs/safekup/php/include/emailsender.inc.php');
require_once('/opt/lampp/htdocs/safekup/php/include/sendticketglpiSingle.inc.php');
require_once('/opt/lampp/htdocs/safekup/vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$encryption = new Encryption();
$glpiClient = new GlpiClient();

$smtp_email_admin = 'safekup.hu-ufsc@ebserh.gov.br';
$emailDestinatarios = [
    'nairo.sanches@ebserh.gov.br'/*,
'amilton.figueredo@ebserh.gov.br'*/
];



// Buscar configurações de SMTP no banco de dados
$sql_smtp = "SELECT * FROM smtp ";
$result_smtp = mysqli_query($conexao, $sql_smtp);

if ($result_smtp && mysqli_num_rows($result_smtp) > 0) {
    $smtp_config = mysqli_fetch_assoc($result_smtp);

    // Configurações de email
    $smtp = $smtp_config['smtp_endereco'];
    $porta = $smtp_config['smtp_porta'];
    $username = $smtp_email_admin;
    $senha = base64_decode($smtp_config['smtp_senha']);
} else {
    die("Erro ao buscar configurações de SMTP no banco de dados.");
}

// Inicialização das variáveis de tempo
$tempo_inicio = 0;
$tempo_fim = 0;
$tempo_decorrido = 0;
$tamanho_arquivo = 0;
$arquivo_backup = '';

// Pegando dia da semana
$dia_semana = date("D");
$data_aux = "";

switch ($dia_semana) {
    case 'Sun':
        $data_aux = "bd_dia_0";
        break;
    case 'Mon':
        $data_aux = "bd_dia_1";
        break;
    case 'Tue':
        $data_aux = "bd_dia_2";
        break;
    case 'Wed':
        $data_aux = "bd_dia_3";
        break;
    case 'Thu':
        $data_aux = "bd_dia_4";
        break;
    case 'Fri':
        $data_aux = "bd_dia_5";
        break;
    case 'Sat':
        $data_aux = "bd_dia_6";
        break;
}

// Pegando hora do backup
$hora = date("H");
$hora_aux = intval($hora);
$intervaloEntreBackups = 30;
$contador = 0;

// Consultar as informações do banco de dados

$sql = "
SELECT 
    A.bd_id,
    A.bd_nome_usuario,
    A.bd_login,
    A.bd_senha,
    A.bd_ip,
    A.bd_porta,
    A.bd_tipo,
    A.bd_hora_backup,
  
    LPAD(
        CASE 
            WHEN A.bd_tipo = 1 THEN 0 + (MOD(A.bd_id, 7) * 7)  
            WHEN A.bd_tipo = 2 THEN 1 + (MOD(A.bd_id, 7) * 7) 
            WHEN A.bd_tipo = 3 THEN 2 + (MOD(A.bd_id, 7) * 7)  
            ELSE 9
        END
    , 2, '0') AS bd_hora_backup_intercalada,
    
    A.bd_backup_ativo,
    B.tipo_nome,
    A.bd_ssh,
    A.bd_container,
    A.bd_recorrencia,
    A.bd_id_glpi

FROM 
    db_management A
JOIN 
    tipo B ON A.bd_tipo = B.tipo_id
WHERE 
    A.$data_aux = 1  AND    
    A.bd_backup_ativo = 'SIM' 
 
ORDER BY 
    bd_hora_backup_intercalada, A.bd_tipo, A.bd_id;
";


$result = mysqli_query($conexao, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bd_hora_backup = intval($row['bd_hora_backup']);
        $bd_recorrencia = intval($row['bd_recorrencia']);

        $executar_backup = false;

        if ($bd_recorrencia == 1 && $hora_aux == 3) {
            $executar_backup = true;
        } elseif ($bd_recorrencia == 2 && in_array($hora_aux, [6, 9, 15])) {
            $executar_backup = true;
        } elseif ($bd_recorrencia == 3 && in_array($hora_aux, [6, 9, 15, 18])) {
            $executar_backup = true;
        } elseif ($bd_recorrencia == 4 && in_array($hora_aux, [3, 6, 9, 15, 18, 21])) {
            $executar_backup = true;
        }

 

        if ($executar_backup) {
            $nameTypeDB = $row['tipo_nome'];
            $bd_nome_usuario = $row['bd_nome_usuario'];
            $db_user = $row['bd_login'];
            $db_password = str_replace("\007", "", $encryption->decrypt($row['bd_senha']));
            $db_host = $row['bd_ip'];
            $db_port = $row['bd_porta'];
            $db_name = $row['bd_nome_usuario'];
            $db_ssh = $row['bd_ssh'];
            $ssh_container = $row['bd_container'];
            $ssh_tunnel = false;

            // Diretório de backup conforme a estrutura desejada
            $backup_dir = "/DBbackup/$db_host/$nameTypeDB/$db_name";
            if (!is_dir($backup_dir)) {
                if (!mkdir($backup_dir, 0777, true)) {
                    die("Erro ao criar o diretório: $backup_dir");
                }
                // Ajustando permissões do diretório
                chmod($backup_dir, 0777);
            }

            // Nome do arquivo de backup
            $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.zst';

            // Captura o tempo de início
            $tempo_inicio = time();

            // Se for necessário usar SSH
            if ($db_ssh > 0) {
                $ssh_sql = "SELECT * FROM ssh WHERE ssh_id = $db_ssh";
                $ssh_result = mysqli_query($conexao, $ssh_sql);

                if ($ssh_result->num_rows > 0) {
                    $ssh_row = $ssh_result->fetch_assoc();
                    $ssh_ip = $ssh_row['ssh_ip'];
                    $ssh_user = $ssh_row['ssh_user'];
                    $ssh_pass = $ssh_row['ssh_pass'];

                    $ssh_tunnel = true;
                } else {
                    $db_host = ($db_host ? $db_host : 'Não Informado');
                    // Enviar email de erro de credenciais SSH
                    $assunto = "Erro de Credenciais SSH";
                    $mensagem = "Erro ao Realizar Backup <br><strong>Banco de dados: $db_name</strong><br>host:<span style='color:red; font-weight:bold;'>$db_host</span>";


                    $emailSender = new EmailSender($smtp, $porta, $username, $senha);
                    foreach ($emailDestinatarios as $destinatario) {
                        $emailSender->enviarEmail($destinatario, $assunto, $mensagem);
                    }

                    die("Erro ao obter credenciais SSH");
                }
            }

            if ($nameTypeDB == "MYSQL") {

                if ($ssh_tunnel == true) {
                    $dump_command = "sshpass -p '$ssh_pass' ssh -o StrictHostKeyChecking=no $ssh_user@$ssh_ip "
                        . "\"echo '$ssh_pass' | sudo -S docker exec -i $ssh_container sh -c 'mysqldump -h $db_host -P $db_port -u root -p'$db_password' $db_name' > /tmp/mysqldump.sql\" && "
                        . "sshpass -p '$ssh_pass' scp $ssh_user@$ssh_ip:/tmp/mysqldump.sql $backup_file 2>&1";
                } else {
                    $dump_command = "/opt/lampp/bin/mysqldump -h $db_host -P $db_port -u $db_user -p'$db_password' $db_name > $backup_file 2>&1";
                }

            } else if ($nameTypeDB == "MARIADB") {
                $dump_options = "--single-transaction --quick --compress";

                if ($ssh_tunnel == true) {
                    $dump_command = "HOME=/tmp sshpass -p '$ssh_pass' ssh -o StrictHostKeyChecking=no $ssh_user@$ssh_ip "
                        . "\"echo '$ssh_pass' | sudo -S docker exec -i $ssh_container sh -c 'mariadb-dump $dump_options -h $db_host -P $db_port -u root -p'$db_password' $db_name' > /tmp/mysqldump.sql\" && "
                        . "sshpass -p '$ssh_pass' scp $ssh_user@$ssh_ip:/tmp/mysqldump.sql $backup_file 2>&1";
                } else {
                    $dump_command = "/opt/lampp/bin/mysqldump $dump_options -h $db_host -P $db_port -u $db_user -p'$db_password' $db_name > $backup_file 2>&1";
                }
            } else if ($nameTypeDB == "POSTGRESQL") {
                $dump_options = "--no-synchronized-snapshots --encoding=UTF8 --serializable-deferrable --lock-wait-timeout=5s";
                $pg_options = "PGOPTIONS='--client-min-messages=warning --work-mem=1MB'";
                $exclude_tables = ($db_name === "cortex")  ? "--exclude-table=patologia.bk_* --exclude-table=public.bk_*"
  : "";

                if ($ssh_tunnel == true) {
                    $dump_command = "HOME=/tmp sshpass -p '$ssh_pass' ssh -o StrictHostKeyChecking=no $ssh_user@$ssh_ip "
                        . "\"echo '$ssh_pass' | sudo -S docker exec -i $ssh_container sh -c '"
                        . "env PGOPTIONS=\\\"--client-min-messages=warning --work-mem=1MB\\\" "
                        . "PGPASSWORD=\\\"$db_password\\\" "
                        . "nice -n 19 ionice -c 3 -n 7 /usr/bin/pg_dump -Fc $dump_options "
                        . "-h $db_host -p $db_port -U $db_user $db_name "
                        . "| zstd -T3 -3 -o /tmp/pgdump.dump.zst && chmod 644 /tmp/pgdump.dump.zst'\" && "
                        . "sshpass -p '$ssh_pass' scp $ssh_user@$ssh_ip:/tmp/pgdump.dump.zst $backup_file 2>&1 && "
                        . "sshpass -p '$ssh_pass' ssh $ssh_user@$ssh_ip 'rm -f /tmp/pgdump.dump.zst'";
                } else {
                    $dump_command = "env PGOPTIONS='--client-min-messages=warning --work-mem=1MB' "
                        . "PGPASSWORD=\"$db_password\" "
                        . "nice -n 19 ionice -c 3 -n 7 /usr/bin/pg_dump -Fc $dump_options $exclude_tables "
                        . "-h $db_host -p $db_port -U $db_user \"$db_name\" "
                        . "| zstd -T3 -3 -o \"$backup_file\" && chmod 644 \"$backup_file\" 2>&1";
                }
            }

            exec($dump_command, $output, $return_var);

            $tempo_fim = time();
            $tempo_decorrido = $tempo_fim - $tempo_inicio;

            if ($return_var === 0 and file_exists($backup_file)) {
                $return_var_zip = 0;
                $data_execucao = date('Y-m-d H:i:s');
                $status = 'OK';
                $arquivo_backup = $backup_file;

                $tamanho_arquivo = filesize($arquivo_backup);
                if (file_exists($backup_file)) {

                    $tamanho_arquivo = filesize($backup_file);
                    if (!empty($row['bd_id_glpi'])) {
                        $bdIdGlpi = $row['bd_id_glpi'];
                        $tamanho_mb = round($tamanho_arquivo / (1024 * 1024));
                        $data_backup = date('Y-m-d H:i:s', $tempo_fim);
                        try {
                            $resultGlpi = $glpiClient->updateDatabase($bdIdGlpi, $tamanho_mb, $data_backup);
                            echo "Banco de dados GLPI ID {$bdIdGlpi} atualizado com sucesso. Tamanho: {$tamanho_mb}MB\n";
                        } catch (Exception $e) {
                            echo "Erro ao atualizar banco no GLPI: " . $e->getMessage() . "\n";
                        }
                    }
                }
                if ($return_var_zip === 0) {
                    //unlink($backup_file);
                    $files = glob("$backup_dir/*.zst");
                    usort($files, function ($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $keep_files = 90;
                    for ($i = $keep_files; $i < count($files); $i++) {
                        unlink($files[$i]);
                    }

                    $descricao = isset($descricao) ? $descricao : '';

                    $insert_sql = "INSERT INTO historico_dumps (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_backup, tempo_decorrido, tamanho_arquivo, descricao)
              VALUES ('$bd_nome_usuario', '$db_host', {$row['bd_id']}, '$data_execucao', '$status', '$arquivo_backup', $tempo_decorrido, '$tamanho_arquivo', '$descricao')";

                    if (mysqli_query($conexao, $insert_sql)) {
                        echo "Registro inserido com sucesso na tabela de histórico.\n";
                    } else {
                        echo "Erro ao inserir registro na tabela de histórico: " . mysqli_error($conexao) . "\n";
                    }

                    // Exibir saída completa para fins de depuração
                    logAndDisplay("*************");
                    logAndDisplay("*Host: $db_host*");
                    logAndDisplay("Nome da base: $db_name");
                    logAndDisplay("Data e hora de início: " . date('Y-m-d H:i:s', $tempo_inicio));
                    logAndDisplay("Data e hora de fim: " . date('Y-m-d H:i:s', $tempo_fim));
                    logAndDisplay("Tempo decorrido (segundos): $tempo_decorrido");

                    logAndDisplay("Backup realizado com sucesso em $arquivo_backup ");

                    // Enviar email de sucesso de backup
                    $mailer = new PHPMailer(true);

                    try {
                        // Configurações do servidor SMTP
                        $mailer->isSMTP();
                        $mailer->Host = $smtp;
                        $mailer->Port = $porta;
                        $mailer->SMTPAuth = true;
                        $mailer->Username = $username;
                        $mailer->Password = $senha;
                        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

                        // Configurações de email
                        $mailer->setFrom($smtp_email_admin, 'Safekup Admin');
                        $mailer->addAddress($smtp_email_admin);
                        $mailer->Subject = "Backup Realizado com Sucesso";
                        $mailer->Body = "O backup do banco de dados $db_name foi realizado com sucesso no host $db_host.\nArquivo: $arquivo_backup ";

                        // Enviar email
                        $mailer->send();
                        echo 'Email de sucesso de backup enviado.' . "\n";
                    } catch (Exception $e) {
                        echo "Erro ao enviar email de sucesso de backup: {$mailer->ErrorInfo}\n";
                    }
                } else {
                    // Handle zip error
                    echo "Erro ao compactar o arquivo de backup.";

                    // Enviar email de erro de compactação
                    $assunto = "Erro ao Compactar Backup";
                    $mensagem = "Erro ao compactar o arquivo de backup do banco de dados $db_name no host $db_host.";

                    $emailSender = new EmailSender($smtp, $porta, $username, $senha);
                    foreach ($emailDestinatarios as $destinatario) {
                        $emailSender->enviarEmail($destinatario, $assunto, $mensagem);
                    }
                }
            } else {
                // Inserir registro de erro na tabela de histórico
                $data_execucao = date('Y-m-d H:i:s');
                $status = 'Erro';

                // Enviar email de erro de backup
                $db_host = ($db_host ? $db_host : 'N&atilde;o Informado');

                $assunto = "Erro ao Realizar Backup";
                $mensagem = "Erro ao Realizar Backup <br><br><strong>Banco de dados: $db_name</strong><br><br>host: <span style='color:red; font-weight:bold;'>$db_host</span> $nameTypeDB";


                $emailSender = new EmailSender($smtp, $porta, $username, $senha);
                foreach ($emailDestinatarios as $destinatario) {
                    $emailSender->enviarEmail($destinatario, $assunto, $mensagem);
                }

                $response = '';
                try {
                    // $glpiClient = new GlpiClient();
                    // $response = $glpiClient->openTicket($assunto, $mensagem);
                    //print_r($response);
                } catch (Exception $e) {
                    echo 'Exceção capturada: ', $e->getMessage(), "\n";
                }

                $insert_sql = "INSERT INTO historico_dumps (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_backup, tempo_decorrido, tamanho_arquivo, descricao)
                VALUES ('$bd_nome_usuario', '$db_host', {$row['bd_id']}, '$data_execucao', '$status', '$arquivo_backup', $tempo_decorrido, '0', '$response')";

                if (mysqli_query($conexao, $insert_sql)) {
                    // echo "Registro de erro inserido com sucesso na tabela de histórico.\n";
                } else {
                    // echo "Erro ao inserir registro de erro na tabela de histórico: " . mysqli_error($conexao) . "\n";
                }

            }

        }
        sleep($intervaloEntreBackups);
    }
} else {
    echo "Nenhuma base de dados encontrada para Dump\n";
}
?>