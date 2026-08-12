<?php

namespace App\Services;

use RuntimeException;

class RouterOsClient
{
    /** @var resource|null */
    private $socket = null;

    private ?string $plainLoginFailure = null;

    public function connect(string $host, int $port, string $username, string $password, int $timeout = 5): void
    {
        $this->socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);

        if (! $this->socket) {
            throw new RuntimeException("Network/port failure: cannot connect to MikroTik {$host}:{$port}. {$errstr}");
        }

        stream_set_timeout($this->socket, $timeout);

        if ($this->tryPlainLogin($username, $password)) {
            return;
        }

        $this->legacyLogin($username, $password);
    }

    public function command(string $command, array $attributes = []): array
    {
        $words = [$command];

        foreach ($attributes as $key => $value) {
            $prefix = str_starts_with((string) $key, '?') ? '' : '=';
            $words[] = "{$prefix}{$key}={$value}";
        }

        $this->writeSentence($words);

        $responses = [];

        while (true) {
            $sentence = $this->readSentence();

            if ($sentence === []) {
                continue;
            }

            $reply = array_shift($sentence);
            $data = $this->parseAttributes($sentence);

            if ($reply === '!trap') {
                throw new RuntimeException($this->formatTrapExceptionMessage($data));
            }

            if ($reply === '!done') {
                return $responses;
            }

            if ($reply === '!re') {
                $responses[] = $data;
            }
        }
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function tryPlainLogin(string $username, string $password): bool
    {
        $this->writeSentence(['/login', '=name='.$username, '=password='.$password]);

        while (true) {
            $sentence = $this->readSentence();

            if ($sentence === []) {
                continue;
            }

            $reply = array_shift($sentence);
            $data = $this->parseAttributes($sentence);

            if ($reply === '!done') {
                return true;
            }

            if ($reply === '!trap') {
                $this->plainLoginFailure = $data['message'] ?? null;
                return false;
            }
        }
    }

    private function legacyLogin(string $username, string $password): void
    {
        $this->writeSentence(['/login']);
        $challenge = null;

        while (true) {
            $sentence = $this->readSentence();

            if ($sentence === []) {
                continue;
            }

            $reply = array_shift($sentence);
            $data = $this->parseAttributes($sentence);

            if ($reply === '!trap') {
                $message = $this->formatTrapExceptionMessage($data);
                throw new RuntimeException('Authentication failed: '.$message);
            }

            if (isset($data['ret'])) {
                $challenge = $data['ret'];
            }

            if ($reply === '!done') {
                break;
            }
        }

        if (! $challenge) {
            $detail = $this->plainLoginFailure ? ' RouterOS said: '.$this->plainLoginFailure : '';
            throw new RuntimeException('Authentication failed: RouterOS rejected the saved username or password.'.$detail);
        }

        $response = '00'.md5(chr(0).$password.hex2bin($challenge));
        $this->writeSentence(['/login', '=name='.$username, '=response='.$response]);

        while (true) {
            $sentence = $this->readSentence();

            if ($sentence === []) {
                continue;
            }

            $reply = array_shift($sentence);
            $data = $this->parseAttributes($sentence);

            if ($reply === '!trap') {
                $message = $this->formatTrapExceptionMessage($data);
                throw new RuntimeException('Authentication failed: '.$message);
            }

            if ($reply === '!done') {
                return;
            }
        }
    }

    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeLength(strlen($word));
            $this->write($word);
        }

        $this->writeLength(0);
    }

    private function readSentence(): array
    {
        $sentence = [];

        while (true) {
            $length = $this->readLength();

            if ($length === 0) {
                return $sentence;
            }

            $sentence[] = $this->read($length);
        }
    }

    private function writeLength(int $length): void
    {
        if ($length < 0x80) {
            $this->write(chr($length));
        } elseif ($length < 0x4000) {
            $this->write(chr(($length >> 8) | 0x80).chr($length & 0xFF));
        } elseif ($length < 0x200000) {
            $this->write(chr(($length >> 16) | 0xC0).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        } elseif ($length < 0x10000000) {
            $this->write(chr(($length >> 24) | 0xE0).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        } else {
            $this->write(chr(0xF0).chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
        }
    }

    private function readLength(): int
    {
        $first = ord($this->read(1));

        if (($first & 0x80) === 0) {
            return $first;
        }

        if (($first & 0xC0) === 0x80) {
            return (($first & ~0xC0) << 8) + ord($this->read(1));
        }

        if (($first & 0xE0) === 0xC0) {
            return (($first & ~0xE0) << 16) + (ord($this->read(1)) << 8) + ord($this->read(1));
        }

        if (($first & 0xF0) === 0xE0) {
            return (($first & ~0xF0) << 24) + (ord($this->read(1)) << 16) + (ord($this->read(1)) << 8) + ord($this->read(1));
        }

        return (ord($this->read(1)) << 24) + (ord($this->read(1)) << 16) + (ord($this->read(1)) << 8) + ord($this->read(1));
    }

    private function write(string $value): void
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('MikroTik socket is not connected.');
        }

        fwrite($this->socket, $value);
    }

    private function read(int $length): string
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('MikroTik socket is not connected.');
        }

        $value = '';

        while (strlen($value) < $length) {
            $chunk = fread($this->socket, $length - strlen($value));

            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);

                if ($meta['timed_out'] ?? false) {
                    throw new RuntimeException('Network timeout while waiting for the RouterOS API response.');
                }

                throw new RuntimeException('MikroTik connection closed unexpectedly.');
            }

            $value .= $chunk;
        }

        return $value;
    }

    private function parseAttributes(array $words): array
    {
        $data = [];

        foreach ($words as $word) {
            if (! str_starts_with($word, '=')) {
                continue;
            }

            $parts = explode('=', $word, 3);

            if (count($parts) === 3) {
                $data[$parts[1]] = $parts[2];
            }
        }

        return $data;
    }

    private function formatTrapExceptionMessage(array $data): string
    {
        $message = trim((string) ($data['message'] ?? 'MikroTik returned an error.'));
        $extras = [];

        foreach (['reply', 'detail', 'category', 'code', 'type'] as $field) {
            if (! empty($data[$field]) && (string) $data[$field] !== $message) {
                $extras[$field] = (string) $data[$field];
            }
        }

        foreach ($data as $field => $value) {
            if (in_array($field, ['message', 'reply', 'detail', 'category', 'code', 'type'], true)) {
                continue;
            }

            if (! is_scalar($value) || $value === '') {
                continue;
            }

            $extras[$field] = (string) $value;
        }

        if ($extras === []) {
            return $message;
        }

        $extraText = [];
        foreach ($extras as $field => $value) {
            if ($field === 'message') {
                continue;
            }

            $extraText[] = "{$field}: {$value}";
        }

        return "{$message} (".implode(', ', $extraText).')';
    }
}
