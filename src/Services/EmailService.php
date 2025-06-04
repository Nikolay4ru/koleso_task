<?php
namespace App\Services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailService {
    private $mailer;
    
    public function __construct($config) {
        $this->mailer = new PHPMailer(true);
        
        // Настройки SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $config['smtp_host'];
        $this->mailer->SMTPAuth = true;
       // $this->mailer->SMTPDebug  = 2;
        $this->mailer->Username = $config['smtp_username'];
        $this->mailer->Password = $config['smtp_password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $config['smtp_port'];
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
        
        $this->mailer->setFrom($config['from_email'], $config['from_name']);
    }
    
    public function send($to, $subject, $body) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->getEmailTemplate($subject, $body);
            $this->mailer->AltBody = strip_tags($body);
            
            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEmailTemplate($title, $content) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$title}</h2>
                </div>
                <div class='content'>
                    <p>{$content}</p>
                </div>
                <div class='footer'>
                    <p>Система управления задачами</p>
                </div>
            </div>
        </body>
        </html>";
    }
}