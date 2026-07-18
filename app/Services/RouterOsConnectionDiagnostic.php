<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use Throwable;

class RouterOsConnectionDiagnostic
{
    public function describe(Throwable $exception, MikrotikRouter $router, bool $pingOnline): array
    {
        $rawMessage = trim($exception->getMessage());
        $message = strtolower($rawMessage);

        if (str_contains($message, 'authentication failed')
            || str_contains($message, 'invalid user')
            || str_contains($message, 'username or password')
            || str_contains($message, 'login failed')) {
            return [
                'type' => 'credentials',
                'label' => 'Credential rejected',
                'message' => "RouterOS rejected the saved username '{$router->username}' or password.",
                'guidance' => 'Confirm the RouterOS user, password, api policy permission, and allowed source address.',
            ];
        }

        if (str_contains($message, 'cannot connect')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'timed out')
            || str_contains($message, 'network/port failure')) {
            return [
                'type' => 'network',
                'label' => $pingOnline ? 'API port unreachable' : 'Router unreachable',
                'message' => $pingOnline
                    ? "The router responds to ping, but TCP {$router->api_port} cannot be opened."
                    : "The app server cannot reach {$router->ip_address}:{$router->api_port}.",
                'guidance' => 'Check /ip service api port, firewall/NAT rules, public routing, and whether the API service is enabled.',
            ];
        }

        return [
            'type' => 'protocol',
            'label' => 'RouterOS API response error',
            'message' => $rawMessage ?: 'RouterOS returned an unexpected or incomplete API response.',
            'guidance' => 'Retry the check. If it repeats, verify RouterOS API compatibility, connection limits, and router logs.',
        ];
    }
}
