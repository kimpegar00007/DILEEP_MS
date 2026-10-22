<?php
// includes/Mailer.php
// Minimal outgoing-mail helper (no Composer on this project, so no PHPMailer).
//
// Driver comes from MAIL_MAILER in .env:
//   smtp  - authenticated SMTP (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION)
//   mail  - PHP's mail() through the web server's sendmail
//   log   - writes the message to the PHP error log instead of sending it (development)
//   (empty / "none") - mail is switched off
//
// send() never throws: it returns null on success or a short error string.

class Mailer {
    private string $driver;
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $encryption;   // ssl (implicit TLS, usually 465), tls (STARTTLS, usually 587) or none
    private bool   $verifyPeer;
    private string $fromAddress;
    private string $fromName;

    /** @var resource|null */
    private $socket = null;

    public function __construct() {
        $get = static function (string $key, string $default = ''): string {
            return (string) env_any([$key], $default);
        };

        $this->driver      = strtolower($get('MAIL_MAILER'));
        $this->host        = $get('MAIL_HOST');
        $this->port        = (int) $get('MAIL_PORT', '465');
        $this->username    = $get('MAIL_USERNAME');
        $this->password    = $get('MAIL_PASSWORD');
        $this->encryption  = strtolower($get('MAIL_ENCRYPTION', 'ssl'));
        $this->verifyPeer  = !in_array(strtolower($get('MAIL_VERIFY_PEER', 'true')), ['0', 'false', 'no', 'off'], true);
        $this->fromAddress = $get('MAIL_FROM_ADDRESS');
        $this->fromName    = $get('MAIL_FROM_NAME', 'DILEEP Monitoring System');
    }

    /** True when a driver is chosen and has what it needs to send. */
    public function isConfigured(): bool {
        if ($this->driver === 'log') {
            return true;
        }
        if (!filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if ($this->driver === 'smtp') {
            return $this->host !== '';
        }
        return $this->driver === 'mail';
    }

    /** Returns null when the message was handed to the mail server, otherwise an error string. */
    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): ?string {
        if (!$this->isConfigured()) {
            return 'Mail is not configured (set MAIL_MAILER and MAIL_FROM_ADDRESS in .env)';
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return 'Recipient address is not valid';
        }

        $headers = $this->buildHeaders($toEmail, $toName, $subject);
        $body    = $this->buildBody($headers['boundary'], $html, $text);

        try {
            switch ($this->driver) {
                case 'log':
                    error_log("[Mailer] (log driver) To: {$toEmail} | Subject: {$subject}\n{$text}");
                    return null;
                case 'mail':
                    $ok = @mail(
                        $this->encodeAddress($toEmail, $toName),
                        $headers['subject'],
                        $body,
                        implode("\r\n", $headers['lines'])
                    );
                    return $ok ? null : 'PHP mail() refused the message';
                default:
                    return $this->sendSmtp($toEmail, $headers, $body);
            }
        } catch (Throwable $e) {
            $this->closeSocket();
            return $e->getMessage();
        }
    }

    // =========================================================
    // Message building
    // =========================================================

    /** Keeps header values on one line so user-entered text cannot inject extra headers. */
    private function clean(string $value): string {
        return trim(preg_replace('/[\r\n]+/', ' ', $value));
    }

    private function encodeWord(string $value): string {
        $value = $this->clean($value);
        return preg_match('/^[\x20-\x7E]*$/', $value) ? $value : '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function encodeAddress(string $email, string $name): string {
        $name = $this->clean($name);
        if ($name === '') {
            return $email;
        }
        $encoded = $this->encodeWord($name);
        if ($encoded === $name) {
            $encoded = '"' . addcslashes($name, '"\\') . '"';
        }
        return $encoded . ' <' . $email . '>';
    }

    /** @return array{lines: string[], subject: string, boundary: string, messageId: string} */
    private function buildHeaders(string $toEmail, string $toName, string $subject): array {
        $boundary  = 'b_' . bin2hex(random_bytes(12));
        $domain    = substr(strrchr($this->fromAddress, '@') ?: '@localhost', 1);
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . $domain . '>';
        $encodedSubject = $this->encodeWord($subject);

        $lines = [
            'Date: ' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('r'),
            'From: ' . $this->encodeAddress($this->fromAddress, $this->fromName),
            'To: ' . $this->encodeAddress($toEmail, $toName),
            'Subject: ' . $encodedSubject,
            'Message-ID: ' . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Auto-Submitted: auto-generated',
            'X-Auto-Response-Suppress: All',
        ];

        // mail() adds To and Subject itself, so it gets the headers without them.
        if ($this->driver === 'mail') {
            $lines = array_values(array_filter($lines, static function ($l) {
                return strpos($l, 'To: ') !== 0 && strpos($l, 'Subject: ') !== 0;
            }));
        }

        return ['lines' => $lines, 'subject' => $encodedSubject, 'boundary' => $boundary, 'messageId' => $messageId];
    }

