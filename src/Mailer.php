<?php
namespace App;

class Mailer
{
    public function __construct(private array $cfg) {}

    public static function fromSettings(array $settings): self
    {
        return new self([
            'host'      => $settings['smtp_host']      ?? 'smtp.gmail.com',
            'port'      => (int)($settings['smtp_port'] ?? 587),
            'user'      => $settings['smtp_user']      ?? '',
            'pass'      => $settings['smtp_pass']      ?? '',
            'from'      => $settings['smtp_from']      ?? $settings['smtp_user'] ?? '',
            'from_name' => $settings['smtp_from_name'] ?? 'Bar Inventory',
        ]);
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (empty($this->cfg['user']) || empty($this->cfg['pass'])) {
            throw new \RuntimeException('SMTP not configured. Add Gmail credentials in Admin → Email.');
        }

        $boundary = bin2hex(random_bytes(8));
        $headers  = implode("\r\n", [
            "From: {$this->cfg['from_name']} <{$this->cfg['from']}>",
            "To: $to",
            "Subject: $subject",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "X-Mailer: BarInventory/1.0",
        ]);

        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $message  = "--$boundary\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n\r\n$textBody\r\n\r\n"
            . "--$boundary\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n$htmlBody\r\n\r\n"
            . "--$boundary--";

        // SMTP over TLS (port 587 STARTTLS or 465 SSL)
        $host   = $this->cfg['host'];
        $port   = $this->cfg['port'];
        $ssl    = $port === 465;
        $socket = $ssl
            ? fsockopen("ssl://$host", $port, $errno, $errstr, 15)
            : fsockopen($host, $port, $errno, $errstr, 15);

        if (!$socket) throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");

        $this->expect($socket, '220');
        $this->cmd($socket, "EHLO bar-inventory", '250');

        if (!$ssl) {
            $this->cmd($socket, "STARTTLS", '220');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            $this->cmd($socket, "EHLO bar-inventory", '250');
        }

        $this->cmd($socket, "AUTH LOGIN", '334');
        $this->cmd($socket, base64_encode($this->cfg['user']), '334');
        $this->cmd($socket, base64_encode($this->cfg['pass']), '235');
        $this->cmd($socket, "MAIL FROM:<{$this->cfg['from']}>", '250');
        $this->cmd($socket, "RCPT TO:<$to>", '250');
        $this->cmd($socket, "DATA", '354');

        fwrite($socket, "$headers\r\n\r\n$message\r\n.\r\n");
        $this->expect($socket, '250');
        $this->cmd($socket, "QUIT", '221');
        fclose($socket);
        return true;
    }

    private function cmd($sock, string $cmd, string $expect): string
    {
        fwrite($sock, "$cmd\r\n");
        return $this->expect($sock, $expect);
    }

    private function expect($sock, string $code): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if (!str_starts_with(trim($response), $code)) {
            throw new \RuntimeException("SMTP error (expected $code): $response");
        }
        return $response;
    }
}
