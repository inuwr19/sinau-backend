<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Generate order number format:
     * CS-YYYYMMDD-<branch_code>-NNN
     *
     * Uses table `order_counters` to increment per-date-per-branch counter
     * in a DB transaction for safety.
     */
    public static function generateForBranch(int $branchId): string
    {
        $today = now()->toDateString(); // YYYY-MM-DD

        return DB::transaction(function () use ($branchId, $today) {
            // lock or create the counter row
            $row = DB::table('order_counters')
                ->where('date', $today)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $counter = $row->counter + 1;
                DB::table('order_counters')
                    ->where('id', $row->id)
                    ->update(['counter' => $counter, 'updated_at' => now()]);
            } else {
                $counter = 1;
                DB::table('order_counters')->insert([
                    'date' => $today,
                    'branch_id' => $branchId,
                    'counter' => $counter,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // get branch code
            $branch = Branch::find($branchId);
            $branchCode = $branch ? strtoupper(substr($branch->code ?? $branch->name, 0, 3)) : 'BRH';

            $serial = str_pad($counter, 3, '0', STR_PAD_LEFT);

            return sprintf('CS-%s-%s-%s', now()->format('Ymd'), $branchCode, $serial);
        }, 5);
    }
}
