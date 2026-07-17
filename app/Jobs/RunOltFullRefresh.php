<?php

namespace App\Jobs;

use App\Http\Controllers\OltOnuController;
use App\Models\OltRefreshRun;
use App\Services\OltLiveOutputParser;
use App\Services\OltSshClient;
use App\Services\OltTelnetClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Throwable;

class RunOltFullRefresh implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $refreshRunId)
    {
    }

    public function handle(
        OltOnuController $controller,
        OltSshClient $sshClient,
        OltTelnetClient $telnetClient,
        OltLiveOutputParser $parser
    ): void {
        // Deferred jobs run inside the Windows/XAMPP request process. Give a
        // full multi-PON ONU detail scan the same allowance as the job timeout.
        set_time_limit($this->timeout);

        $run = OltRefreshRun::query()->with('oltDevice')->findOrFail($this->refreshRunId);

        if (! $run->oltDevice) {
            $run->update([
                'status' => 'failed',
                'progress' => 100,
                'message' => 'OLT was deleted before the background refresh started.',
                'completed_at' => now(),
            ]);

            return;
        }

        $request = Request::create('/internal/olt-refresh', 'POST', [
            'refresh_mode' => $run->refresh_mode,
            'pon_port' => $run->pon_port,
            'refresh_run_id' => $run->id,
            '_background_worker' => 1,
        ]);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);

        $controller->refresh($request, $run->oltDevice, $sshClient, $telnetClient, $parser);
    }

    public function failed(?Throwable $exception): void
    {
        OltRefreshRun::query()->whereKey($this->refreshRunId)->update([
            'status' => 'failed',
            'progress' => 100,
            'message' => 'Background refresh stopped: '.($exception?->getMessage() ?: 'Unknown error'),
            'completed_at' => now(),
        ]);
    }
}
