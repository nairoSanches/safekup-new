<?php

function enviar_email_log_backup($bd_nome_usuario,$app_nome,$assunto,$data,$msg,$backup_origem){

  require_once '../../vendor/autoload.php';
  include ('../conexao/conexao.php');

  $log_anexo = "";

  if($backup_origem == "MANUAL"){
    $log_anexo = "/var/www/html/safekup/log/$data/$bd_nome_usuario.txt";

  } else {
    $log_anexo = "/var/www/html/safekup/log/$data.zip";
  }


  $consulta = mysqli_query($conexao,"SELECT * FROM smtp");
  $email = mysqli_fetch_array($consulta);
  $smtp = $email['smtp_endereco'];
  $porta = $email['smtp_porta'];
  $username = $email['smtp_email_admin'];
  $senha = base64_decode($email['smtp_senha']);

  // Criando o transporte
  $transport = (new Swift_SmtpTransport($smtp, $porta, 'tls'))->setUsername($username)->setPassword($senha);

  // Crie o Mailer usando o seu transporte criado
  $mailer = new Swift_Mailer($transport);

  // Criando a mensagem
  $message = (new Swift_Message($assunto.$data))->setFrom([$username => 'Suporte safekup'])->setTo([$username])->setBody($msg, 'text/html');

  // Anexando arquivo de log
  $message -> attach (Swift_Attachment::fromPath($log_anexo));

  // Enviando email
  $result = $mailer->send($message);

}

function writeLog($message) {
  $logFile = '/opt/lampp/htdocs/safekup/php/backup/backup.log';
  $timestamp = date('Y-m-d H:i:s');
  $message = "[$timestamp] $message\n";
  file_put_contents($logFile, $message, FILE_APPEND);
}

function logAndDisplay($message) {
  writeLog($message);
  echo "$message\n";
}

?>
