<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Carbon;
use Throwable;

class NightlyLiveSyncService
{
    public const ENABLED_KEY = 'network.nightly_live_sync.enabled';

    public const RUN_TIME_KEY = 'network.nightly_live_sync.run_time';

    // Kept for backward compatibility with older deployments that only stored hour.
    public const HOUR_KEY = 'network.nightly_live_sync.hour';

    public const LAST_STARTED_AT_KEY = 'network.nightly_live_sync.last_started_at';

    public const LAST_COMPLETED_AT_KEY = 'network.nightly_live_sync.last_completed_at';

    public const LAST_STATUS_KEY = 'network.nightly_live_sync.last_status';

    public const LAST_SUMMARY_KEY = 'network.nightly_live_sync.last_summary';

    public function enabled(): bool
    {
        return AppSetting::value(self::ENABLED_KEY, '1') === '1';
    }

    public function runTime(): string
    {
        $runTime = (string) AppSetting::value(self::RUN_TIME_KEY, '');

        if ($runTime === '') {
            $runTime = (string) AppSetting::value(self::HOUR_KEY, '04:00');
        }

        return $this->normalizeRunTime($runTime);
    }

    public function setSchedule(bool $enabled, string $runTime): void
    {
        AppSetting::setValue(self::ENABLED_KEY, $enabled ? '1' : '0');
        AppSetting::setValue(self::RUN_TIME_KEY, $this->normalizeRunTime($runTime));
    }

    public function lastStartedAt(): ?Carbon
    {
        return $this->dateSetting(self::LAST_STARTED_AT_KEY);
    }

    public function lastCompletedAt(): ?Carbon
    {
        return $this->dateSetting(self::LAST_COMPLETED_AT_KEY);
    }

    public function lastStatus(): ?string
    {
        return AppSetting::value(self::LAST_STATUS_KEY);
    }

    public function lastSummary(): ?string
    {
        return AppSetting::value(self::LAST_SUMMARY_KEY);
    }

    public function isDue(?Carbon $at = null): bool
    {
        $at ??= now();

        if (! $this->enabled() || $at->format('H:i') !== $this->runTime()) {
            return false;
        }

        return $this->lastStartedAt()?->toDateString() !== $at->toDateString();
    }

    /**
     * @return array<int, array{key: string, label: string, command: string, parameters: array<string, mixed>}>
     */
    public function steps(): array
    {
        return [
            [
                'key' => 'mikrotik_secrets',
                'label' => 'MikroTik profiles, PPP secrets and active-user cache',
                'command' => 'mikrotik:import-secrets',
                'parameters' => [],
            ],
            [
                'key' => 'mikrotik_pools',
                'label' => 'MikroTik IP pools',
                'command' => 'mikrotik:import-ip-pools',
                'parameters' => [],
            ],
            [
                'key' => 'mikrotik_sessions',
                'label' => 'MikroTik active MAC/IP and session reconciliation',
                'command' => 'mikrotik:sync-active-macs',
                'parameters' => [],
            ],
            [
                'key' => 'olt_onus',
                'label' => 'All OLT/ONU status, name, power, VLAN and MAC data',
                'command' => 'olt:sync-all',
                'parameters' => [],
            ],
        ];
    }

    /**
     * @param  callable(string, array<string, mixed>): int  $runner
     * @return array{status: 'success'|'failed', succeeded: int, failed: int, summary: string, results: array<int, array<string, mixed>>}
     */
    public function run(callable $runner): array
    {
        $startedAt = now();
        AppSetting::setValue(self::LAST_STARTED_AT_KEY, $startedAt->toDateTimeString());
        AppSetting::setValue(self::LAST_STATUS_KEY, 'running');
        AppSetting::setValue(self::LAST_SUMMARY_KEY, 'Nightly live sync is running.');

        $results = [];
        $failed = 0;

        foreach ($this->steps() as $step) {
            try {
                $exitCode = (int) $runner($step['command'], $step['parameters']);
                $success = $exitCode === 0;
                $message = $success ? 'completed' : 'failed with exit code '.$exitCode;
            } catch (Throwable $exception) {
                $success = false;
                $message = trim($exception->getMessage()) ?: 'unknown error';
            }

            if (! $success) {
                $failed++;
            }

            $results[] = $step + [
                'success' => $success,
                'message' => $message,
            ];
        }

        $status = $failed === 0 ? 'success' : 'failed';
        $succeeded = count($results) - $failed;
        $summary = "{$succeeded}/".count($results).' live-sync step(s) completed.';

        if ($failed > 0) {
            $failedLabels = collect($results)
                ->where('success', false)
                ->pluck('label')
                ->implode(', ');
            $summary .= ' Failed: '.$failedLabels.'.';
        }

        AppSetting::setValue(self::LAST_COMPLETED_AT_KEY, now()->toDateTimeString());
        AppSetting::setValue(self::LAST_STATUS_KEY, $status);
        AppSetting::setValue(self::LAST_SUMMARY_KEY, $summary);

        return compact('status', 'succeeded', 'failed', 'summary', 'results');
    }

    private function dateSetting(string $key): ?Carbon
    {
        $value = AppSetting::value($key);

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeRunTime(string $runTime): string
    {
        if (preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d$/', trim($runTime)) === 1) {
            $parts = explode(':', trim($runTime), 2);
            $hour = (int) $parts[0];
            $minute = (int) $parts[1];

            return sprintf('%02d:%02d', $hour, $minute);
        }

        return '04:00';
    }
}
