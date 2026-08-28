<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Before this release an "unmark_special" action left the matching
 * "mark_special" row open, so its running give-away value would grow forever.
 * Close any such stale row at the time its party stopped being special.
 */
return new class extends Migration
{
    public function up(): void
    {
        $openSpecials = DB::table('concession_logs')
            ->where('action_type', 'mark_special')
            ->whereNull('closed_at')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'created_at', 'daily_rate']);

        foreach ($openSpecials as $row) {
            $stoppedAt = DB::table('concession_logs')
                ->where('customer_id', $row->customer_id)
                ->where('action_type', 'unmark_special')
                ->where('id', '>', $row->id)
                ->orderBy('id')
                ->value('created_at');

            if ($stoppedAt === null) {
                // Still special: leave the row open so it keeps accruing.
                continue;
            }

            $start = Carbon::parse($row->created_at)->startOfDay();
            $end = Carbon::parse($stoppedAt)->startOfDay();
            $days = $end->lessThan($start) ? 0 : (int) $start->diffInDays($end) + 1;

            DB::table('concession_logs')->where('id', $row->id)->update([
                'free_days' => $days,
                'estimated_value' => round($days * (float) $row->daily_rate, 2),
                'value_status' => 'final',
                'closed_at' => $stoppedAt,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Re-opening settled history is not meaningful; nothing to undo.
    }
};
