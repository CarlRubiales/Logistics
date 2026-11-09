<?php

namespace App\Services;

use App\Http\Controllers\ExceptionsController;
use App\Models\CommissionRule;
use App\Models\Parcel;
use App\Models\RiderCommission;
use App\Models\RiderProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CommissionCalculatorService
{
    /**
     * Build the list of selectable pay periods based on the configured period_type
     * ('semi_monthly' = 1st–15th and 16th–end of month, 'monthly', or 'weekly'),
     * plus any period keys already stored in rider_commissions.
     *
     * @return Collection<int, array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    public function availablePeriods(?string $periodType = null): Collection
    {
        $rule = CommissionRule::current();
        $cadence = $periodType ?: ($rule->period_type ?: 'semi_monthly');
        $now = Carbon::now('Asia/Manila');

        $periods = collect();

        if ($cadence === 'daily') {
            for ($i = 0; $i < 30; $i++) {
                $day = $now->copy()->subDays($i);
                $key = $day->format('Y-m-d') . '-D';
                $label = $day->isToday()
                    ? 'Today (' . $day->format('M j, Y') . ') · Daily'
                    : ($day->isYesterday()
                        ? 'Yesterday (' . $day->format('M j, Y') . ') · Daily'
                        : $day->format('D, M j, Y') . ' · Daily');
                $periods->push([
                    'key' => $key,
                    'label' => $label,
                    'start' => $day->copy()->startOfDay(),
                    'end' => $day->copy()->endOfDay(),
                ]);
            }
        } elseif ($cadence === 'monthly') {
            for ($i = 0; $i < 6; $i++) {
                $month = $now->copy()->subMonthsNoOverflow($i);
                $start = $month->copy()->startOfMonth()->startOfDay();
                $end = $month->copy()->endOfMonth()->endOfDay();
                $key = $month->format('Y-m') . '-M';
                $label = $month->format('M 1') . '–' . $end->format('j, Y') . ' (Monthly)';
                $periods->push([
                    'key' => $key,
                    'label' => $label,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
        } elseif ($cadence === 'weekly') {
            for ($i = 0; $i < 8; $i++) {
                $week = $now->copy()->subWeeks($i);
                $start = $week->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end = $week->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $key = $start->format('Y-m-d') . '_W';
                $label = $start->format('M j') . '–' . $end->format('M j, Y');
                $periods->push([
                    'key' => $key,
                    'label' => $label,
                    'start' => $start,
                    'end' => $end,
                ]);
            }
        } else {
            // Default: Semi-monthly (1st–15th and 16th–end of month)
            for ($i = 0; $i < 6; $i++) {
                $month = $now->copy()->subMonthsNoOverflow($i);
                $yearMonth = $month->format('Y-m');
                $monthShort = $month->format('M');
                $year = $month->format('Y');
                $daysInMonth = $month->daysInMonth;

                // If current month and today is <= 15, put 1st–15th first, otherwise include 16th–end first
                if ($i > 0 || $now->day >= 16) {
                    $periods->push([
                        'key' => "{$yearMonth}-H2",
                        'label' => "{$monthShort} 16–{$daysInMonth}, {$year}",
                        'start' => $month->copy()->day(16)->startOfDay(),
                        'end' => $month->copy()->endOfMonth()->endOfDay(),
                    ]);
                }

                $periods->push([
                    'key' => "{$yearMonth}-H1",
                    'label' => "{$monthShort} 1–15, {$year}",
                    'start' => $month->copy()->day(1)->startOfDay(),
                    'end' => $month->copy()->day(15)->endOfDay(),
                ]);
            }
        }

        // Also include any historical periods that have existing records in rider_commissions
        if (Schema::hasTable('rider_commissions')) {
            $existingPeriods = RiderCommission::query()
                ->select('period_key', 'period_label', 'period_start', 'period_end')
                ->distinct()
                ->orderByDesc('period_start')
                ->get();

            foreach ($existingPeriods as $ep) {
                if (! $periods->contains(fn (array $p) => $p['key'] === $ep->period_key)) {
                    $periods->push([
                        'key' => $ep->period_key,
                        'label' => $ep->period_label,
                        'start' => Carbon::parse($ep->period_start, 'Asia/Manila')->startOfDay(),
                        'end' => Carbon::parse($ep->period_end, 'Asia/Manila')->endOfDay(),
                    ]);
                }
            }
        }

        return $periods->values();
    }

    /**
     * Resolve a period descriptor array by key, or return the current default period.
     *
     * @return array{key: string, label: string, start: Carbon, end: Carbon}
     */
    public function resolvePeriod(?string $periodKey = null): array
    {
        $periods = $this->availablePeriods();

        if ($periodKey !== null && $periodKey !== '') {
            $found = $periods->firstWhere('key', $periodKey);
            if ($found) {
                return $found;
            }

            // Parse custom/historical daily YYYY-MM-DD(-D) keys on the fly
            if (preg_match('/^(\d{4}-\d{2}-\d{2})(-D)?$/', (string) $periodKey, $m)) {
                $day = Carbon::createFromFormat('Y-m-d', $m[1], 'Asia/Manila');
                $label = $day->isToday()
                    ? 'Today (' . $day->format('M j, Y') . ') · Daily'
                    : ($day->isYesterday()
                        ? 'Yesterday (' . $day->format('M j, Y') . ') · Daily'
                        : $day->format('D, M j, Y') . ' · Daily');
                return [
                    'key' => $m[1] . '-D',
                    'label' => $label,
                    'start' => $day->copy()->startOfDay(),
                    'end' => $day->copy()->endOfDay(),
                ];
            }

            // Parse custom/historical YYYY-MM-H1 / YYYY-MM-H2 keys on the fly
            if (preg_match('/^(\d{4})-(\d{2})-(H1|H2|M)$/', $periodKey, $m)) {
                $month = Carbon::createFromDate((int) $m[1], (int) $m[2], 1, 'Asia/Manila');
                if ($m[3] === 'H1') {
                    return [
                        'key' => $periodKey,
                        'label' => $month->format('M') . ' 1–15, ' . $month->format('Y'),
                        'start' => $month->copy()->day(1)->startOfDay(),
                        'end' => $month->copy()->day(15)->endOfDay(),
                    ];
                }
                if ($m[3] === 'H2') {
                    $lastDay = $month->daysInMonth;
                    return [
                        'key' => $periodKey,
                        'label' => $month->format('M') . " 16–{$lastDay}, " . $month->format('Y'),
                        'start' => $month->copy()->day(16)->startOfDay(),
                        'end' => $month->copy()->endOfMonth()->endOfDay(),
                    ];
                }
                return [
                    'key' => $periodKey,
                    'label' => $month->format('M 1') . '–' . $month->endOfMonth()->format('j, Y') . ' (Monthly)',
                    'start' => $month->copy()->startOfMonth()->startOfDay(),
                    'end' => $month->copy()->endOfMonth()->endOfDay(),
                ];
            }
        }

        // If the current period has 0 parcels so far, check if a period with parcel activity exists
        // while still defaulting to the current semi-monthly period when appropriate.
        return $periods->first();
    }

    /**
     * Determine whether a Parcel is a Return / Refund case caused by rider fault.
     */
    public static function isRiderFaultReturn(Parcel $parcel): bool
    {
        $meta = is_array($parcel->metadata) ? $parcel->metadata : [];

        $isReturnCase = in_array($parcel->status, ['exception', 'failed', 'returned'], true)
            || ! empty($meta['return_reason'])
            || ! empty($meta['failure_reason'])
            || ! empty($meta['return_status'])
            || ! empty($meta['is_return_refund']);

        if (! $isReturnCase) {
            return false;
        }

        // If an admin explicitly set rider_fault on the parcel metadata, respect that flag
        if (array_key_exists('rider_fault', $meta) && $meta['rider_fault'] !== null) {
            return (bool) $meta['rider_fault'];
        }

        // Rejected return/refund requests do not penalize the rider unless explicitly marked rider_fault
        $returnStatus = ExceptionsController::resolveReturnStatus($parcel);
        if ($returnStatus === 'rejected') {
            return false;
        }

        $reason = strtolower(trim((string) ($meta['return_reason'] ?? $meta['failure_reason'] ?? '')));

        return str_contains($reason, 'damaged')
            || str_contains($reason, 'defective')
            || str_contains($reason, 'rider')
            || str_contains($reason, 'mishandled')
            || str_contains($reason, 'lost');
    }

    /**
     * Determine whether a delivered parcel was delivered on time (within 24h of assignment/receipt).
     */
    public static function isDeliveredOnTime(Parcel $parcel): bool
    {
        if ($parcel->status !== 'delivered') {
            return false;
        }

        $meta = is_array($parcel->metadata) ? $parcel->metadata : [];
        if (array_key_exists('delivered_on_time', $meta)) {
            return (bool) $meta['delivered_on_time'];
        }

        $startTime = $parcel->rider_received_at
            ?: $parcel->assigned_at
            ?: $parcel->scanned_at
            ?: $parcel->received_at
            ?: $parcel->created_at;

        $endTime = ! empty($meta['delivered_at'])
            ? Carbon::parse($meta['delivered_at'])
            : ($parcel->updated_at ?: Carbon::now());

        if (! $startTime || ! $endTime) {
            return true;
        }

        return $startTime->diffInHours($endTime, false) <= 24;
    }

    /**
     * Resolve the hub branch name for a RiderProfile.
     */
    public static function resolveRiderHub(RiderProfile $rider): string
    {
        if ($rider->relationLoaded('hub') && $rider->hub) {
            $b = $rider->hub->branch ?: $rider->hub->city;
            if (! empty($b)) {
                return trim((string) $b);
            }
        }

        if ($rider->relationLoaded('user') && $rider->user && ! empty($rider->user->branch)) {
            return trim((string) $rider->user->branch);
        }

        if ($rider->relationLoaded('areas') && $rider->areas->isNotEmpty()) {
            $areaBranch = $rider->areas->first()->branch ?: $rider->areas->first()->city;
            if (! empty($areaBranch)) {
                return trim((string) $areaBranch);
            }
        }

        if ($rider->user) {
            return $rider->user->getBranchName() ?: 'Pila';
        }

        return 'Pila';
    }

    /**
     * Synchronize and compute RiderCommission records for all approved riders in the given period.
     * IMPORTANT: Rows already in 'approved' or 'paid' status keep their frozen rules_snapshot and amounts.
     *
     * @param array{key: string, label: string, start: Carbon, end: Carbon} $period
     */
    public function syncPeriodCommissions(array $period): void
    {
        if (! Schema::hasTable('rider_commissions') || ! Schema::hasTable('commission_rules')) {
            return;
        }

        $rule = CommissionRule::current();
        $snapshot = $rule->toSnapshotArray();

        $startUtc = $period['start']->copy()->setTimezone('UTC');
        $endUtc = $period['end']->copy()->setTimezone('UTC');
        $startDateStr = $period['start']->toDateString();
        $endDateStr = $period['end']->toDateString();

        // Fetch all approved riders + any riders with parcels in the period
        $riders = RiderProfile::query()
            ->with(['user', 'hub', 'areas'])
            ->where(function ($q) use ($startUtc, $endUtc) {
                $q->where('registration_status', 'approved')
                    ->orWhereHas('parcels', function ($pq) use ($startUtc, $endUtc) {
                        $pq->whereBetween('updated_at', [$startUtc, $endUtc])
                            ->orWhereBetween('created_at', [$startUtc, $endUtc]);
                    });
            })
            ->get();

        if ($riders->isEmpty()) {
            return;
        }

        // Fetch existing commission rows for this period
        $existingByRider = RiderCommission::query()
            ->where('period_key', $period['key'])
            ->get()
            ->keyBy('rider_profile_id');

        // Fetch all parcels assigned to these riders that were active in this period
        $riderIds = $riders->pluck('id')->all();
        $parcelsInPeriod = Parcel::query()
            ->whereIn('assigned_rider_id', $riderIds)
            ->where(function ($q) use ($startUtc, $endUtc, $startDateStr, $endDateStr) {
                $q->whereBetween('updated_at', [$startUtc, $endUtc])
                    ->orWhereBetween('assigned_at', [$startUtc, $endUtc])
                    ->orWhereBetween('created_at', [$startUtc, $endUtc])
                    ->orWhere(function ($dq) use ($startDateStr, $endDateStr) {
                        $dq->whereDate('updated_at', '>=', $startDateStr)
                            ->whereDate('updated_at', '<=', $endDateStr);
                    });
            })
            ->get()
            ->groupBy('assigned_rider_id');

        foreach ($riders as $rider) {
            /** @var RiderCommission|null $existing */
            $existing = $existingByRider->get($rider->id);

            $riderParcels = $parcelsInPeriod->get($rider->id, collect());

            $deliveredParcels = $riderParcels->where('status', 'delivered');
            $deliveredCount = $deliveredParcels->count();

            // If already approved/paid and no new parcels delivered, preserve frozen snapshot
            if ($existing && in_array($existing->status, ['approved', 'paid'], true) && $deliveredCount <= $existing->delivered_count) {
                continue;
            }

            $onTimeCount = $deliveredParcels->filter(fn (Parcel $p) => self::isDeliveredOnTime($p))->count();
            $onTimeRateBp = $deliveredCount > 0
                ? (int) round(($onTimeCount / $deliveredCount) * 10000)
                : 0;
            $onTimeRatePct = $onTimeRateBp / 100;

            $faultReturnParcels = $riderParcels->filter(fn (Parcel $p) => self::isRiderFaultReturn($p));
            $returnFaultCount = $faultReturnParcels->count();
            $returnParcelIds = $faultReturnParcels->pluck('id')->values()->all();

            // Riders with no deliveries and no deductions in this period get no commission record
            if ($deliveredCount === 0 && $returnFaultCount === 0) {
                if ($existing && $existing->status === 'pending') {
                    $existing->delete();
                }
                continue;
            }

            $baseCentavos = $deliveredCount * (int) $rule->base_rate_centavos;

            $bonusCentavos = 0;
            if ($deliveredCount > 0) {
                if ($onTimeRatePct >= (int) $rule->bonus_tier2_rate_pct) {
                    $bonusCentavos = (int) $rule->bonus_tier2_centavos;
                } elseif ($onTimeRatePct >= (int) $rule->bonus_tier1_rate_pct) {
                    $bonusCentavos = (int) $rule->bonus_tier1_centavos;
                }
            }

            $deductionsCentavos = $returnFaultCount * (int) $rule->deduction_per_return_centavos;
            $netCentavos = max(0, $baseCentavos + $bonusCentavos - $deductionsCentavos);

            $hubBranch = self::resolveRiderHub($rider);

            RiderCommission::query()->updateOrCreate(
                [
                    'rider_profile_id' => $rider->id,
                    'period_key' => $period['key'],
                ],
                [
                    'hub_branch' => $hubBranch,
                    'period_label' => $period['label'],
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                    'delivered_count' => $deliveredCount,
                    'on_time_count' => $onTimeCount,
                    'on_time_rate_bp' => $onTimeRateBp,
                    'return_fault_count' => $returnFaultCount,
                    'return_parcel_ids' => $returnParcelIds,
                    'base_centavos' => $baseCentavos,
                    'bonus_centavos' => $bonusCentavos,
                    'deductions_centavos' => $deductionsCentavos,
                    'net_centavos' => $netCentavos,
                    'rules_snapshot' => $snapshot,
                    'status' => $existing ? $existing->status : 'pending',
                    'is_flagged' => $existing ? $existing->is_flagged : false,
                    'flagged_note' => $existing ? $existing->flagged_note : null,
                    'flagged_at' => $existing ? $existing->flagged_at : null,
                    'flagged_by' => $existing ? $existing->flagged_by : null,
                ]
            );
        }
    }

    /**
     * Count the number of riders in Pending status for the selected (or current) pay period.
     * Used for the Sidebar badge on "Commission".
     */
    public static function pendingRidersCount(?string $periodKey = null, ?string $branch = null): int
    {
        try {
            if (! Schema::hasTable('rider_commissions')) {
                return 0;
            }

            $service = app(self::class);
            $resolvedKey = $periodKey ?: request()->query('period');
            $period = $service->resolvePeriod(is_string($resolvedKey) ? $resolvedKey : null);

            $existsForPeriod = RiderCommission::query()
                ->where('period_key', $period['key'])
                ->exists();

            if (! $existsForPeriod) {
                $service->syncPeriodCommissions($period);
            }

            $query = RiderCommission::query()
                ->where('period_key', $period['key'])
                ->where('status', 'pending');

            if (! empty($branch)) {
                $query->whereRaw('LOWER(hub_branch) = ?', [strtolower(trim($branch))]);
            }

            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
