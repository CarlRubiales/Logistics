<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ErpMessage;
use App\Models\Hub;
use App\Models\Parcel;
use App\Models\Remittance;
use App\Models\RemittanceParcel;
use App\Models\RiderProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RemittanceController extends Controller
{
    public static function ensureTables(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('remittances')) {
                \Illuminate\Support\Facades\Schema::create('remittances', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('rider_profile_id')->index();
                    $table->unsignedBigInteger('hub_id')->nullable()->index();
                    $table->string('hub_branch', 100)->index();
                    $table->date('remit_date')->index();
                    $table->string('status', 40)->default('awaiting_cashier')->index();
                    $table->bigInteger('expected_amount_centavos')->default(0);
                    $table->bigInteger('declared_amount_centavos')->default(0);
                    $table->bigInteger('counted_amount_centavos')->nullable();
                    $table->bigInteger('difference_centavos')->default(0);
                    $table->timestamp('submitted_at')->nullable()->index();
                    $table->timestamp('confirmed_at')->nullable()->index();
                    $table->unsignedBigInteger('confirmed_by')->nullable()->index();
                    $table->text('cashier_note')->nullable();
                    $table->text('rider_note')->nullable();
                    $table->string('idempotency_key', 191)->nullable()->unique();
                    $table->timestamps();

                    $table->unique(['rider_profile_id', 'remit_date']);
                });
            }

            if (! \Illuminate\Support\Facades\Schema::hasTable('remittance_parcels')) {
                \Illuminate\Support\Facades\Schema::create('remittance_parcels', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('remittance_id')->index();
                    $table->unsignedBigInteger('parcel_id')->index();
                    $table->string('parcel_number', 100)->index();
                    $table->bigInteger('cod_amount_centavos')->default(0);
                    $table->string('status', 40)->default('delivered');
                    $table->timestamps();

                    $table->unique(['remittance_id', 'parcel_id']);
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('hubs') && ! \Illuminate\Support\Facades\Schema::hasColumn('hubs', 'remit_cutoff_time')) {
                \Illuminate\Support\Facades\Schema::table('hubs', function ($table) {
                    $table->time('remit_cutoff_time')->default('20:00:00')->nullable();
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('RemittanceController::ensureTables: ' . $e->getMessage());
        }
    }

    private function authorizeHubAccess(Request $request): User
    {
        self::ensureTables();

        $user = $request->user();
        abort_unless(
            $user && in_array($user->role, ['admin', 'logistics', 'operations_manager', 'super_admin', 'cashier'], true),
            403,
            'Access denied. Only Hub Admins and Cashiers can access Remittance.'
        );

        return $user;
    }

    public function index(Request $request): View
    {
        try {
            $user = $this->authorizeHubAccess($request);
            $isMasterAdmin = $user->isMasterAdmin();

            $branchName = $isMasterAdmin
                ? ($request->query('hub') ?: ($user->getBranchName() ?: 'Pila'))
                : ($user->getBranchName() ?: 'Pila');

            // Verify hub isolation: non-master users cannot query other hubs
            if (! $isMasterAdmin && $request->filled('hub')) {
                $requestedHub = trim((string) $request->query('hub'));
                if (strcasecmp($requestedHub, $branchName) !== 0) {
                    abort(403, 'Unauthorized. You can only view remittances for your assigned hub (' . $branchName . ').');
                }
            }

            try {
                $hub = Hub::whereRaw('LOWER(branch) = ?', [mb_strtolower($branchName)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($branchName . ' hub')])
                    ->orWhereRaw('LOWER(city) = ?', [mb_strtolower($branchName)])
                    ->first();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RemittanceController hub query: ' . $e->getMessage());
                $hub = null;
            }

            $tz = 'Asia/Manila';
            $todayStr = Carbon::now($tz)->toDateString();
            $dateStr = $request->query('date', $todayStr);

            try {
                $selectedDate = Carbon::createFromFormat('Y-m-d', $dateStr, $tz);
            } catch (\Throwable $e) {
                $selectedDate = Carbon::now($tz);
                $dateStr = $todayStr;
            }

            $cutoffTime = $hub?->remit_cutoff_time ?: '20:00:00';
            $nowTimeStr = Carbon::now($tz)->format('H:i:s');
            $isPastCutoff = $selectedDate->toDateString() === $todayStr && $nowTimeStr >= $cutoffTime;
            try {
                $cutoffFormatted = Carbon::parse($cutoffTime, $tz)->format('g:i A');
            } catch (\Throwable $e) {
                $cutoffFormatted = '8:00 PM';
            }

            try {
                // Fetch all active riders assigned to this hub
                $riders = RiderProfile::with(['user', 'hub'])
                    ->forBranch($branchName)
                    ->where('registration_status', 'approved')
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RemittanceController riders query: ' . $e->getMessage());
                $riders = collect();
            }

            try {
                // Fetch existing remittances for this hub and date
                $remittances = \Illuminate\Support\Facades\Schema::hasTable('remittances')
                    ? Remittance::with(['rider.user', 'cashier', 'parcels'])
                        ->forBranch($branchName)
                        ->forDate($selectedDate)
                        ->get()
                        ->keyBy('rider_profile_id')
                    : collect();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RemittanceController remittances query: ' . $e->getMessage());
                $remittances = collect();
            }

            // Build composite row for each rider
            $riderRows = $riders->map(function (RiderProfile $rider) use ($selectedDate, $remittances, $branchName) {
                try {
                    $remittance = $remittances->get($rider->id);

                    // Fetch parcels for this rider on this date
                    try {
                        $allParcels = Parcel::where('assigned_rider_id', $rider->id)
                            ->forDate($selectedDate)
                            ->get();
                    } catch (\Throwable $e) {
                        $allParcels = collect();
                    }

                    $deliveredCodParcels = $allParcels->filter(function (Parcel $p) {
                        $meta = is_array($p->metadata) ? $p->metadata : (is_string($p->metadata) ? json_decode($p->metadata, true) : []);
                        $meta = is_array($meta) ? $meta : [];
                        $isCod = (bool) ($meta['is_cod'] ?? false);
                        $codVal = (float) ($meta['cod_amount'] ?? ($p->order?->total ?? ($p->order?->total_amount ?? 0)));
                        return $p->status === 'delivered' && $isCod && $codVal > 0;
                    });

                    $totalAssignedCodCount = $allParcels->filter(function (Parcel $p) {
                        $meta = is_array($p->metadata) ? $p->metadata : (is_string($p->metadata) ? json_decode($p->metadata, true) : []);
                        $meta = is_array($meta) ? $meta : [];
                        return (bool) ($meta['is_cod'] ?? false);
                    })->count();

                    $serverExpectedCentavos = $deliveredCodParcels->sum(function (Parcel $p) {
                        $meta = is_array($p->metadata) ? $p->metadata : (is_string($p->metadata) ? json_decode($p->metadata, true) : []);
                        $meta = is_array($meta) ? $meta : [];
                        $codVal = (float) ($meta['cod_amount'] ?? ($p->order?->total ?? ($p->order?->total_amount ?? 0)));
                        return (int) round($codVal * 100);
                    });

                    $expectedAmount = round($serverExpectedCentavos / 100, 2);

                    // If remittance record exists, use its data; otherwise generate virtual row
                    if ($remittance) {
                        $status = (string) ($remittance->status ?? Remittance::STATUS_NOT_REMITTED);
                        $declaredAmount = (float) ($remittance->declared_amount ?? 0.0);
                        $countedAmount = $remittance->counted_amount !== null ? (float) $remittance->counted_amount : null;
                        $difference = (float) ($remittance->difference ?? 0.0);
                        $balanceDue = (float) ($remittance->balance_due ?? 0.0);
                        $remittanceId = $remittance->id;
                        $submittedAt = $remittance->submitted_at;
                        $confirmedAt = $remittance->confirmed_at;
                        $cashierName = $remittance->cashier?->name;
                        $cashierNote = $remittance->cashier_note;
                        $riderNote = $remittance->rider_note;
                        $coveredParcels = $remittance->parcels ?? collect();
                    } else {
                        $status = $expectedAmount > 0 ? Remittance::STATUS_NOT_REMITTED : Remittance::STATUS_NOTHING_DUE;
                        $declaredAmount = 0.00;
                        $countedAmount = null;
                        $difference = 0.00;
                        $balanceDue = $expectedAmount;
                        $remittanceId = null;
                        $submittedAt = null;
                        $confirmedAt = null;
                        $cashierName = null;
                        $cashierNote = null;
                        $riderNote = null;
                        $coveredParcels = collect();
                    }

                    return (object) [
                        'rider_id' => $rider->id,
                        'remittance_id' => $remittanceId,
                        'name' => $rider->user?->name ?? 'Courier',
                        'phone' => $rider->phone ?: ($rider->user?->email ?? ''),
                        'delivered_cod_count' => $deliveredCodParcels->count(),
                        'total_cod_count' => max($totalAssignedCodCount, $deliveredCodParcels->count()),
                        'expected_amount' => (float) $expectedAmount,
                        'declared_amount' => (float) $declaredAmount,
                        'counted_amount' => $countedAmount,
                        'difference' => (float) $difference,
                        'balance_due' => (float) $balanceDue,
                        'status' => $status,
                        'status_label' => match ($status) {
                            Remittance::STATUS_NOT_REMITTED => 'Not remitted',
                            Remittance::STATUS_AWAITING_CASHIER => 'Awaiting cashier',
                            Remittance::STATUS_REMITTED => 'Remitted',
                            Remittance::STATUS_SHORT => 'Short',
                            Remittance::STATUS_OVER => 'Over',
                            Remittance::STATUS_NOTHING_DUE => 'Nothing due',
                            default => ucfirst(str_replace('_', ' ', $status)),
                        },
                        'submitted_at' => $submittedAt,
                        'confirmed_at' => $confirmedAt,
                        'cashier_name' => $cashierName,
                        'cashier_note' => $cashierNote,
                        'rider_note' => $riderNote,
                        'delivered_cod_parcels' => $deliveredCodParcels->values(),
                        'covered_parcels' => $coveredParcels,
                    ];
                } catch (\Throwable $riderErr) {
                    \Illuminate\Support\Facades\Log::warning("Remittance row error for rider {$rider->id}: " . $riderErr->getMessage());
                    return (object) [
                        'rider_id' => $rider->id,
                        'remittance_id' => null,
                        'name' => $rider->user?->name ?? 'Courier',
                        'phone' => $rider->phone ?: '',
                        'delivered_cod_count' => 0,
                        'total_cod_count' => 0,
                        'expected_amount' => 0.00,
                        'declared_amount' => 0.00,
                        'counted_amount' => null,
                        'difference' => 0.00,
                        'balance_due' => 0.00,
                        'status' => Remittance::STATUS_NOTHING_DUE,
                        'status_label' => 'Nothing due',
                        'submitted_at' => null,
                        'confirmed_at' => null,
                        'cashier_name' => null,
                        'cashier_note' => null,
                        'rider_note' => null,
                        'delivered_cod_parcels' => collect(),
                        'covered_parcels' => collect(),
                    ];
                }
            });

            // Summary Cards Calculations
            $totalCodCollected = (float) $riderRows->sum('expected_amount');
            $codRidersCount = $riderRows->where('expected_amount', '>', 0)->count();
            $codParcelsTotal = (int) $riderRows->sum('delivered_cod_count');

            $remittedConfirmedAmount = (float) $riderRows->whereIn('status', [Remittance::STATUS_REMITTED, Remittance::STATUS_SHORT, Remittance::STATUS_OVER])
                ->sum('counted_amount');

            $awaitingCashierAmount = (float) $riderRows->where('status', Remittance::STATUS_AWAITING_CASHIER)
                ->sum('declared_amount');

            $notRemittedAmount = (float) $riderRows->where('status', Remittance::STATUS_NOT_REMITTED)
                ->sum('expected_amount');

            $shortagesSum = (float) $riderRows->where('status', Remittance::STATUS_SHORT)
                ->sum(fn ($r) => abs((float) ($r->difference ?? 0)));

            $totalNotRemittedIncludingShort = $notRemittedAmount + $shortagesSum;

            // Status Tabs Counts
            $tabCounts = [
                'all' => $riderRows->count(),
                'not_remitted' => $riderRows->where('status', Remittance::STATUS_NOT_REMITTED)->count(),
                'awaiting_cashier' => $riderRows->where('status', Remittance::STATUS_AWAITING_CASHIER)->count(),
                'confirmed' => $riderRows->whereIn('status', [Remittance::STATUS_REMITTED, Remittance::STATUS_SHORT, Remittance::STATUS_OVER])->count(),
            ];

            // Filter Rows by Tab
            $currentTab = $request->query('tab', 'all');
            $search = strtolower(trim((string) $request->query('q', '')));

            $filteredRows = $riderRows->filter(function ($r) use ($currentTab, $search) {
                if ($currentTab === 'not_remitted' && $r->status !== Remittance::STATUS_NOT_REMITTED) return false;
                if ($currentTab === 'awaiting_cashier' && $r->status !== Remittance::STATUS_AWAITING_CASHIER) return false;
                if ($currentTab === 'confirmed' && ! in_array($r->status, [Remittance::STATUS_REMITTED, Remittance::STATUS_SHORT, Remittance::STATUS_OVER], true)) return false;

                if ($search !== '') {
                    $haystack = strtolower(($r->name ?? '') . ' ' . ($r->phone ?? ''));
                    if (! str_contains($haystack, $search)) return false;
                }

                return true;
            })->values();

            // Selected row for detail panel (default: first awaiting cashier, or requested rider_id, or first row)
            $selectedRiderId = (int) $request->query('rider_id', 0);
            $selectedRow = $selectedRiderId > 0
                ? ($riderRows->firstWhere('rider_id', $selectedRiderId) ?? $filteredRows->first())
                : ($filteredRows->firstWhere('status', Remittance::STATUS_AWAITING_CASHIER) ?? $filteredRows->first());

            // Activity Today Feed
            try {
                $activityLogs = AuditLog::with('user')
                    ->where('auditable_type', Remittance::class)
                    ->whereDate('created_at', $selectedDate->format('Y-m-d'))
                    ->latest('id')
                    ->take(15)
                    ->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('RemittanceController activityLogs: ' . $e->getMessage());
                $activityLogs = collect();
            }

            try {
                $activeHubs = Hub::where('is_active', true)->get();
            } catch (\Throwable $e) {
                $activeHubs = collect();
            }

            return view('remittance.index', [
                'hubName' => $branchName,
                'hub' => $hub,
                'isMasterAdmin' => $isMasterAdmin,
                'activeHubs' => $activeHubs,
                'selectedDate' => $selectedDate,
                'dateStr' => $dateStr,
                'todayStr' => $todayStr,
                'isPastCutoff' => $isPastCutoff,
                'cutoffFormatted' => $cutoffFormatted,
                'cutoffTime' => $cutoffTime,
                'totalCodCollected' => $totalCodCollected,
                'codRidersCount' => $codRidersCount,
                'codParcelsTotal' => $codParcelsTotal,
                'remittedConfirmedAmount' => $remittedConfirmedAmount,
                'awaitingCashierAmount' => $awaitingCashierAmount,
                'notRemittedAmount' => $notRemittedAmount,
                'shortagesSum' => $shortagesSum,
                'totalNotRemittedIncludingShort' => $totalNotRemittedIncludingShort,
                'tabCounts' => $tabCounts,
                'currentTab' => $currentTab,
                'search' => $search,
                'riderRows' => $riderRows,
                'filteredRows' => $filteredRows,
                'selectedRow' => $selectedRow,
                'activityLogs' => $activityLogs,
            ]);
        } catch (\Throwable $fatalError) {
            \Illuminate\Support\Facades\Log::error('RemittanceController::index fatal error caught: ' . $fatalError->getMessage() . "\n" . $fatalError->getTraceAsString());

            $tz = 'Asia/Manila';
            $today = Carbon::now($tz);
            $userBranch = $request->user()?->getBranchName() ?: 'Pila';

            return view('remittance.index', [
                'hubName' => $userBranch,
                'hub' => null,
                'isMasterAdmin' => (bool) ($request->user()?->isMasterAdmin() ?? false),
                'activeHubs' => collect(),
                'selectedDate' => $today,
                'dateStr' => $today->toDateString(),
                'todayStr' => $today->toDateString(),
                'isPastCutoff' => false,
                'cutoffFormatted' => '8:00 PM',
                'cutoffTime' => '20:00:00',
                'totalCodCollected' => 0.00,
                'codRidersCount' => 0,
                'codParcelsTotal' => 0,
                'remittedConfirmedAmount' => 0.00,
                'awaitingCashierAmount' => 0.00,
                'notRemittedAmount' => 0.00,
                'shortagesSum' => 0.00,
                'totalNotRemittedIncludingShort' => 0.00,
                'tabCounts' => ['all' => 0, 'not_remitted' => 0, 'awaiting_cashier' => 0, 'confirmed' => 0],
                'currentTab' => 'all',
                'search' => '',
                'riderRows' => collect(),
                'filteredRows' => collect(),
                'selectedRow' => null,
                'activityLogs' => collect(),
            ])->withErrors(['error' => 'Notice: Remittance data is initializing. Details: ' . $fatalError->getMessage()]);
        }
    }

    public function confirm(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $this->authorizeHubAccess($request);

        $remittance = Remittance::with(['rider.user', 'parcels.parcel'])->findOrFail($id);

        if (! $user->isMasterAdmin() && strcasecmp($remittance->hub_branch, $user->getBranchName()) !== 0) {
            abort(403, 'Unauthorized to confirm remittances outside your branch hub.');
        }

        $validated = $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'action_type' => ['nullable', 'string', 'in:confirm,discrepancy'],
        ]);

        $countedCentavos = (int) round(((float) $validated['counted_amount']) * 100);
        $expectedCentavos = $remittance->expected_amount_centavos;
        $diffCentavos = $countedCentavos - $expectedCentavos;

        if ($diffCentavos === 0) {
            $newStatus = Remittance::STATUS_REMITTED;
        } elseif ($diffCentavos < 0) {
            $newStatus = Remittance::STATUS_SHORT;
        } else {
            $newStatus = Remittance::STATUS_OVER;
        }

        if ($newStatus !== Remittance::STATUS_REMITTED && empty($validated['note'])) {
            return back()->withErrors(['note' => 'A note explaining the discrepancy is required when reporting shortages or overages.']);
        }

        $remittance->update([
            'counted_amount_centavos' => $countedCentavos,
            'difference_centavos' => $diffCentavos,
            'status' => $newStatus,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
            'cashier_note' => $validated['note'] ?? null,
        ]);

        // Flag parcels as remitted
        foreach ($remittance->parcels as $rp) {
            if ($rp->parcel) {
                $meta = $rp->parcel->metadata ?? [];
                $meta['is_cod_remitted'] = true;
                $meta['cod_remit_status'] = $newStatus;
                $meta['cod_confirmed_by'] = $user->name;
                $meta['cod_confirmed_at'] = now()->toIso8601String();
                $rp->parcel->metadata = $meta;
                $rp->parcel->save();
            }
        }

        // Write Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'event' => 'remittance_' . $newStatus,
            'auditable_type' => Remittance::class,
            'auditable_id' => $remittance->id,
            'metadata' => [
                'branch' => $remittance->hub_branch,
                'cashier_name' => $user->name,
                'rider_name' => $remittance->rider?->user?->name,
                'expected_amount' => $remittance->expected_amount,
                'declared_amount' => $remittance->declared_amount,
                'counted_amount' => $remittance->counted_amount,
                'difference' => $remittance->difference,
                'status' => $newStatus,
                'note' => $validated['note'] ?? null,
            ],
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Remittance confirmed as ' . $remittance->status_label . '.',
                'remittance' => $remittance,
            ]);
        }

        return back()->with('status', 'Remittance for ' . ($remittance->rider?->user?->name ?? 'rider') . ' confirmed as ' . $remittance->status_label . '.');
    }

    public function remindUnremitted(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->authorizeHubAccess($request);
        $branchName = $user->isMasterAdmin() ? ($request->input('hub') ?: 'Pila') : ($user->getBranchName() ?: 'Pila');

        $tz = 'Asia/Manila';
        $todayStr = Carbon::now($tz)->toDateString();
        $dateStr = $request->input('date', $todayStr);

        $unremittedRiders = RiderProfile::with(['user', 'hub'])
            ->forBranch($branchName)
            ->where('registration_status', 'approved')
            ->get()
            ->filter(function (RiderProfile $rider) use ($dateStr) {
                $remittance = Remittance::where('rider_profile_id', $rider->id)
                    ->whereDate('remit_date', $dateStr)
                    ->first();

                if ($remittance && in_array($remittance->status, [Remittance::STATUS_REMITTED, Remittance::STATUS_NOTHING_DUE], true)) {
                    return false;
                }

                $deliveredCodCentavos = Parcel::where('assigned_rider_id', $rider->id)
                    ->where('status', 'delivered')
                    ->forDate($dateStr)
                    ->get()
                    ->sum(function (Parcel $p) {
                        $meta = is_array($p->metadata) ? $p->metadata : (is_string($p->metadata) ? json_decode($p->metadata, true) : []);
                        $meta = is_array($meta) ? $meta : [];
                        return ((bool) ($meta['is_cod'] ?? false)) ? (int) round(((float) ($meta['cod_amount'] ?? 0)) * 100) : 0;
                    });

                return $deliveredCodCentavos > 0;
            });

        $count = 0;
        foreach ($unremittedRiders as $rider) {
            if ($rider->user) {
                // Send in-app operations message to rider
                ErpMessage::create([
                    'sender_id' => $user->id,
                    'recipient_id' => $rider->user->id,
                    'body' => 'Hub Cashier Notice: Please surrender all collected COD cash for today\'s delivery run to the cashier desk at ' . $branchName . ' Hub.',
                    'message_type' => 'announcement',
                    'is_read' => false,
                ]);
                $count++;
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'event' => 'remittance_reminder_broadcast',
            'auditable_type' => Remittance::class,
            'auditable_id' => 0,
            'metadata' => [
                'branch' => $branchName,
                'reminded_riders_count' => $count,
                'date' => $dateStr,
            ],
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Reminders sent to {$count} unremitted rider(s).",
                'count' => $count,
            ]);
        }

        return back()->with('status', "Reminders sent to {$count} unremitted rider(s).");
    }

    public function updateCutoff(Request $request): RedirectResponse
    {
        $user = $this->authorizeHubAccess($request);
        $branchName = $user->isMasterAdmin() ? ($request->input('hub') ?: 'Pila') : ($user->getBranchName() ?: 'Pila');

        $validated = $request->validate([
            'remit_cutoff_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ]);

        $hub = Hub::whereRaw('LOWER(branch) = ?', [mb_strtolower($branchName)])->first();
        if ($hub) {
            $formattedTime = strlen($validated['remit_cutoff_time']) === 5 ? ($validated['remit_cutoff_time'] . ':00') : $validated['remit_cutoff_time'];
            $hub->update(['remit_cutoff_time' => $formattedTime]);
        }

        return back()->with('status', 'Hub remittance cut-off time updated.');
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $this->authorizeHubAccess($request);
        $branchName = $user->isMasterAdmin() ? ($request->query('hub') ?: 'Pila') : ($user->getBranchName() ?: 'Pila');

        $tz = 'Asia/Manila';
        $todayStr = Carbon::now($tz)->toDateString();

        $hasRemitTable = \Illuminate\Support\Facades\Schema::hasTable('remittances');

        $awaitingCount = $hasRemitTable
            ? Remittance::forBranch($branchName)
                ->whereDate('remit_date', $todayStr)
                ->where('status', Remittance::STATUS_AWAITING_CASHIER)
                ->count()
            : 0;

        $hub = Hub::whereRaw('LOWER(branch) = ?', [mb_strtolower($branchName)])->first();
        $cutoffTime = $hub?->remit_cutoff_time ?: '20:00:00';
        $isPastCutoff = Carbon::now($tz)->format('H:i:s') >= $cutoffTime;

        // Latest submission in the last 2 hours for live banner
        $latest = $hasRemitTable
            ? Remittance::with('rider.user')
                ->forBranch($branchName)
                ->whereDate('remit_date', $todayStr)
                ->where('status', Remittance::STATUS_AWAITING_CASHIER)
                ->latest('submitted_at')
                ->first()
            : null;

        try {
            $cutoffFormatted = Carbon::parse($cutoffTime, $tz)->format('g:i A');
        } catch (\Throwable $e) {
            $cutoffFormatted = '8:00 PM';
        }

        return response()->json([
            'status' => 'success',
            'awaiting_count' => $awaitingCount,
            'is_past_cutoff' => $isPastCutoff,
            'cutoff_formatted' => $cutoffFormatted,
            'latest_remit' => $latest ? [
                'id' => $latest->id,
                'rider_id' => $latest->rider_profile_id,
                'rider_name' => $latest->rider?->user?->name ?? 'Courier',
                'declared_amount' => $latest->formatted_declared,
                'time' => $latest->submitted_at ? $latest->submitted_at->setTimezone($tz)->format('g:i A') : 'Just now',
            ] : null,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $this->authorizeHubAccess($request);
        $branchName = $user->isMasterAdmin() ? ($request->query('hub') ?: 'Pila') : ($user->getBranchName() ?: 'Pila');

        $tz = 'Asia/Manila';
        $todayStr = Carbon::now($tz)->toDateString();
        $dateStr = $request->query('date', $todayStr);

        $riders = RiderProfile::with(['user', 'hub'])
            ->forBranch($branchName)
            ->where('registration_status', 'approved')
            ->get();

        $hasRemitTable = \Illuminate\Support\Facades\Schema::hasTable('remittances');
        $remittances = $hasRemitTable
            ? Remittance::with(['rider.user', 'cashier', 'parcels'])
                ->forBranch($branchName)
                ->forDate($dateStr)
                ->get()
                ->keyBy('rider_profile_id')
            : collect();

        $filename = "remittance_{$branchName}_{$dateStr}.csv";

        return response()->streamDownload(function () use ($riders, $remittances, $dateStr, $branchName) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Rider Name',
                'Phone',
                'Branch Hub',
                'Date',
                'Delivered COD Parcels',
                'Expected COD Amount (PHP)',
                'Declared Amount (PHP)',
                'Counted Amount (PHP)',
                'Difference (PHP)',
                'Balance Due (PHP)',
                'Status',
                'Submitted At',
                'Confirmed At',
                'Confirmed By Cashier',
                'Notes',
            ]);

            foreach ($riders as $rider) {
                $remittance = $remittances->get($rider->id);

                $deliveredCodCentavos = Parcel::where('assigned_rider_id', $rider->id)
                    ->where('status', 'delivered')
                    ->forDate($dateStr)
                    ->get()
                    ->sum(function (Parcel $p) {
                        $meta = is_array($p->metadata) ? $p->metadata : (is_string($p->metadata) ? json_decode($p->metadata, true) : []);
                        $meta = is_array($meta) ? $meta : [];
                        return ((bool) ($meta['is_cod'] ?? false)) ? (int) round(((float) ($meta['cod_amount'] ?? 0)) * 100) : 0;
                    });

                $expected = round($deliveredCodCentavos / 100, 2);

                if ($remittance) {
                    $status = $remittance->status_label;
                    $declared = $remittance->declared_amount;
                    $counted = $remittance->counted_amount !== null ? $remittance->counted_amount : '—';
                    $diff = $remittance->difference;
                    $balanceDue = $remittance->balance_due;
                    $submittedAt = $remittance->submitted_at ? $remittance->submitted_at->format('Y-m-d H:i') : '—';
                    $confirmedAt = $remittance->confirmed_at ? $remittance->confirmed_at->format('Y-m-d H:i') : '—';
                    $cashier = $remittance->cashier?->name ?? '—';
                    $notes = $remittance->cashier_note ?: $remittance->rider_note ?: '';
                    $deliveredCount = $remittance->parcels()->count();
                } else {
                    $status = $expected > 0 ? 'Not remitted' : 'Nothing due';
                    $declared = 0.00;
                    $counted = '—';
                    $diff = 0.00;
                    $balanceDue = $expected;
                    $submittedAt = '—';
                    $confirmedAt = '—';
                    $cashier = '—';
                    $notes = '';
                    $deliveredCount = 0;
                }

                fputcsv($handle, [
                    $rider->user?->name ?? 'Courier',
                    $rider->phone,
                    $branchName,
                    $dateStr,
                    $deliveredCount,
                    number_format($expected, 2, '.', ''),
                    number_format($declared, 2, '.', ''),
                    is_numeric($counted) ? number_format($counted, 2, '.', '') : $counted,
                    number_format($diff, 2, '.', ''),
                    number_format($balanceDue, 2, '.', ''),
                    $status,
                    $submittedAt,
                    $confirmedAt,
                    $cashier,
                    $notes,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
