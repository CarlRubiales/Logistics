<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CommissionRule;
use App\Models\DestinationArea;
use App\Models\FulfillmentLocation;
use App\Models\Hub;
use App\Models\Parcel;
use App\Models\RiderCommission;
use App\Models\User;
use App\Services\CommissionCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionController extends Controller
{
    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->isMasterAdmin(),
            403,
            'Access denied. Super Admin privileges are required to view or manage rider commissions.'
        );
    }

    public function index(Request $request, CommissionCalculatorService $calculator): View
    {
        $this->authorizeSuperAdmin($request);

        $rule = CommissionRule::current();
        $periods = $calculator->availablePeriods($rule->period_type);

        $requestedPeriodKey = trim((string) $request->query('period', ''));
        $selectedPeriod = $calculator->resolvePeriod($requestedPeriodKey !== '' ? $requestedPeriodKey : null);

        if (! $periods->contains(fn (array $p) => $p['key'] === $selectedPeriod['key'])) {
            $periods->prepend($selectedPeriod);
        }

        // Synchronize pending rider commission records from real parcel data on the server
        $calculator->syncPeriodCommissions($selectedPeriod);

        $filterHub = trim((string) $request->query('hub', ''));
        $search = trim((string) $request->query('search', ''));
        $activeTab = strtolower(trim((string) $request->query('tab', 'all')));
        if (! in_array($activeTab, ['all', 'pending', 'approved', 'paid'], true)) {
            $activeTab = 'all';
        }

        // Load all commission rows for the selected period
        $allPeriodRows = RiderCommission::query()
            ->with(['rider.user', 'approver', 'payer'])
            ->where('period_key', $selectedPeriod['key'])
            ->get()
            ->sortBy([
                fn (RiderCommission $a, RiderCommission $b) => $b->net_centavos <=> $a->net_centavos,
                fn (RiderCommission $a, RiderCommission $b) => strcasecmp($a->rider?->user?->name ?? '', $b->rider?->user?->name ?? ''),
            ])
            ->values();

        // Compute "By hub" horizontal bar breakdown across all hubs for the selected period
        $byHub = $this->computeByHubBreakdown($allPeriodRows);

        // Apply Hub filter
        $periodHubRows = $filterHub !== ''
            ? $allPeriodRows->filter(fn (RiderCommission $rc) => strcasecmp($rc->hub_branch, $filterHub) === 0)->values()
            : $allPeriodRows;

        // 3. Summary Cards for the selected period and hub (computed from real data)
        $pendingRows = $periodHubRows->where('status', 'pending');
        $approvedRows = $periodHubRows->where('status', 'approved');
        $paidRows = $periodHubRows->where('status', 'paid');

        $netCommissionCentavos = (int) $periodHubRows->sum('net_centavos');
        $totalDeliveredCount = (int) $periodHubRows->sum('delivered_count');
        $totalReturnFaultCount = (int) $periodHubRows->sum('return_fault_count');

        $pendingApprovalCentavos = (int) $pendingRows->sum('net_centavos');
        $pendingRidersCount = $pendingRows->count();

        $approvedAwaitingCentavos = (int) $approvedRows->sum('net_centavos');
        $approvedRidersCount = $approvedRows->count();

        $paidOutCentavos = (int) $paidRows->sum('net_centavos');
        $paidRidersCount = $paidRows->count();

        $stats = [
            'net_centavos' => $netCommissionCentavos,
            'net_formatted' => CommissionRule::formatPeso($netCommissionCentavos),
            'delivered_count' => $totalDeliveredCount,
            'return_fault_count' => $totalReturnFaultCount,
            'pending_centavos' => $pendingApprovalCentavos,
            'pending_formatted' => CommissionRule::formatPeso($pendingApprovalCentavos),
            'pending_riders' => $pendingRidersCount,
            'approved_centavos' => $approvedAwaitingCentavos,
            'approved_formatted' => CommissionRule::formatPeso($approvedAwaitingCentavos),
            'approved_riders' => $approvedRidersCount,
            'paid_centavos' => $paidOutCentavos,
            'paid_formatted' => CommissionRule::formatPeso($paidOutCentavos),
            'paid_riders' => $paidRidersCount,
        ];

        // Apply search filter (rider name or hub)
        $searchedRows = $this->applySearchFilter($periodHubRows, $search);

        // 4. Status tabs with live counts
        $tabCounts = [
            'all' => $searchedRows->count(),
            'pending' => $searchedRows->where('status', 'pending')->count(),
            'approved' => $searchedRows->where('status', 'approved')->count(),
            'paid' => $searchedRows->where('status', 'paid')->count(),
        ];

        $filteredRows = $activeTab === 'all'
            ? $searchedRows->values()
            : $searchedRows->where('status', $activeTab)->values();

        // Paginate rider table (12 per page)
        $perPage = 12;
        $currentPage = max(1, (int) $request->query('page', 1));
        $currentItems = $filteredRows->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $currentItems,
            $filteredRows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Hub list for the Hub selector
        $hubs = $this->availableHubs($allPeriodRows);

        // Recent commission audit log entries
        $recentAudits = AuditLog::query()
            ->with('user')
            ->whereIn('auditable_type', [RiderCommission::class, CommissionRule::class])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('commission.index', [
            'commissions' => $paginator,
            'rule' => $rule,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'filterHub' => $filterHub,
            'search' => $search,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
            'stats' => $stats,
            'byHub' => $byHub,
            'hubs' => $hubs,
            'recentAudits' => $recentAudits,
        ]);
    }

    /**
     * Update the Commission Rules (Super Admin only).
     * Changing rules recalculates Pending rows only; Approved and Paid rows keep their stored rules_snapshot.
     */
    public function updateRules(Request $request, CommissionCalculatorService $calculator): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'base_rate_pesos' => ['required', 'numeric', 'min:0', 'max:10000'],
            'bonus_tier1_rate_pct' => ['required', 'integer', 'min:1', 'max:100'],
            'bonus_tier1_pesos' => ['required', 'numeric', 'min:0', 'max:100000'],
            'bonus_tier2_rate_pct' => ['required', 'integer', 'min:1', 'max:100', 'gte:bonus_tier1_rate_pct'],
            'bonus_tier2_pesos' => ['required', 'numeric', 'min:0', 'max:100000'],
            'deduction_per_return_pesos' => ['required', 'numeric', 'min:0', 'max:10000'],
            'period_type' => ['required', 'in:semi_monthly,weekly,monthly'],
        ]);

        $rule = CommissionRule::current();
        $oldSnapshot = $rule->toSnapshotArray();

        $newAttributes = [
            'base_rate_centavos' => (int) round(((float) $validated['base_rate_pesos']) * 100),
            'bonus_tier1_rate_pct' => (int) $validated['bonus_tier1_rate_pct'],
            'bonus_tier1_centavos' => (int) round(((float) $validated['bonus_tier1_pesos']) * 100),
            'bonus_tier2_rate_pct' => (int) $validated['bonus_tier2_rate_pct'],
            'bonus_tier2_centavos' => (int) round(((float) $validated['bonus_tier2_pesos']) * 100),
            'deduction_per_return_centavos' => (int) round(((float) $validated['deduction_per_return_pesos']) * 100),
            'period_type' => $validated['period_type'],
            'updated_by' => $request->user()?->id,
        ];

        if ($rule->exists) {
            $rule->update($newAttributes);
        } else {
            $rule = CommissionRule::query()->create($newAttributes);
        }

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'commission.rules.updated',
            'auditable_type' => CommissionRule::class,
            'auditable_id' => $rule->id,
            'metadata' => [
                'previous' => $oldSnapshot,
                'updated' => $rule->toSnapshotArray(),
                'actor_name' => $request->user()?->name ?? 'Super Admin',
            ],
            'created_at' => now(),
        ]);

        // Re-sync pending commissions for the active period; Approved & Paid rows are untouched
        $periodKey = trim((string) $request->input('period', ''));
        $period = $calculator->resolvePeriod($periodKey !== '' ? $periodKey : null);
        $calculator->syncPeriodCommissions($period);

        return back()->with(
            'status',
            'Commission rules updated. Pending rider commissions were recalculated; Approved and Paid commissions retain their original locked rates.'
        );
    }

    /**
     * Bulk approve selected Pending rider commission rows (Pending -> Approved).
     */
    public function bulkApprove(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer', 'exists:rider_commissions,id'],
        ]);

        $rule = CommissionRule::current();
        $rows = RiderCommission::query()
            ->with('rider.user')
            ->whereIn('id', $validated['commission_ids'])
            ->get();

        // Bulk approve validation: flagged cases cannot be approved until resolved
        $flaggedCount = $rows->where('is_flagged', true)->count();
        if ($flaggedCount > 0) {
            $msg = "Cannot approve selected records because {$flaggedCount} " . ($flaggedCount === 1 ? 'record is' : 'records are') . " flagged with an issue by the hub. Please resolve the flagged issue first.";
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }
            return back()->withErrors(['status' => $msg]);
        }

        $approvedCount = 0;
        $now = now();
        $adminId = $request->user()?->id;
        $adminName = $request->user()?->name ?? 'Super Admin';

        foreach ($rows as $rc) {
            if ($rc->status !== 'pending') {
                continue;
            }

            $snapshot = ! empty($rc->rules_snapshot) ? $rc->rules_snapshot : $rule->toSnapshotArray();

            $rc->update([
                'status' => 'approved',
                'rules_snapshot' => $snapshot,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ]);

            AuditLog::query()->create([
                'user_id' => $adminId,
                'event' => 'commission.approved',
                'auditable_type' => RiderCommission::class,
                'auditable_id' => $rc->id,
                'metadata' => [
                    'rider_name' => $rc->rider?->user?->name ?? 'Rider #' . $rc->rider_profile_id,
                    'hub_branch' => $rc->hub_branch,
                    'period_key' => $rc->period_key,
                    'period_label' => $rc->period_label,
                    'net_centavos' => $rc->net_centavos,
                    'net_formatted' => $rc->formatted_net,
                    'approved_by_name' => $adminName,
                    'rules_snapshot' => $snapshot,
                ],
                'created_at' => $now,
            ]);

            $approvedCount++;
        }

        $message = $approvedCount > 0
            ? "Approved {$approvedCount} rider commission " . ($approvedCount === 1 ? 'record' : 'records') . '.'
            : 'No pending commission records were eligible for approval.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'approved_count' => $approvedCount,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    /**
     * Mark an Approved rider commission row as Paid (Approved -> Paid).
     */
    public function markPaid(Request $request, RiderCommission $riderCommission): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $isMaster = $user && method_exists($user, 'isMasterAdmin') && $user->isMasterAdmin();
        $branch = $user?->getBranchName();

        if (! $isMaster) {
            abort_unless($user && in_array($user->role, ['admin', 'logistics', 'operations_manager'], true), 403);
            if ($branch && strcasecmp($riderCommission->hub_branch, $branch) !== 0) {
                abort(403, 'You may only record payouts for riders assigned to your own hub.');
            }
        }

        if ($riderCommission->status === 'paid') {
            $msg = "Parcel payout was already recorded as Paid on {$riderCommission->paid_at?->format('M j, Y')}.";
            if ($request->expectsJson()) {
                return response()->json(['status' => 'success', 'message' => $msg]);
            }
            return back()->with('status', $msg);
        }

        if ($riderCommission->status !== 'approved') {
            $err = 'Only Approved (Ready to pay) commission records can be marked as paid.';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $err], 422);
            }
            return back()->withErrors(['status' => $err]);
        }

        $validated = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:160'],
        ]);

        $reference = trim((string) ($validated['payment_reference'] ?? ''));
        $now = now();
        $adminId = $user?->id;
        $adminName = $user?->name ?? 'Hub Admin';

        $riderCommission->update([
            'status' => 'paid',
            'paid_at' => $now,
            'paid_by' => $adminId,
            'payment_reference' => $reference !== '' ? $reference : null,
        ]);

        $riderName = $riderCommission->rider?->user?->name ?? ('Rider #' . $riderCommission->rider_profile_id);

        AuditLog::query()->create([
            'user_id' => $adminId,
            'event' => 'commission.paid',
            'auditable_type' => RiderCommission::class,
            'auditable_id' => $riderCommission->id,
            'metadata' => [
                'rider_name' => $riderName,
                'hub_branch' => $riderCommission->hub_branch,
                'period_key' => $riderCommission->period_key,
                'period_label' => $riderCommission->period_label,
                'net_centavos' => $riderCommission->net_centavos,
                'net_formatted' => $riderCommission->formatted_net,
                'payment_reference' => $reference !== '' ? $reference : null,
                'paid_by_name' => $adminName,
            ],
            'created_at' => $now,
        ]);

        $msg = "Marked {$riderName} ({$riderCommission->formatted_net}) as Paid" . ($reference !== '' ? " [Ref: {$reference}]" : '') . '.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    /**
     * Bulk mark approved rows as Paid (Branch Hub and HQ).
     */
    public function bulkMarkPaid(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $isMaster = $user && method_exists($user, 'isMasterAdmin') && $user->isMasterAdmin();
        $branch = $user?->getBranchName();

        if (! $isMaster) {
            abort_unless($user && in_array($user->role, ['admin', 'logistics', 'operations_manager'], true), 403);
        }

        $validated = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer', 'exists:rider_commissions,id'],
            'payment_reference' => ['nullable', 'string', 'max:160'],
        ]);

        $query = RiderCommission::query()->whereIn('id', $validated['commission_ids']);
        if (! $isMaster && $branch) {
            $query->forHub($branch);
        }

        $rows = $query->with('rider.user')->get();
        $now = now();
        $adminId = $user?->id;
        $adminName = $user?->name ?? ($branch ? "{$branch} Hub Admin" : 'Admin');
        $reference = trim((string) ($validated['payment_reference'] ?? ''));

        $paidCount = 0;
        foreach ($rows as $rc) {
            if ($rc->status !== 'approved') {
                continue;
            }

            $rc->update([
                'status' => 'paid',
                'paid_at' => $now,
                'paid_by' => $adminId,
                'payment_reference' => $reference !== '' ? $reference : $rc->payment_reference,
            ]);

            AuditLog::query()->create([
                'user_id' => $adminId,
                'event' => 'commission.paid',
                'auditable_type' => RiderCommission::class,
                'auditable_id' => $rc->id,
                'metadata' => [
                    'rider_name' => $rc->rider?->user?->name ?? ('Rider #' . $rc->rider_profile_id),
                    'hub_branch' => $rc->hub_branch,
                    'period_key' => $rc->period_key,
                    'period_label' => $rc->period_label,
                    'net_centavos' => $rc->net_centavos,
                    'net_formatted' => $rc->formatted_net,
                    'payment_reference' => $reference !== '' ? $reference : null,
                    'paid_by_name' => $adminName,
                ],
                'created_at' => $now,
            ]);

            $paidCount++;
        }

        $msg = "Recorded payout for {$paidCount} " . ($paidCount === 1 ? 'rider' : 'riders') . '.';
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'paid_count' => $paidCount, 'message' => $msg]);
        }
        return back()->with('status', $msg);
    }

    /**
     * Flag an issue to HQ on a commission record (Hub Admin).
     */
    public function flagIssue(Request $request, RiderCommission $riderCommission): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $isMaster = $user && method_exists($user, 'isMasterAdmin') && $user->isMasterAdmin();
        $branch = $user?->getBranchName();

        if (! $isMaster && $branch) {
            if (strcasecmp($riderCommission->hub_branch, $branch) !== 0) {
                abort(403, 'You may only flag records for your own hub.');
            }
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $now = now();
        $note = trim($validated['note']);

        $riderCommission->update([
            'is_flagged' => true,
            'flagged_note' => $note,
            'flagged_at' => $now,
            'flagged_by' => $user?->id,
        ]);

        AuditLog::query()->create([
            'user_id' => $user?->id,
            'event' => 'commission.flagged',
            'auditable_type' => RiderCommission::class,
            'auditable_id' => $riderCommission->id,
            'metadata' => [
                'rider_name' => $riderCommission->rider?->user?->name ?? ('Rider #' . $riderCommission->rider_profile_id),
                'hub_branch' => $riderCommission->hub_branch,
                'period_key' => $riderCommission->period_key,
                'note' => $note,
                'flagged_by_name' => $user?->name ?? 'Hub Admin',
            ],
            'created_at' => $now,
        ]);

        $msg = "Flagged issue to Headquarters for {$riderCommission->rider?->user?->name}.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }
        return back()->with('status', $msg);
    }

    /**
     * Resolve a flagged issue (HQ Super Admin).
     */
    public function resolveFlag(Request $request, RiderCommission $riderCommission): RedirectResponse|JsonResponse
    {
        $this->authorizeSuperAdmin($request);

        $riderCommission->update([
            'is_flagged' => false,
            'flagged_note' => null,
            'flagged_at' => null,
            'flagged_by' => null,
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'commission.flag_resolved',
            'auditable_type' => RiderCommission::class,
            'auditable_id' => $riderCommission->id,
            'metadata' => [
                'rider_name' => $riderCommission->rider?->user?->name,
                'resolved_by_name' => $request->user()?->name ?? 'Super Admin',
            ],
            'created_at' => now(),
        ]);

        $msg = "Resolved flag for {$riderCommission->rider?->user?->name}. Record is now eligible for approval.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }
        return back()->with('status', $msg);
    }

    /**
     * Branch Hub Commission page (for Hub Admin, e.g. Pila Hub).
     */
    public function hubIndex(Request $request, CommissionCalculatorService $calculator): View|\Illuminate\Http\Response
    {
        try {
            $user = $request->user();
            abort_unless($user && in_array($user->role, ['admin', 'logistics', 'operations_manager', 'super_admin'], true), 403);

            $hubName = $user->isMasterAdmin() ? ($request->query('hub') ?: 'Pila') : ($user->getBranchName() ?: 'Pila');
            $rule = CommissionRule::current();
            $periods = $calculator->availablePeriods($rule->period_type);

            $requestedPeriodKey = trim((string) $request->query('period', ''));
            $selectedPeriod = $calculator->resolvePeriod($requestedPeriodKey !== '' ? $requestedPeriodKey : null);

            if (! $periods->contains(fn (array $p) => $p['key'] === $selectedPeriod['key'])) {
                $periods->prepend($selectedPeriod);
            }

            // Auto-sync real parcel calculations for the period
            try {
                $calculator->syncPeriodCommissions($selectedPeriod);
            } catch (\Throwable $e) {
                // Keep going with existing cached or fallback records
            }

            $search = trim((string) $request->query('search', ''));
            $activeTab = strtolower(trim((string) $request->query('tab', 'all')));
            if (! in_array($activeTab, ['all', 'awaiting_hq', 'ready_to_pay', 'paid'], true)) {
                $activeTab = 'all';
            }

            // Scope records strictly to this hub
            try {
                $hubRows = RiderCommission::query()
                    ->with(['rider.user', 'approver', 'payer', 'flagger'])
                    ->where('period_key', $selectedPeriod['key'])
                    ->forHub($hubName)
                    ->get()
                    ->sortByDesc('net_centavos')
                    ->values();
            } catch (\Throwable $e) {
                $hubRows = collect();
            }

            // 4 Summary cards for this Hub:
            $awaitingHqRows = $hubRows->where('status', 'pending');
            $readyToPayRows = $hubRows->where('status', 'approved');
            $paidRows = $hubRows->where('status', 'paid');

            $hubNetCentavos = (int) $hubRows->sum('net_centavos');
            $hubDeliveredCount = (int) $hubRows->sum('delivered_count');
            $hubRidersCount = $hubRows->count();

            $awaitingHqCentavos = (int) $awaitingHqRows->sum('net_centavos');
            $awaitingHqCount = $awaitingHqRows->count();

            $readyToPayCentavos = (int) $readyToPayRows->sum('net_centavos');
            $readyToPayCount = $readyToPayRows->count();

            $paidOutCentavos = (int) $paidRows->sum('net_centavos');
            $paidOutCount = $paidRows->count();

            $stats = [
                'hub_net_centavos' => $hubNetCentavos,
                'hub_net_formatted' => CommissionRule::formatPeso($hubNetCentavos),
                'delivered_count' => $hubDeliveredCount,
                'riders_count' => $hubRidersCount,
                'awaiting_hq_centavos' => $awaitingHqCentavos,
                'awaiting_hq_formatted' => CommissionRule::formatPeso($awaitingHqCentavos),
                'awaiting_hq_count' => $awaitingHqCount,
                'ready_to_pay_centavos' => $readyToPayCentavos,
                'ready_to_pay_formatted' => CommissionRule::formatPeso($readyToPayCentavos),
                'ready_to_pay_count' => $readyToPayCount,
                'paid_centavos' => $paidOutCentavos,
                'paid_formatted' => CommissionRule::formatPeso($paidOutCentavos),
                'paid_count' => $paidOutCount,
            ];

            // Search by rider name
            $searchedRows = $this->applySearchFilter($hubRows, $search);

            // Status tabs with live counts
            $tabCounts = [
                'all' => $searchedRows->count(),
                'awaiting_hq' => $searchedRows->where('status', 'pending')->count(),
                'ready_to_pay' => $searchedRows->where('status', 'approved')->count(),
                'paid' => $searchedRows->where('status', 'paid')->count(),
            ];

            $statusMap = [
                'awaiting_hq' => 'pending',
                'ready_to_pay' => 'approved',
                'paid' => 'paid',
            ];

            $filteredRows = $activeTab === 'all'
                ? $searchedRows->values()
                : $searchedRows->where('status', $statusMap[$activeTab])->values();

            // Paginate rider table (12 per page)
            $perPage = 12;
            $currentPage = max(1, (int) $request->query('page', 1));
            $currentItems = $filteredRows->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $currentItems,
                $filteredRows->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            // Selected rider for the detail panel
            $selectedId = (int) $request->query('selected', 0);
            $selectedCommission = null;
            if ($selectedId > 0) {
                $selectedCommission = $hubRows->firstWhere('id', $selectedId);
            }
            if (! $selectedCommission && $currentItems->isNotEmpty()) {
                $selectedCommission = $currentItems->first();
            }

            return view('commission.hub', [
                'commissions' => $paginator,
                'hubName' => $hubName,
                'rule' => $rule,
                'periods' => $periods,
                'selectedPeriod' => $selectedPeriod,
                'selectedCommission' => $selectedCommission,
                'search' => $search,
                'activeTab' => $activeTab,
                'tabCounts' => $tabCounts,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            return response('Hub commission error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), 500);
        }
    }

    /**
     * Export the currently filtered rider commission table as CSV.
     */
    public function exportCsv(Request $request, CommissionCalculatorService $calculator): StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $user = $request->user();
            $isMaster = $user && method_exists($user, 'isMasterAdmin') && $user->isMasterAdmin();
            $branch = $user?->getBranchName();

            if (! $isMaster) {
                abort_unless($user && in_array($user->role, ['admin', 'logistics', 'operations_manager', 'super_admin'], true), 403);
            }

            $requestedPeriodKey = trim((string) $request->query('period', ''));
            $selectedPeriod = $calculator->resolvePeriod($requestedPeriodKey !== '' ? $requestedPeriodKey : null);
            try {
                $calculator->syncPeriodCommissions($selectedPeriod);
            } catch (\Throwable $e) {}

            $filterHub = $isMaster ? trim((string) $request->query('hub', '')) : ($branch ?: 'Pila');
            $search = trim((string) $request->query('search', ''));
            $activeTab = strtolower(trim((string) $request->query('tab', 'all')));

            try {
                $query = RiderCommission::query()
                    ->with(['rider.user', 'approver', 'payer', 'flagger'])
                    ->where('period_key', $selectedPeriod['key']);

                if (! empty($filterHub)) {
                    $query->forHub($filterHub);
                }

                $rows = $query->get()->sortByDesc('net_centavos')->values();
            } catch (\Throwable $e) {
                $rows = collect();
            }

            $rows = $this->applySearchFilter($rows, $search);

            if (in_array($activeTab, ['pending', 'approved', 'paid'], true)) {
                $rows = $rows->where('status', $activeTab)->values();
            }

            $filename = 'lumora-commissions-' . $selectedPeriod['key'] . '-' . now('Asia/Manila')->format('Ymd-His') . '.csv';

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                // UTF-8 BOM so Excel opens with correct encoding and symbols
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($out, [
                    'Rider Name',
                    'Rider Phone',
                    'Hub',
                    'Pay Period',
                    'Delivered Parcels',
                    'On-Time Parcels',
                    'On-Time Rate (%)',
                    'Rider-Fault Returns',
                    'Base Commission',
                    'On-Time Bonus',
                    'Deductions',
                    'Net Commission',
                    'Base Rate Applied (PHP)',
                    'Status',
                    'Approved By',
                    'Approved At',
                    'Paid By',
                    'Paid At',
                    'Payment Reference',
                ]);

                $formatDate = function ($dt) {
                    if (empty($dt)) return '—';
                    try {
                        if ($dt instanceof \Carbon\CarbonInterface) {
                            return $dt->copy()->setTimezone('Asia/Manila')->format('M j, Y g:i A');
                        }
                        return \Carbon\Carbon::parse($dt)->setTimezone('Asia/Manila')->format('M j, Y g:i A');
                    } catch (\Throwable $e) {
                        return (string) $dt;
                    }
                };

                foreach ($rows as $rc) {
                    $snap = is_array($rc->rules_snapshot) ? $rc->rules_snapshot : (is_string($rc->rules_snapshot) ? json_decode($rc->rules_snapshot, true) : []);
                    if (! is_array($snap)) {
                        $snap = [];
                    }
                    $baseRateApplied = isset($snap['base_rate_centavos'])
                        ? number_format(((int) $snap['base_rate_centavos']) / 100, 2)
                        : '18.00';

                    $riderName = $rc->rider?->user?->name ?? ('Rider #' . $rc->rider_profile_id);
                    $riderPhone = $rc->rider?->phone ?? '—';
                    $hubLabel = ($rc->hub_branch ?: 'Laguna') . ' Hub';
                    $approvedAt = $formatDate($rc->approved_at);
                    $paidAt = $formatDate($rc->paid_at);

                    fputcsv($out, [
                        $riderName,
                        $riderPhone,
                        $hubLabel,
                        $rc->period_label,
                        $rc->delivered_count,
                        $rc->on_time_count,
                        number_format($rc->on_time_rate_percent, 1) . '%',
                        $rc->return_fault_count,
                        $rc->formatted_base,
                        $rc->formatted_bonus,
                        $rc->formatted_deductions,
                        $rc->formatted_net,
                        '₱' . $baseRateApplied,
                        ucfirst((string) $rc->status),
                        $rc->approver?->name ?? '—',
                        $approvedAt,
                        $rc->payer?->name ?? '—',
                        $paidAt,
                        $rc->payment_reference ?: '—',
                    ]);
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            return response('CSV export error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), 500);
        }
    }

    /**
     * Filter RiderCommission collection by rider name, email, phone, or hub.
     */
    private function applySearchFilter(Collection $rows, string $search): Collection
    {
        if ($search === '') {
            return $rows;
        }

        $needle = strtolower($search);

        return $rows->filter(function (RiderCommission $rc) use ($needle) {
            $haystack = strtolower(implode(' ', [
                $rc->rider?->user?->name ?? '',
                $rc->rider?->user?->email ?? '',
                $rc->rider?->phone ?? '',
                $rc->hub_branch,
                $rc->hub_branch . ' hub',
                $rc->payment_reference ?? '',
            ]));

            return str_contains($haystack, $needle);
        })->values();
    }

    /**
     * Compute net commission per hub for the "By hub" horizontal bar chart panel.
     *
     * @return Collection<int, array{hub: string, label: string, net_centavos: int, net_formatted: string, delivered_count: int, riders_count: int, pct_width: int}>
     */
    private function computeByHubBreakdown(Collection $allPeriodRows): Collection
    {
        $grouped = $allPeriodRows->groupBy(fn (RiderCommission $rc) => $rc->hub_branch ?: 'Main');

        $hubStats = $grouped->map(function (Collection $group, string $hubName) {
            $netCentavos = (int) $group->sum('net_centavos');
            return [
                'hub' => $hubName,
                'label' => $hubName . ' Hub',
                'net_centavos' => $netCentavos,
                'net_formatted' => CommissionRule::formatPeso($netCentavos),
                'delivered_count' => (int) $group->sum('delivered_count'),
                'riders_count' => $group->count(),
            ];
        })->sortByDesc('net_centavos')->values();

        $maxCentavos = max(1, (int) $hubStats->max('net_centavos'));

        return $hubStats->map(function (array $item) use ($maxCentavos) {
            $item['pct_width'] = $item['net_centavos'] > 0
                ? max(6, (int) round(($item['net_centavos'] / $maxCentavos) * 100))
                : 0;
            return $item;
        });
    }

    /**
     * Collect all available Laguna hub names for the Hub filter dropdown.
     */
    private function availableHubs(Collection $allPeriodRows): Collection
    {
        try {
            $hubTableBranches = Hub::query()->where('is_active', true)->pluck('branch');
        } catch (\Throwable $e) {
            $hubTableBranches = collect();
        }

        try {
            $locationCities = FulfillmentLocation::query()->where('is_active', true)->pluck('city');
        } catch (\Throwable $e) {
            $locationCities = collect();
        }

        $areaBranches = DestinationArea::query()->whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch');
        $parcelBranches = Parcel::query()->whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch');
        $userBranches = User::query()->whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch');

        return $hubTableBranches
            ->merge($locationCities)
            ->merge($areaBranches)
            ->merge($parcelBranches)
            ->merge($userBranches)
            ->merge($allPeriodRows->pluck('hub_branch'))
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique(fn (string $b) => strtolower($b))
            ->sort()
            ->values();
    }
}
