<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos necessários
include ('/opt/lampp/htdocs/safekup/php/conexao/conexao.php');
include ('/opt/lampp/htdocs/safekup/php/backup/funcoesPhp.php');
require_once ('/opt/lampp/htdocs/safekup/php/include/encryption.inc.php');
require_once ('/opt/lampp/htdocs/safekup/php/include/emailsender.inc.php');
require_once ('/opt/lampp/htdocs/safekup/php/include/sendticketglpi.inc.php');
require_once ('/opt/lampp/htdocs/safekup/vendor/autoload.php');

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

// Consultar as informações do banco de dados
$sql = "SELECT A.bd_id, A.bd_nome_usuario, A.bd_login, A.bd_senha, A.bd_ip, A.bd_porta, A.bd_tipo, A.bd_hora_backup, A.bd_backup_ativo, B.tipo_nome, A.bd_ssh, A.bd_container, A.bd_recorrencia 
        FROM db_management A, tipo B
        WHERE A.bd_backup_ativo = 'SIM' AND A.bd_tipo = B.tipo_id AND A.$data_aux = 1";

$result = mysqli_query($conexao, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bd_hora_backup = intval($row['bd_hora_backup']);
        $bd_recorrencia = intval($row['bd_recorrencia']);

        $executar_backup = false;

        // Verificação da hora de backup e recorrência
        if ($bd_recorrencia == 1 && $hora_aux == $bd_hora_backup) {
            $executar_backup = true;
        } elseif ($bd_recorrencia == 2 && ($hora_aux == $bd_hora_backup || $hora_aux == ($bd_hora_backup + 6) % 24)) {
            $executar_backup = true;
        } elseif ($bd_recorrencia == 3 && ($hora_aux == $bd_hora_backup || $hora_aux == ($bd_hora_backup + 6) % 24 || $hora_aux == ($bd_hora_backup + 12) % 24)) {
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
            $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

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

            // Comando mysqldump ou pg_dump
            if ($nameTypeDB == "MYSQL") {
                // Comando MySQL
                if ($ssh_tunnel == true) {
                    $dump_command = "sshpass -p '$ssh_pass' ssh -o StrictHostKeyChecking=no $ssh_user@$ssh_ip "
                        . "\"echo '$ssh_pass' | sudo -S docker exec -i $ssh_container sh -c 'mysqldump -h $db_host -P $db_port -u root -p'$db_password' $db_name' > /tmp/mysqldump.sql\" && "
                        . "sshpass -p '$ssh_pass' scp $ssh_user@$ssh_ip:/tmp/mysqldump.sql $backup_file 2>&1";
                } else {
                    $dump_command = "/opt/lampp/bin/mysqldump -h $db_host -P $db_port -u $db_user -p'$db_password' $db_name > $backup_file 2>&1";
                }
            } else if ($nameTypeDB == "POSTGRESQL") {
                // Comando PostgreSQL
                if ($ssh_tunnel == true) {
                    $dump_command = "sshpass -p '$ssh_pass' ssh -o StrictHostKeyChecking=no $ssh_user@$ssh_ip "
                        . "\"echo '$ssh_pass' | sudo -S docker exec -i $ssh_container sh -c '/usr/bin/pg_dump -h $db_host -p $db_port -U $db_user $db_name' > $backup_file 2>&1";
                } else {
                    
                    $dump_command = "PGPASSWORD=\"$db_password\" /usr/bin/pg_dump -h $db_host -p $db_port -U $db_user \"$db_name\" > \"$backup_file\" 2>&1";
                }
              
            }         

            // Executar o comando mysqldump ou pg_dump e capturar exceções
            exec($dump_command, $output, $return_var);


            $tempo_fim = time();
            $tempo_decorrido = $tempo_fim - $tempo_inicio;

            // Verificar se o dump foi bem-sucedido
            if ($return_var === 0 and file_exists($backup_file)) {
                // Compactar o arquivo de backup
                $zip_file = $backup_file . '.zip';
                $zip_command = "zip $zip_file $backup_file";
                exec($zip_command, $output_zip, $return_var_zip);

                // Verificar se a compactação foi bem-sucedida
                if ($return_var_zip === 0) {
                    // Remover o arquivo de backup original
                    unlink($backup_file);

                    // Opcional: Limpar arquivos de backup antigos, mantenha apenas os X mais recentes
                    $files = glob("$backup_dir/*.zip");
                    usort($files, function ($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });

                    $keep_files = 50; // Número de arquivos de backup a manter
                    for ($i = $keep_files; $i < count($files); $i++) {
                        unlink($files[$i]);
                    }

                    // Registro de sucesso na tabela de histórico
                    $data_execucao = date('Y-m-d H:i:s');
                    $status = 'OK';
                    $arquivo_backup = $zip_file;

                    $tamanho_arquivo = filesize($arquivo_backup);

                   /* $insert_sql = "INSERT INTO historico_dumps (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_backup)
                                  VALUES ('$bd_nome_usuario', '$db_host', {$row['bd_id']}, '$data_execucao', '$status', '$arquivo_backup')";*/

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

                    logAndDisplay("Backup realizado com sucesso em $zip_file");

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
                        $mailer->Body = "O backup do banco de dados $db_name foi realizado com sucesso no host $db_host.\nArquivo: $zip_file";

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

              /*  $insert_sql = "INSERT INTO historico_dumps (bd_nome_usuario, bd_ip, bd_id, data_execucao, status)
                              VALUES ('$bd_nome_usuario', '$db_host', {$row['bd_id']}, '$data_execucao', '$status')";

                if (mysqli_query($conexao, $insert_sql)) {
                    echo "Registro de erro inserido com sucesso na tabela de histórico.\n";
                } else {
                    echo "Erro ao inserir registro de erro na tabela de histórico: " . mysqli_error($conexao) . "\n";
                }*/

                logAndDisplay("Erro ao realizar o backup");
                logAndDisplay("Saída do comando:");
                logAndDisplay(implode("\n", $output));

                // Enviar email de erro de backup
                $db_host = ($db_host ? $db_host : 'N&atilde;o Informado');

                $assunto = "Erro ao Realizar Backup";
                $mensagem = "Erro ao Realizar Backup <br><br><strong>Banco de dados: $db_name</strong><br><br>host: <span style='color:red; font-weight:bold;'>$db_host</span> $nameTypeDB";


                $emailSender = new EmailSender($smtp, $porta, $username, $senha);
                foreach ($emailDestinatarios as $destinatario) {
                    $emailSender->enviarEmail($destinatario, $assunto, $mensagem);
                }


                try {
                    $glpiClient = new GlpiClient();
                    $response = $glpiClient->openTicket($assunto, $mensagem);
                    print_r($response);
                } catch (Exception $e) {
                    echo 'Exceção capturada: ', $e->getMessage(), "\n";
                }

                $insert_sql = "INSERT INTO historico_dumps (bd_nome_usuario, bd_ip, bd_id, data_execucao, status, arquivo_backup, tempo_decorrido, tamanho_arquivo, descricao)
                VALUES ('$bd_nome_usuario', '$db_host', {$row['bd_id']}, '$data_execucao', '$status', '$arquivo_backup', $tempo_decorrido, '$tamanho_arquivo', '$response')";              
  if (mysqli_query($conexao, $insert_sql)) {
    echo "Registro de erro inserido com sucesso na tabela de histórico.\n";
} else {
    echo "Erro ao inserir registro de erro na tabela de histórico: " . mysqli_error($conexao) . "\n";
}

            }

        }
    }
} else {
    echo "Nenhuma base de dados encontrada para Dump\n";
}
?>