    private function buildBody(string $boundary, string $html, string $text): string {
        $part = static function (string $type, string $content): string {
            return "Content-Type: {$type}; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n\r\n"
                 . chunk_split(base64_encode($content), 76, "\r\n");
        };

        return "This is a multi-part message in MIME format.\r\n"
             . "--{$boundary}\r\n" . $part('text/plain', $text)
             . "--{$boundary}\r\n" . $part('text/html', $html)
             . "--{$boundary}--\r\n";
    }

    // =========================================================
    // SMTP
    // =========================================================

    private function sendSmtp(string $toEmail, array $headers, string $body): ?string {
        $this->connect();
        try {
            $ehloHost = substr(strrchr($this->fromAddress, '@') ?: '@localhost', 1);

            $this->expect($this->readReply(), [220], 'greeting');
            $this->command("EHLO {$ehloHost}", [250]);

            if ($this->encryption === 'tls') {
                $this->command('STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Could not start TLS with the mail server');
                }
                $this->command("EHLO {$ehloHost}", [250]);
            }

            if ($this->username !== '') {
                $this->command('AUTH LOGIN', [334]);
                $this->command(base64_encode($this->username), [334], 'the username');
                $this->command(base64_encode($this->password), [235], 'the password');
            }

            $this->command("MAIL FROM:<{$this->fromAddress}>", [250]);
            $this->command("RCPT TO:<{$toEmail}>", [250, 251]);
            $this->command('DATA', [354]);

            $message = implode("\r\n", $headers['lines']) . "\r\n\r\n" . $body;
            // Dot-stuff lines that start with a dot, then end the data with <CRLF>.<CRLF>.
            $message = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $message));
            $message = str_replace("\n", "\r\n", $message);
            $this->write($message . "\r\n.\r\n");
            $this->expect($this->readReply(), [250], 'message');

            try {
                $this->command('QUIT', [221]);
            } catch (Throwable $e) {
                // The message is already accepted; a rough goodbye does not matter.
            }
            $this->closeSocket();
            return null;
        } catch (Throwable $e) {
            $this->closeSocket();
            throw $e;
        }
    }

    private function connect(): void {
        $scheme  = $this->encryption === 'ssl' ? 'ssl' : 'tcp';
        $context = stream_context_create(['ssl' => [
            'verify_peer'      => $this->verifyPeer,
            'verify_peer_name' => $this->verifyPeer,
            'peer_name'        => $this->host,
        ]]);

        $socket = @stream_socket_client(
            "{$scheme}://{$this->host}:{$this->port}", $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT, $context
        );
        if (!$socket) {
            throw new RuntimeException("Could not connect to {$this->host}:{$this->port} ({$errstr})");
        }
        stream_set_timeout($socket, 20);
        $this->socket = $socket;
    }

    private function closeSocket(): void {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }

    private function write(string $data): void {
        if (@fwrite($this->socket, $data) === false) {
            throw new RuntimeException('Lost the connection to the mail server');
        }
    }

    /** $label names the step in error messages; credentials are sent as lines, so they must never be used as the name. */
    private function command(string $line, array $okCodes, ?string $label = null): string {
        $this->write($line . "\r\n");
        return $this->expect($this->readReply(), $okCodes, $label ?? strtok($line, ' '));
    }

    /** Reads a (possibly multi-line) reply and returns it as "250 text". */
    private function readReply(): string {
        $reply = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        if ($reply === '') {
            throw new RuntimeException('The mail server did not answer (timed out)');
        }
        return trim($reply);
    }

    private function expect(string $reply, array $okCodes, string $step): string {
        if (!in_array((int) substr($reply, 0, 3), $okCodes, true)) {
            throw new RuntimeException("Mail server rejected {$step}: " . substr($reply, 0, 200));
        }
        return $reply;
    }
}
