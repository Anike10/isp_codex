<?php

namespace App\Services;

use App\Support\Utf8Text;
use phpseclib3\Net\SSH2;
use RuntimeException;

class OltSshClient
{
    private ?SSH2 $ssh = null;

    public function connect(string $host, int $port, string $username, string $password, int $timeout = 10): void
    {
        $this->ssh = new SSH2($host, $port, $timeout);
        $this->ssh->setTimeout($timeout);
        $this->ssh->setPreferredAlgorithms([
            'hostkey' => ['ssh-rsa', 'ssh-dss'],
        ]);

        if (! $this->ssh->login($username, $password)) {
            throw new RuntimeException('OLT SSH login failed.');
        }

        $this->ssh->enablePTY();
        $this->ssh->read('/[#>]\s*$/', SSH2::READ_REGEX);
    }

    public function command(string $command): string
    {
        if (! $this->ssh) {
            throw new RuntimeException('OLT SSH client is not connected.');
        }

        $this->ssh->write($command."\r");

        $output = '';
        $loops = 0;

        do {
            $chunk = (string) $this->ssh->read('/(--More--|[#>]\s*$)/', SSH2::READ_REGEX);
            $output .= $chunk;

            if (str_contains($chunk, '--More--')) {
                $this->ssh->write(' ');
            } else {
                break;
            }

            $loops++;
        } while ($loops < 200);

        return $this->cleanOutput($output);
    }

    public function close(): void
    {
        $this->ssh?->disconnect();
        $this->ssh = null;
    }

    private function cleanOutput(string $output): string
    {
        $output = preg_replace('/\x1B\[[0-9;?]*[A-Za-z]/', '', $output) ?? $output;
        $output = preg_replace('/\x1B\][^\x07]*(?:\x07|\x1B\\\\)/', '', $output) ?? $output;
        $output = str_replace(['--More--', "\x08", "\r"], ['', '', ''], $output);

        return trim(Utf8Text::clean($output) ?? '');
    }
}
