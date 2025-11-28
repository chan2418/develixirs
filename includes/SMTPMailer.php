<?php
require_once __DIR__ . '/smtp_config.php';

class SMTPMailer {
    private $socket;

    public function send($to, $subject, $message) {
        $this->socket = fsockopen('ssl://' . SMTP_HOST, 465, $errno, $errstr, 15);

        if (!$this->socket) {
            return "Error connecting to SMTP server: $errstr ($errno)";
        }

        $this->serverCmd(""); // Read initial greeting
        $this->serverCmd("EHLO " . gethostname());
        $this->serverCmd("AUTH LOGIN");
        $this->serverCmd(base64_encode(SMTP_USER));
        $this->serverCmd(base64_encode(SMTP_PASS));
        $this->serverCmd("MAIL FROM: <" . SMTP_USER . ">");
        $this->serverCmd("RCPT TO: <$to>");
        $this->serverCmd("DATA");

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $this->serverCmd($headers . "\r\n" . $message . "\r\n.");
        $this->serverCmd("QUIT");

        fclose($this->socket);
        return true;
    }

    private function serverCmd($cmd) {
        if (!empty($cmd)) {
            fputs($this->socket, $cmd . "\r\n");
        }
        
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }
}
?>
