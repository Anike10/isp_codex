<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\MikrotikRouter;
use App\Models\PppUsageLog;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manages the "PPP disconnect webhook" feature: an operator toggle plus a URL,
 * and the RouterOS side of it — one shared `on-down` script pushed onto every
 * `/ppp/profile` of every managed router. RouterOS runs that script when a PPP
 * session drops and POSTs the session's usage to the app.
 */
class PppWebhookService
{
    public const ENABLED_KEY = 'ppp_webhook_enabled';

    public const URL_KEY = 'ppp_webhook_url';

    public const SECRET_KEY = 'ppp_webhook_secret';

    /** Days to keep `ppp_usage_logs` rows; 0 = keep forever. */
    public const RETENTION_KEY = 'ppp_usage_log_retention_days';

    /** Header RouterOS sends and the receiver checks. */
    public const SECRET_HEADER = 'X-PPP-Webhook-Secret';

    public function __construct(private readonly MikrotikImportService $import) {}

    public function isEnabled(): bool
    {
        return AppSetting::value(self::ENABLED_KEY, '0') === '1';
    }

    public function url(): string
    {
        return (string) AppSetting::value(self::URL_KEY, '');
    }

    /** The shared secret, generating and persisting one the first time it is needed. */
    public function secret(): string
    {
        $secret = (string) AppSetting::value(self::SECRET_KEY, '');

        if ($secret === '') {
            $secret = Str::random(48);
            AppSetting::setValue(self::SECRET_KEY, $secret);
        }

        return $secret;
    }

    /** Days to keep disconnect-log rows; 0 means keep everything. */
    public function retentionDays(): int
    {
        return max(0, (int) AppSetting::value(self::RETENTION_KEY, '0'));
    }

    public function setRetentionDays(int $days): void
    {
        AppSetting::setValue(self::RETENTION_KEY, (string) max(0, $days));
    }

    /**
     * Delete `ppp_usage_logs` rows older than the configured retention window.
     * Returns the number of rows removed (0 when retention is off).
     */
    public function pruneUsageLogs(): int
    {
        $days = $this->retentionDays();

        if ($days <= 0) {
            return 0;
        }

        return PppUsageLog::query()
            ->where('disconnected_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Persist the toggle + URL, then push the matching `on-down` script (or an
     * empty one when disabled) onto every PPP profile of every managed router.
     *
     * @return array{enabled: bool, results: array<int, array{router: string, profiles?: int, skipped?: string, error?: string}>, profiles: int, routers: int, failed: int}
     */
    public function save(bool $enabled, string $url): array
    {
        AppSetting::setValue(self::ENABLED_KEY, $enabled ? '1' : '0');
        AppSetting::setValue(self::URL_KEY, trim($url));
        $this->secret();

        return $this->syncAllRouters();
    }

    /**
     * Re-push the current script state to every managed router. Read-only and
     * inactive routers are skipped (and reported) because the app must not, or
     * cannot, write to them.
     *
     * @return array{enabled: bool, results: array<int, array{router: string, profiles?: int, skipped?: string, error?: string}>, profiles: int, routers: int, failed: int}
     */
    public function syncAllRouters(): array
    {
        $enabled = $this->isEnabled();
        $results = [];
        $profiles = 0;
        $routers = 0;
        $failed = 0;

        MikrotikRouter::query()->orderBy('id')->get()->each(function (MikrotikRouter $router) use ($enabled, &$results, &$profiles, &$routers, &$failed): void {
            if ($router->status !== 'active') {
                $results[] = ['router' => $router->name, 'skipped' => 'router is not active'];

                return;
            }

            if ($router->read_only) {
                $results[] = ['router' => $router->name, 'skipped' => 'router is read-only'];

                return;
            }

            try {
                $count = $this->applyToRouter($router, $enabled);
                $profiles += $count;
                $routers++;
                $results[] = ['router' => $router->name, 'profiles' => $count];
            } catch (Throwable $exception) {
                $failed++;
                $results[] = ['router' => $router->name, 'error' => $exception->getMessage()];
            }
        });

        return [
            'enabled' => $enabled,
            'results' => $results,
            'profiles' => $profiles,
            'routers' => $routers,
            'failed' => $failed,
        ];
    }

    /**
     * Set the same `on-down` value on every `/ppp/profile` of one router.
     * Returns the number of profiles written.
     */
    public function applyToRouter(MikrotikRouter $router, bool $enabled): int
    {
        $script = $enabled ? $this->scriptFor($router) : '';
        $records = $this->import->liveRecords($router, '/ppp/profile/print');
        $written = 0;

        foreach ($records as $record) {
            if (empty($record['.id'])) {
                continue;
            }

            $this->import->write($router, '/ppp/profile/set', [
                '.id' => $record['.id'],
                'on-down' => $script,
            ]);
            $written++;
        }

        return $written;
    }

    /**
     * The one reusable RouterOS `on-down` script. Event variables are first
     * copied to uniquely named locals so hyphenated names such as
     * $"bytes-in" never break the quoting of the JSON payload.
     *
     * Values are sent as JSON strings so an empty counter never produces
     * malformed JSON; the receiver casts them back to integers.
     */
    public function scriptFor(MikrotikRouter $router): string
    {
        $url = $this->url();
        $secret = $this->secret();

        $body = '("{\\"user\\":\\"".$webhookUser."\\",'
            .'\\"uptime\\":\\"".$webhookUptime."\\",'
            .'\\"download\\":\\"".$webhookBytesOut."\\",'
            .'\\"upload\\":\\"".$webhookBytesIn."\\",'
            .'\\"caller_id\\":\\"".$webhookCallerId."\\",'
            .'\\"reason\\":\\"".$webhookReason."\\",'
            .'\\"router_id\\":\\"'.$router->id.'\\"}")';

        $header = 'Content-Type: application/json,'.self::SECRET_HEADER.': '.$secret;

        return ':local webhookUser $user;'
            .':local webhookUptime $uptime;'
            .':local webhookCallerId $"caller-id";'
            .':local webhookReason $"last-disconnect-reason";'
            .':if ($webhookUptime = "null" || [:len $webhookUptime] = 0) do={:set webhookUptime "0";};'
            .':local webhookBytesIn $"bytes-in";'
            .':local webhookBytesOut $"bytes-out";'
            .':if ([:len $webhookBytesIn] = 0 || $webhookBytesIn = "null") do={:set webhookBytesIn 0};'
            .':if ([:len $webhookBytesOut] = 0 || $webhookBytesOut = "null") do={:set webhookBytesOut 0};'
            .':local webhookPayload '.$body.';'
            .'/tool fetch url="'.$url.'"'
            .' http-method=post'
            .' http-header-field="'.$header.'"'
            .' http-data=$webhookPayload'
            .' output=none;';
    }
}
