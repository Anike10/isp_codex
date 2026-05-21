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
        stream_set_blocking($this->socket, false);

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
        $startedAt = microtime(true);

        while (true) {
            if (! is_resource($this->socket)) {
                throw new RuntimeException('OLT socket is not connected.');
            }

            if ($output !== '' && (preg_match('/(?:login|username|user name|password)\s*:\s*$/i', $output) || preg_match('/[#>]\s*$/', $output))) {
                return $output;
            }

            $read = [$this->socket];
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 0, 200000);

            if ($ready === false) {
                return $output;
            }

            if ($ready === 0) {
                if (microtime(true) - $startedAt >= $this->timeout) {
                    return $output;
                }

                continue;
            }

            $chunk = fread($this->socket, 4096);

            if ($chunk !== false && $chunk !== '') {
                $output .= $this->stripTelnetNegotiation($chunk);

                if (str_contains($output, '--More--')) {
                    $this->writeRaw(' ');
                    $output = str_replace('--More--', '', $output);

                    continue;
                }

                if (preg_match('/(?:login|username|user name|password)\s*:\s*$/i', $output) || preg_match('/[#>]\s*$/', $output)) {
                    return $output;
                }
            }

            if (microtime(true) - $startedAt >= $this->timeout) {
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

    private function writeRaw(string $value): void
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('OLT socket is not connected.');
        }

        fwrite($this->socket, $value);
    }

    private function stripTelnetNegotiation(string $value): string
    {
        $value = preg_replace('/\xFF[\xFB-\xFE]./s', '', $value) ?? $value;

        $value = str_replace(["--More--", "\x08", "\r"], ['', '', ''], $value);

        return Utf8Text::clean($value) ?? '';
    }
}
