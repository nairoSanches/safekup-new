<?php

require_once '/opt/lampp/htdocs/safekup/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    
    private $mailer;
    
    public function __construct($smtpHost, $smtpPort, $username, $password) {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configuração do servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtpHost;
            $this->mailer->Port = $smtpPort;
            $this->mailer->SMTPSecure = 'tls'; // tls ou ssl
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
            
            // Configurações opcionais
            $this->mailer->setFrom($username, 'Seu Nome');
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            throw new Exception("Erro ao configurar o PHPMailer: {$e->getMessage()}");
        }
    }
    
    public function enviarEmail($destinatario, $assunto, $mensagem) {
        try {
            // Configurações de destinatário e conteúdo do email
            $this->mailer->addAddress($destinatario);
            $this->mailer->Subject = $assunto;
            $this->mailer->Body = $mensagem;
            
            // Envio do email
            if ($this->mailer->send()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new Exception("Erro ao enviar email: {$this->mailer->ErrorInfo}");
        }
    }
}

?>
