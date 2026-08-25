<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class EmailRecipientVerificationService
{
    /**
     * Verify that a recipient mailbox is reachable via SMTP before continuing checkout.
     *
     * This is best-effort validation. For Gmail addresses it catches non-existent
     * mailboxes early by probing the MX server with RCPT TO.
     */
    public function assertDeliverable(string $email): void
    {
        $normalizedEmail = $this->normalizeEmail($email);

        if (! filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email không đúng định dạng.');
        }

        [, $domain] = array_pad(explode('@', $normalizedEmail, 2), 2, '');
        $mxHosts = $this->resolveMxHosts($domain);

        if ($mxHosts === []) {
            throw new RuntimeException('Không tìm thấy máy chủ thư của email này. Vui lòng kiểm tra lại địa chỉ.');
        }

        $lastTemporaryMessage = null;

        foreach ($mxHosts as $host) {
            $probe = $this->probeMailbox($host, $normalizedEmail);

            if ($probe['deliverable']) {
                return;
            }

            if ($probe['temporary']) {
                $lastTemporaryMessage = $probe['message'];
                continue;
            }

            throw new RuntimeException($probe['message']);
        }

        throw new RuntimeException($lastTemporaryMessage ?: 'Không thể xác minh địa chỉ email này. Vui lòng thử lại sau.');
    }

    /**
     * @return array<int, string>
     */
    private function resolveMxHosts(string $domain): array
    {
        $records = dns_get_record($domain, DNS_MX);

        if (! is_array($records) || $records === []) {
            return [];
        }

        usort($records, static function (array $left, array $right): int {
            return ((int) ($left['pri'] ?? 0)) <=> ((int) ($right['pri'] ?? 0));
        });

        return collect($records)
            ->pluck('target')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{deliverable: bool, temporary: bool, message: string}
     */
    private function probeMailbox(string $host, string $email): array
    {
        $endpoint = "tcp://{$host}:25";
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($endpoint, $errno, $errstr, 10);

        if (! is_resource($socket)) {
            return [
                'deliverable' => false,
                'temporary' => true,
                'message' => 'Không thể kết nối tới máy chủ thư để kiểm tra email. Vui lòng thử lại sau.',
            ];
        }

        stream_set_timeout($socket, 10);

        try {
            $banner = $this->readResponse($socket);
            if (! in_array($banner['code'], [220], true)) {
                return [
                    'deliverable' => false,
                    'temporary' => true,
                    'message' => 'Máy chủ thư phản hồi bất thường khi kiểm tra email. Vui lòng thử lại sau.',
                ];
            }

            $ehlo = $this->sendCommand($socket, 'EHLO chilldrink.local');
            if (! in_array($ehlo['code'], [250], true)) {
                $helo = $this->sendCommand($socket, 'HELO chilldrink.local');
                if (! in_array($helo['code'], [250], true)) {
                    return [
                        'deliverable' => false,
                        'temporary' => true,
                        'message' => 'Không thể khởi tạo kết nối kiểm tra email. Vui lòng thử lại sau.',
                    ];
                }
            }

            $mailFrom = $this->sendCommand($socket, 'MAIL FROM:<>');
            if (! in_array($mailFrom['code'], [250], true)) {
                return $this->classifyResponse($mailFrom);
            }

            $rcptTo = $this->sendCommand($socket, 'RCPT TO:<' . $email . '>');
            $classification = $this->classifyResponse($rcptTo);

            $this->sendCommand($socket, 'QUIT');

            return $classification;
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return array{code: int, message: string}
     */
    private function readResponse($socket): array
    {
        $lines = [];

        while (! feof($socket)) {
            $line = fgets($socket, 2048);

            if ($line === false) {
                break;
            }

            $trimmed = rtrim($line, "\r\n");
            $lines[] = $trimmed;

            if (preg_match('/^\d{3}\s/', $trimmed) === 1) {
                break;
            }
        }

        $code = isset($lines[0]) ? (int) substr($lines[0], 0, 3) : 0;

        return [
            'code' => $code,
            'message' => trim(implode(' ', array_map(static function (string $line): string {
                return preg_replace('/^\d{3}[-\s]?/', '', $line) ?? $line;
            }, $lines))),
        ];
    }

    /**
     * @return array{code: int, message: string}
     */
    private function sendCommand($socket, string $command): array
    {
        fwrite($socket, $command . "\r\n");

        return $this->readResponse($socket);
    }

    /**
     * @param array{code: int, message: string} $response
     * @return array{deliverable: bool, temporary: bool, message: string}
     */
    private function classifyResponse(array $response): array
    {
        $code = (int) ($response['code'] ?? 0);
        $message = trim((string) ($response['message'] ?? ''));

        if (in_array($code, [250, 251], true)) {
            return [
                'deliverable' => true,
                'temporary' => false,
                'message' => $message,
            ];
        }

        if (in_array($code, [421, 450, 451, 452, 454, 455], true)) {
            return [
                'deliverable' => false,
                'temporary' => true,
                'message' => $message !== '' ? $message : 'Máy chủ thư đang bận hoặc tạm thời không phản hồi.',
            ];
        }

        if (in_array($code, [550, 551, 552, 553, 554], true)) {
            return [
                'deliverable' => false,
                'temporary' => false,
                'message' => 'Địa chỉ email này không tồn tại hoặc không nhận thư. Vui lòng kiểm tra lại.',
            ];
        }

        return [
            'deliverable' => false,
            'temporary' => false,
            'message' => $message !== '' ? $message : 'Không thể xác minh địa chỉ email này. Vui lòng kiểm tra lại.',
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
