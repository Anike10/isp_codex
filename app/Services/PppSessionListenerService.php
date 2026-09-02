<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use RuntimeException;

/**
 * Owns one long-lived `/ppp/active/listen` connection for one RouterOS router.
 */
class PppSessionListenerService
{
    public function __construct(
        private readonly PppSessionSnapshotService $snapshots,
        private readonly MikrotikCustomerSyncService $customerSync,
    ) {}

    /**
     * Run until the caller asks the worker to stop or the socket fails.
     *
     * The listener command is sent before the one-time full read on a second
     * connection. Events occurring during that read are buffered by the first
     * socket, closing the otherwise unavoidable print/listen race window.
     *
     * @param  callable(): bool  $shouldStop
     * @param  null|callable(string, array<string, int>): void  $report
     * @return array{active: int, events: int, added: int, updated: int, finalised: int}
     */
    public function run(MikrotikRouter $router, callable $shouldStop, ?callable $report = null): array
    {
        if ($router->usesRestTransport()) {
            throw new RuntimeException('PPP event streaming requires the RouterOS binary API transport.');
        }

        if ($router->status !== 'active') {
            throw new RuntimeException('PPP event streaming requires an active router.');
        }

        $listener = $this->makeClient();
        $snapshotClient = $this->makeClient();

        try {
            $listener->connect(
                $router->ip_address,
                $router->api_port,
                $router->username,
                $router->apiPassword(),
                10
            );
            $listener->startCommand('/ppp/active/listen');

            $snapshotClient->connect(
                $router->ip_address,
                $router->api_port,
                $router->username,
                $router->apiPassword(),
                10
            );
            $sessions = collect($snapshotClient->command('/ppp/active/print'))
                ->filter(fn ($row): bool => is_array($row)
                    && filled($row['.id'] ?? null)
                    && filled($row['name'] ?? null))
                ->values();
            $interfaces = collect($snapshotClient->command('/interface/print'));
            $snapshotClient->close();

            // Capture current rows without deleting old snapshots yet. A
            // session may have dropped after listen started but before this
            // print completed; its exact `.dead` event is already buffered.
            $this->snapshots->sync($router, $sessions, $interfaces, 'listener', false);
            $this->customerSync->updateActiveConnectionData($router, $sessions);

            /** @var array<string, array<string, mixed>> $active */
            $active = $sessions
                ->keyBy(fn (array $row): string => trim((string) $row['.id']))
                ->all();
            $stats = [
                'active' => count($active),
                'events' => 0,
                'added' => 0,
                'updated' => 0,
                'finalised' => 0,
            ];
            $ready = false;

            $consume = function (array $reply) use ($router, &$active, &$stats, &$ready, $report): void {
                if ($reply['type'] === '!trap' || $reply['type'] === '!fatal') {
                    throw new RuntimeException($this->replyError($reply['data']));
                }

                if ($reply['type'] === '!done') {
                    throw new RuntimeException('RouterOS ended the PPP event stream unexpectedly.');
                }

                if ($reply['type'] !== '!re') {
                    return;
                }

                $event = $reply['data'];
                $sessionId = trim((string) ($event['.id'] ?? ''));
                if ($sessionId === '') {
                    return;
                }

                $merged = array_replace($active[$sessionId] ?? [], $event);
                $dead = filter_var($event['.dead'] ?? false, FILTER_VALIDATE_BOOL);
                $result = $this->snapshots->applyEvent($router, $merged);

                if ($dead) {
                    unset($active[$sessionId]);
                } else {
                    $active[$sessionId] = $merged;
                    $this->customerSync->updateActiveConnectionData($router, collect([$merged]));
                }

                $stats['active'] = count($active);
                $stats['events']++;
                if (array_key_exists($result, $stats)) {
                    $stats[$result]++;
                }
                if ($ready && $report !== null) {
                    $report($result, $stats);
                }
            };

            // Drain everything the listener buffered while the second socket
            // performed the full print. One quiet second is the boundary; the
            // stream itself deliberately has no terminating `!done` reply.
            while (! $shouldStop()) {
                $reply = $listener->nextReply(1);
                if ($reply === null) {
                    break;
                }

                $consume($reply);
            }

            // Now `active` includes both the print and every buffered add/dead
            // event, so snapshots still missing are truly stale from downtime.
            $reconciled = $this->snapshots->sync(
                $router,
                collect(array_values($active)),
                $interfaces,
                'listener'
            );
            $stats['active'] = count($active);
            $stats['finalised'] += (int) $reconciled['finalised'];

            if ($shouldStop()) {
                return $stats;
            }

            $ready = true;
            if ($report !== null) {
                $report('ready', $stats);
            }

            while (! $shouldStop()) {
                $reply = $listener->nextReply(1);
                if ($reply === null) {
                    continue;
                }

                $consume($reply);
            }

            return $stats;
        } finally {
            $snapshotClient->close();
            $listener->close();
        }
    }

    protected function makeClient(): RouterOsClient
    {
        return new RouterOsClient;
    }

    /** @param array<string, string> $data */
    private function replyError(array $data): string
    {
        $message = trim((string) ($data['message'] ?? 'RouterOS rejected the PPP event stream.'));
        $category = trim((string) ($data['category'] ?? ''));

        return $category === '' ? $message : "{$message} (category: {$category})";
    }
}
