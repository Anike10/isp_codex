<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only client for the RouterOS v7 REST API (the "www" / "www-ssl"
 * service on a custom port). It returns the same shape the binary
 * {@see RouterOsClient} returns for `.../print` commands — a list of
 * associative string arrays — so {@see MikrotikImportService} can consume
 * either transport without caring which one produced the rows.
 */
class RouterOsRestClient
{
    /**
     * Run a RouterOS `.../print` command over REST.
     *
     * `/ppp/secret/print` maps to `GET {base}/rest/ppp/secret`. Any
     * `?field` attributes become `?field=value` query filters and
     * `.proplist` is passed through unchanged, matching the binary client.
     *
     * @param  array<string, string>  $attributes
     * @return array<int, array<string, string>>
     */
    public function records(MikrotikRouter $router, string $command, array $attributes = []): array
    {
        $path = $this->pathForCommand($command);
        $query = [];

        foreach ($attributes as $key => $value) {
            $query[ltrim((string) $key, '?')] = $value;
        }

        $payload = $this->request($router, 'get', $path, $query);

        if (! is_array($payload)) {
            return [];
        }

        // A single-object response (some endpoints) is normalised to a list.
        if ($payload !== [] && array_is_list($payload) === false) {
            $payload = [$payload];
        }

        return array_map(fn ($row) => $this->stringifyRow($row), $payload);
    }

    /**
     * Confirm the REST service answers and report the RouterOS version.
     *
     * @return array{version: string, board: string}
     */
    public function probe(MikrotikRouter $router): array
    {
        $resource = $this->request($router, 'get', 'system/resource');

        return [
            'version' => (string) ($resource['version'] ?? 'unknown'),
            'board' => (string) ($resource['board-name'] ?? $resource['platform'] ?? 'RouterOS'),
        ];
    }

    private function pathForCommand(string $command): string
    {
        $path = trim($command, '/');

        if (str_ends_with($path, '/print')) {
            $path = substr($path, 0, -strlen('/print'));
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    private function request(MikrotikRouter $router, string $method, string $path, array $query = []): array
    {
        $url = $router->restBaseUrl().'/rest/'.ltrim($path, '/');

        $request = Http::asJson()
            ->acceptJson()
            ->withBasicAuth($router->username, $router->apiPassword())
            ->connectTimeout(10)
            ->timeout(20);

        if ($router->rest_secure) {
            // RouterOS ships a self-signed certificate by default.
            $request = $request->withoutVerifying();
        }

        try {
            $response = $request->{$method}($url, $query);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                "Cannot connect to the RouterOS REST service at {$url}. ".$exception->getMessage(),
                previous: $exception
            );
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException(
                "Authentication failed: RouterOS rejected the REST user '{$router->username}'. "
                .'The user needs a group with the "read" and "rest-api" (or "api") policies and an allowed source address.'
            );
        }

        if ($response->failed()) {
            $detail = trim((string) $response->body());
            $detail = $detail === '' ? '' : ' RouterOS said: '.mb_substr($detail, 0, 300);

            throw new RuntimeException("RouterOS REST request to {$url} failed with HTTP {$response->status()}.".$detail);
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  mixed  $row
     * @return array<string, string>
     */
    private function stringifyRow($row): array
    {
        if (! is_array($row)) {
            return [];
        }

        $clean = [];

        foreach ($row as $key => $value) {
            if (is_bool($value)) {
                $clean[$key] = $value ? 'true' : 'false';
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = (string) $value;
            } else {
                $clean[$key] = json_encode($value);
            }
        }

        return $clean;
    }
}
