<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client for the RouterOS v7 REST API (the "www" / "www-ssl" service on a
 * custom port). Reads return the same shape the binary {@see RouterOsClient}
 * returns for `.../print` commands — a list of associative string arrays —
 * and writes map RouterOS `add` / `set` / `remove` commands onto the REST
 * verbs, so {@see MikrotikImportService} can drive either transport the same
 * way. Whether writes are allowed at all is governed by the router's
 * "read-only" flag in the app, exactly like the binary API.
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
     * Run a RouterOS write command over REST. Mirrors the binary client's
     * `write()`: `<menu>/add` -> PUT /rest/<menu>, `<menu>/set` (with `.id`)
     * -> PATCH /rest/<menu>/<id>, `<menu>/remove` (with `.id`) -> DELETE
     * /rest/<menu>/<id>, and any other trailing verb -> POST /rest/<menu>/<verb>.
     *
     * @param  array<string, string>  $attributes
     * @return array<int, array<string, string>>
     */
    public function write(MikrotikRouter $router, string $command, array $attributes = []): array
    {
        $path = trim($command, '/');
        $segments = explode('/', $path);
        $verb = array_pop($segments);
        $menu = implode('/', $segments);

        $id = $attributes['.id'] ?? $attributes['numbers'] ?? null;
        unset($attributes['.id'], $attributes['numbers']);

        [$method, $target] = match ($verb) {
            'add' => ['put', $menu],
            'set', 'edit' => ['patch', $menu.'/'.$this->requireId($id, $command)],
            'remove', 'delete' => ['delete', $menu.'/'.$this->requireId($id, $command)],
            default => ['post', $path],
        };

        $payload = $this->request($router, $method, $target, $attributes);

        if ($payload === []) {
            return [];
        }

        if (array_is_list($payload) === false) {
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

    private function requireId(?string $id, string $command): string
    {
        $id = trim((string) $id);

        if ($id === '') {
            throw new RuntimeException("The REST command \"{$command}\" needs a RouterOS \".id\" to target a row.");
        }

        return $id;
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
     * @param  array<string, mixed>  $data  Query string for GET, JSON body otherwise.
     * @return array<mixed>
     */
    private function request(MikrotikRouter $router, string $method, string $path, array $data = []): array
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
            $response = $request->{$method}($url, $data);
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
