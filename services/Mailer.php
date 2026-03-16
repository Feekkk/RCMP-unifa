<?php

final class Mailer
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? self::loadConfig();
    }

    public static function loadConfig(): array
    {
        $local = __DIR__ . '/email_config.php';
        $example = __DIR__ . '/email_config.example.php';

        $cfg = file_exists($local) ? require $local : require $example;
        return is_array($cfg) ? $cfg : [];
    }

    public function sendHtml(string $toEmail, string $subject, string $html, ?string $text = null): bool
    {
        $driver = strtolower((string)($this->config['driver'] ?? 'mail'));

        if ($driver === 'smtp') {
            return $this->sendViaSmtp($toEmail, $subject, $html, $text);
        }

        return $this->sendViaMail($toEmail, $subject, $html, $text);
    }

    private function sendViaMail(string $toEmail, string $subject, string $html, ?string $text = null): bool
    {
        $fromEmail = $this->config['from']['email'] ?? 'unifa@rcmp.edu.my';
        $fromName = $this->config['from']['name'] ?? 'RCMP UniFa';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->formatAddress($fromEmail, $fromName);
        $headers[] = 'Reply-To: ' . $fromEmail;

        if ($text !== null && $text !== '') {
            $headers[] = 'X-Alt-Text: ' . $this->sanitizeHeaderValue($text);
        }

        $ok = mail($toEmail, $subject, $html, implode("\r\n", $headers));
        return (bool)$ok;
    }

    private function sendViaSmtp(string $toEmail, string $subject, string $html, ?string $text = null): bool
    {
        $libPath = __DIR__ . '/../../PHPMailer_lib/class.phpmailer.php';
        if (!file_exists($libPath)) {
            throw new RuntimeException('PHPMailer library not found at ' . $libPath);
        }

        require_once $libPath;

        if (!class_exists('PHPMailer')) {
            throw new RuntimeException('PHPMailer class not available after including library.');
        }

        /** @var PHPMailer $mail */
        $mail = new PHPMailer();

        $smtp = $this->config['smtp'] ?? [];

        $host       = $smtp['host']       ?? '';
        $port       = (int)($smtp['port'] ?? 587);
        $encryption = $smtp['encryption'] ?? 'tls';
        $username   = $smtp['username']   ?? '';
        $password   = $smtp['password']   ?? '';

        $fromEmail = $this->config['from']['email'] ?? 'unifa@rcmp.edu.my';
        $fromName  = $this->config['from']['name']  ?? 'RCMP UniFa';

        $mail->IsSMTP();
        $mail->Host     = $host;
        $mail->Port     = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = 'ssl';
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = 'tls';
        }

        $mail->From     = $fromEmail;
        $mail->FromName = $fromName;

        $mail->AddAddress($toEmail);
        $mail->IsHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text !== null && $text !== '' ? $text : strip_tags($html);

        return (bool)$mail->Send();
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = trim(preg_replace('/[\r\n]+/', ' ', $name) ?? '');
        if ($safeName === '') {
            return $email;
        }
        return sprintf('"%s" <%s>', addcslashes($safeName, "\"\\"), $email);
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', $value) ?? '');
    }
}

