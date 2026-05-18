<?php

namespace App\Services;

use App\Support\Utf8Text;
use RuntimeException;

class OltTelnetClient
{
    /** @var resource|null */
    private $socket = null;

    private int $timeout = 8;

    public function connect(string $host, int $port, string $username, string $password, ?string $enablePassword = null, int $timeout = 8): void
    {
        $this->timeout = $timeout;
        $this->socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);

        if (! $this->socket) {
            throw new RuntimeException("Cannot connect to OLT {$host}:{$port}. {$errstr}");
        }

        stream_set_timeout($this->socket, $timeout);

        $banner = $this->readUntilPrompt();

        if (preg_match('/(?:login|username|user name)\s*:/i', $banner)) {
            $this->writeLine($username);
            $banner = $this->readUntilPrompt();
        }

        if (preg_match('/password\s*:/i', $banner)) {
            $this->writeLine($password);
            $banner = $this->readUntilPrompt();
        }

        if (! preg_match('/[#>]\s*$/', $banner)) {
            throw new RuntimeException('OLT login prompt was not recognized.');
        }

        if ($enablePassword && str_ends_with(rtrim($banner), '>')) {
            $this->writeLine('enable');
            $enablePrompt = $this->readUntilPrompt();

            if (preg_match('/password\s*:/i', $enablePrompt)) {
                $this->writeLine($enablePassword);
                $this->readUntilPrompt();
            }
        }

        // Do not send pager/config helper commands here; OLT polling must stay read-only.
    }

    public function command(string $command): string
    {
        $this->writeLine($command);

        return $this->readUntilPrompt();
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function readUntilPrompt(): string
    {
        $output = '';
        $startedAt = time();

        while (true) {
            if (! is_resource($this->socket)) {
                throw new RuntimeException('OLT socket is not connected.');
            }

            $chunk = fread($this->socket, 4096);

            if ($chunk !== false && $chunk !== '') {
                $output .= $this->stripTelnetNegotiation($chunk);

                if (preg_match('/(?:login|username|user name|password)\s*:\s*$/i', $output) || preg_match('/[#>]\s*$/', $output)) {
                    return $output;
                }
            }

            $meta = stream_get_meta_data($this->socket);

            if ($meta['timed_out'] || time() - $startedAt >= $this->timeout) {
                return $output;
            }
        }
    }

    private function writeLine(string $value): void
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('OLT socket is not connected.');
        }

        fwrite($this->socket, $value."\r\n");
    }

    private function stripTelnetNegotiation(string $value): string
    {
        $value = preg_replace('/\xFF[\xFB-\xFE]./s', '', $value) ?? $value;

        return Utf8Text::clean($value) ?? '';
    }
}
