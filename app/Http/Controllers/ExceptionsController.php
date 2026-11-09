<?php

namespace App\Http\Controllers;

use App\Models\FulfillmentLocation;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\Shipment;
use App\Services\ParcelShipmentSyncService;
use App\Services\ShipmentStatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExceptionsController extends Controller
{
    public const REASONS = [
        'Damaged / Defective Item',
        'Wrong Item / Incomplete Order',
        'Customer Refused / Cancelled Order',
        'Item Does Not Match Description',
        'Customer Unreachable / Not Home',
        'Wrong / Incomplete Delivery Address',
        'Other Return / Refund Reason',
    ];

    /**
     * Compute the number of Return / Refund cases needing action (pending or under_review)
     * for the sidebar badge.
     */
    public static function needsActionCount(?string $branch = null): int
    {
        try {
            $parcels = self::baseReturnParcelQuery($branch)->get(['id', 'status', 'metadata']);
            return $parcels->filter(function (Parcel $p) {
                $status = self::resolveReturnStatus($p);
                return in_array($status, ['pending', 'under_review'], true);
            })->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Base Eloquent query for Return / Refund parcels.
     */
    public static function baseReturnParcelQuery(?string $branch = null)
    {
        return Parcel::query()
            ->forBranch($branch)
            ->where(function ($q) {
                $q->whereIn('status', ['exception', 'failed', 'returned'])
                    ->orWhereNotNull('metadata->failure_reason')
                    ->orWhereNotNull('metadata->return_reason')
                    ->orWhereNotNull('metadata->return_status')
                    ->orWhere('metadata->is_return_refund', true);
            });
    }

    /**
     * Normalize a parcel's return case status into one of:
     * 'pending', 'under_review', 'approved', 'rejected'
     */
    public static function resolveReturnStatus(Parcel $parcel): string
    {
        $meta = is_array($parcel->metadata) ? $parcel->metadata : [];
        $explicit = strtolower(trim((string) ($meta['return_status'] ?? '')));
        if (in_array($explicit, ['pending', 'under_review', 'approved', 'rejected'], true)) {
            return $explicit;
        }

        $resolution = strtolower(trim((string) ($meta['return_resolution'] ?? '')));
        if (str_contains($resolution, 'approved') || str_contains($resolution, 'refunded')) {
            return 'approved';
        }
        if (str_contains($resolution, 'rejected') || str_contains($resolution, 're-dispatched') || str_contains($resolution, 'resumed')) {
            return 'rejected';
        }
        if (str_contains($resolution, 'received at hub') || str_contains($resolution, 'under review') || str_contains($resolution, 'return to hub')) {
            return 'under_review';
        }

        return 'pending';
    }

    /**
     * Build enriched view model for a Return / Refund parcel case.
     */
    public static function enrichParcelCase(Parcel $parcel): array
    {
        $now = Carbon::now('Asia/Manila');
        $meta = is_array($parcel->metadata) ? $parcel->metadata : [];
        $returnStatus = self::resolveReturnStatus($parcel);

        $statusLabels = [
            'pending' => 'Pending',
            'under_review' => 'Under review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        $reportedRaw = $meta['return_reported_at'] ?? $meta['last_updated_at'] ?? null;
        try {
            $reportedAt = $reportedRaw ? Carbon::parse($reportedRaw)->setTimezone('Asia/Manila') : ($parcel->updated_at ? $parcel->updated_at->copy()->setTimezone('Asia/Manila') : $now->copy());
        } catch (\Throwable $e) {
            $reportedAt = $parcel->updated_at ? $parcel->updated_at->copy()->setTimezone('Asia/Manila') : $now->copy();
        }

        $deadlineRaw = $meta['sla_deadline_at'] ?? null;
        try {
            $slaDeadline = $deadlineRaw ? Carbon::parse($deadlineRaw)->setTimezone('Asia/Manila') : $reportedAt->copy()->addHours(24);
        } catch (\Throwable $e) {
            $slaDeadline = $reportedAt->copy()->addHours(24);
        }

        $resolvedRaw = $meta['return_resolved_at'] ?? null;
        $resolvedAt = null;
        if ($resolvedRaw) {
            try {
                $resolvedAt = Carbon::parse($resolvedRaw)->setTimezone('Asia/Manila');
            } catch (\Throwable $e) {
                $resolvedAt = null;
            }
        }

        $needsAction = in_array($returnStatus, ['pending', 'under_review'], true);
        $diffMinutes = (int) round($now->diffInMinutes($slaDeadline, false));

        if (! $needsAction) {
            $slaState = 'resolved';
            if ($resolvedAt) {
                $resMins = max(1, (int) round($reportedAt->diffInMinutes($resolvedAt)));
                $slaText = 'Resolved in ' . self::formatDurationMinutes($resMins);
            } else {
                $slaText = 'Resolved';
            }
            $resolutionMinutes = $resolvedAt ? max(1, (int) round($reportedAt->diffInMinutes($resolvedAt))) : null;
        } else {
            $resolutionMinutes = null;
            if ($diffMinutes < 0) {
                $slaState = 'overdue';
                $slaText = 'Overdue by ' . self::formatDurationMinutes(abs($diffMinutes));
            } elseif ($diffMinutes <= 240) {
                $slaState = 'warning';
                $slaText = self::formatDurationMinutes($diffMinutes) . ' left';
            } else {
                $slaState = 'on_track';
                $slaText = self::formatDurationMinutes($diffMinutes) . ' left';
            }
        }

        $hasPhoto = ! empty($meta['return_photo_path']) || ! empty($meta['return_photo_data']) || ! empty($meta['proof_url']);
        $hasVideo = ! empty($meta['return_video_path']) || ! empty($meta['return_video_data']) || ! empty($meta['proof_video_url']);
        $photoUrl = $hasPhoto ? route('returns.media', ['parcel' => $parcel->id, 'type' => 'photo']) : null;
        $videoUrl = $hasVideo ? route('returns.media', ['parcel' => $parcel->id, 'type' => 'video']) : null;

        $reason = $meta['return_reason'] ?? $meta['failure_reason'] ?? 'Return / Refund Requested';
        $notes = $meta['return_notes'] ?? $meta['failure_notes'] ?? '';
        $trackId = $parcel->shipment?->lumora_tracking_id ?: $parcel->parcel_number;
        $hubName = ($parcel->branch ?: ($parcel->city ?: 'Main')) . ' Hub';
        $riderName = $parcel->rider?->user?->name ?? 'Unassigned';
        $riderPhone = $parcel->rider?->phone ?? ($parcel->rider?->user?->phone ?? '');

        // Build chronological audit trail from real scans + return_audit_trail metadata
        $auditTrail = [];
        if ($parcel->relationLoaded('scans')) {
            foreach ($parcel->scans as $scan) {
                $scanTime = $scan->scanned_at ?: $scan->created_at;
                $auditTrail[] = [
                    'actor' => $scan->user?->name ?? ($riderName !== 'Unassigned' ? $riderName : 'Hub System'),
                    'role' => $scan->user?->role ? ucwords(str_replace('_', ' ', $scan->user->role)) : 'Operations',
                    'action' => ucwords(str_replace('_', ' ', (string) $scan->scan_type)),
                    'note' => (string) ($scan->note ?? ''),
                    'timestamp_iso' => $scanTime?->toIso8601String() ?? '',
                    'timestamp_formatted' => $scanTime ? $scanTime->copy()->setTimezone('Asia/Manila')->format('M j, Y · g:i A') : '—',
                    'sort_ts' => $scanTime ? $scanTime->getTimestamp() : 0,
                ];
            }
        }

        if (is_array($meta['return_audit_trail'] ?? null)) {
            foreach ($meta['return_audit_trail'] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                try {
                    $entryTime = ! empty($entry['timestamp']) ? Carbon::parse($entry['timestamp'])->setTimezone('Asia/Manila') : $reportedAt;
                } catch (\Throwable $e) {
                    $entryTime = $reportedAt;
                }
                $auditTrail[] = [
                    'actor' => (string) ($entry['actor'] ?? 'Logistics Admin'),
                    'role' => (string) ($entry['role'] ?? 'Admin'),
                    'action' => (string) ($entry['action'] ?? 'Updated Case'),
                    'note' => (string) ($entry['note'] ?? ''),
                    'timestamp_iso' => $entryTime->toIso8601String(),
                    'timestamp_formatted' => $entryTime->format('M j, Y · g:i A'),
                    'sort_ts' => $entryTime->getTimestamp(),
                ];
            }
        }

        if (empty($auditTrail)) {
            $auditTrail[] = [
                'actor' => $riderName !== 'Unassigned' ? $riderName : 'Courier',
                'role' => 'Rider',
                'action' => 'Reported Return / Refund',
                'note' => trim($reason . ($notes !== '' ? ' — ' . $notes : '')),
                'timestamp_iso' => $reportedAt->toIso8601String(),
                'timestamp_formatted' => $reportedAt->format('M j, Y · g:i A'),
                'sort_ts' => $reportedAt->getTimestamp(),
            ];
        }

        usort($auditTrail, fn (array $a, array $b) => $b['sort_ts'] <=> $a['sort_ts']);

        return [
            'id' => $parcel->id,
            'parcel' => $parcel,
            'track_id' => $trackId,
            'parcel_number' => $parcel->parcel_number,
            'barcode' => $parcel->barcode ?: '—',
            'order_number' => $parcel->order?->order_number ?: ($parcel->order?->external_id ?: ($meta['waybill_order_number'] ?? null)),
            'order_url' => $parcel->order ? route('orders.show', $parcel->order) : null,
            'shipment_url' => $parcel->shipment ? route('shipments.show', $parcel->shipment) : null,
            'customer_name' => $parcel->recipient_name ?: 'Customer',
            'customer_phone' => $parcel->recipient_phone ?: 'No phone on file',
            'customer_address' => $parcel->address_line ?: ($parcel->city . ', ' . $parcel->province),
            'destination_short' => trim(($parcel->area?->name ? $parcel->area->name . ', ' : '') . $parcel->city . ($parcel->province ? ', ' . $parcel->province : '')),
            'rider_name' => $riderName,
            'rider_phone' => $riderPhone,
            'rider_message_url' => ($parcel->rider && $parcel->rider->user_id)
                ? route('messages.index', ['rider_user_id' => $parcel->rider->user_id, 'parcel_id' => $parcel->id])
                : null,
            'hub' => $parcel->branch ?: ($parcel->city ?: 'Main'),
            'hub_label' => $hubName,
            'reason' => $reason,
            'notes' => $notes,
            'has_photo' => $hasPhoto,
            'has_video' => $hasVideo,
            'has_media' => $hasPhoto || $hasVideo,
            'photo_url' => $photoUrl,
            'video_url' => $videoUrl,
            'return_status' => $returnStatus,
            'return_status_label' => $statusLabels[$returnStatus] ?? 'Pending',
            'needs_action' => $needsAction,
            'reported_at' => $reportedAt,
            'reported_formatted' => $reportedAt->format('M j, Y · g:i A'),
            'reported_date' => $reportedAt->format('M j, Y'),
            'reported_time' => $reportedAt->format('g:i A'),
            'sla_deadline_at' => $slaDeadline,
            'sla_deadline_formatted' => $slaDeadline->format('M j, Y · g:i A'),
            'sla_state' => $slaState,
            'sla_text' => $slaText,
            'sla_minutes_remaining' => $diffMinutes,
            'resolution_minutes' => $resolutionMinutes,
            'resolution_label' => $meta['return_resolution'] ?? null,
            'resolution_note' => $meta['return_resolution_note'] ?? '',
            'resolved_by' => $meta['return_resolved_by'] ?? null,
            'resolved_at_formatted' => $resolvedAt ? $resolvedAt->format('M j, Y · g:i A') : null,
            'cod_amount' => (float) ($meta['cod_amount'] ?? ($parcel->order?->total_amount ?? 0)),
            'is_cod' => (bool) ($meta['is_cod'] ?? false),
            'assigned_rider_id' => $parcel->assigned_rider_id,
            'rider_fault' => \App\Services\CommissionCalculatorService::isRiderFaultReturn($parcel),
            'audit_trail' => $auditTrail,
        ];
    }

    public static function formatDurationMinutes(int $totalMinutes): string
    {
        if ($totalMinutes < 60) {
            return max(1, $totalMinutes) . 'm';
        }
        $hours = intdiv($totalMinutes, 60);
        $mins = $totalMinutes % 60;
        if ($hours >= 48) {
            $days = intdiv($hours, 24);
            $remHours = $hours % 24;
            return $days . 'd' . ($remHours > 0 ? " {$remHours}h" : '');
        }
        return $hours . 'h' . ($mins > 0 ? " {$mins}m" : '');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $assignedBranch = $user?->getBranchName();
        $isMasterAdmin = $user && method_exists($user, 'isMasterAdmin') && $user->isMasterAdmin();

        $search = trim((string) $request->query('search', ''));
        $filterReason = trim((string) $request->query('reason', ''));
        $filterHub = trim((string) $request->query('hub', ''));
        $dateRange = trim((string) $request->query('date_range', 'all'));
        $activeTab = strtolower(trim((string) $request->query('tab', 'all')));
        if (! in_array($activeTab, ['all', 'pending', 'under_review', 'approved', 'rejected'], true)) {
            $activeTab = 'all';
        }

        $effectiveBranch = $assignedBranch ?: ($filterHub !== '' ? $filterHub : null);

        // Fetch all return/refund parcels for this workspace scope to compute real summary metrics
        $allScopedParcels = self::baseReturnParcelQuery($assignedBranch)
            ->with(['rider.user', 'area', 'shipment', 'order', 'scans.user'])
            ->latest('updated_at')
            ->get();

        $allCases = $allScopedParcels->map(fn (Parcel $p) => self::enrichParcelCase($p));

        // 1. Compute the 4 Summary Cards from real data
        $openCases = $allCases->where('needs_action', true);
        $needsActionCount = $openCases->count();
        $pastSlaCount = $openCases->where('sla_state', 'overdue')->count();
        $nearSlaCount = $openCases->where('sla_state', 'warning')->count();

        $withMediaOpenCount = $openCases->where('has_media', true)->count();
        $withMediaTotalCount = $allCases->where('has_media', true)->count();
        $mediaPctOfOpen = $needsActionCount > 0
            ? (int) round(($withMediaOpenCount / $needsActionCount) * 100)
            : ($allCases->isNotEmpty() ? (int) round(($withMediaTotalCount / $allCases->count()) * 100) : 0);

        $damagedOrWrongCases = $allCases->filter(function (array $c) {
            $r = strtolower($c['reason']);
            return str_contains($r, 'damaged')
                || str_contains($r, 'defective')
                || str_contains($r, 'wrong item')
                || str_contains($r, 'incomplete')
                || str_contains($r, 'match');
        });
        $damagedCount = $allCases->filter(fn (array $c) => str_contains(strtolower($c['reason']), 'damaged') || str_contains(strtolower($c['reason']), 'defective'))->count();
        $wrongItemCount = max(0, $damagedOrWrongCases->count() - $damagedCount);

        $resolvedDurations = $allCases
            ->whereNotNull('resolution_minutes')
            ->pluck('resolution_minutes')
            ->filter(fn ($m) => is_numeric($m) && $m > 0);

        $avgResolutionFormatted = $resolvedDurations->isNotEmpty()
            ? self::formatDurationMinutes((int) round($resolvedDurations->avg()))
            : '—';

        // 2. Apply non-tab filters (search, reason, hub, date_range)
        $filteredBeforeTab = $this->applyFilters($allCases, $search, $filterReason, $effectiveBranch, $dateRange);

        // Optional: restrict to specific parcel IDs (used by Commission → Deductions links)
        $filterIds = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values();
        if ($filterIds->isNotEmpty()) {
            $filteredBeforeTab = $filteredBeforeTab->filter(fn (array $c) => $filterIds->contains($c['id']))->values();
        }

        // 3. Compute live counts for each Status Tab
        $tabCounts = [
            'all' => $filteredBeforeTab->count(),
            'pending' => $filteredBeforeTab->where('return_status', 'pending')->count(),
            'under_review' => $filteredBeforeTab->where('return_status', 'under_review')->count(),
            'approved' => $filteredBeforeTab->where('return_status', 'approved')->count(),
            'rejected' => $filteredBeforeTab->where('return_status', 'rejected')->count(),
        ];

        // 4. Filter by active status tab
        $filteredCases = $activeTab === 'all'
            ? $filteredBeforeTab->values()
            : $filteredBeforeTab->where('return_status', $activeTab)->values();

        // 5. Paginate filtered cases (12 per page)
        $perPage = 12;
        $currentPage = max(1, (int) $request->query('page', 1));
        $currentItems = $filteredCases->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $currentItems,
            $filteredCases->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // 6. Determine selected case for the right-side detail panel
        $selectedId = (int) $request->query('selected', 0);
        $selectedCase = null;
        if ($selectedId > 0) {
            $selectedCase = $filteredCases->firstWhere('id', $selectedId)
                ?? $allCases->firstWhere('id', $selectedId);
        }
        if (! $selectedCase && $currentItems->isNotEmpty()) {
            $selectedCase = $currentItems->first();
        }

        // Hub list for the Hub dropdown
        try {
            $locationHubs = FulfillmentLocation::query()
                ->where('is_active', true)
                ->orderBy('city')
                ->pluck('city');
        } catch (\Throwable $e) {
            $locationHubs = collect();
        }

        $parcelBranches = Parcel::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->pluck('branch');

        $hubs = $locationHubs
            ->merge($parcelBranches)
            ->merge($allCases->pluck('hub'))
            ->map(fn ($h) => trim((string) $h))
            ->filter()
            ->unique(fn (string $h) => strtolower($h))
            ->sort()
            ->values();

        return view('exceptions.index', [
            'cases' => $paginator,
            'allFilteredCases' => $filteredCases,
            'selectedCase' => $selectedCase,
            'tabCounts' => $tabCounts,
            'activeTab' => $activeTab,
            'search' => $search,
            'filterReason' => $filterReason,
            'filterHub' => $filterHub,
            'dateRange' => $dateRange,
            'filterIds' => $filterIds,
            'branch' => $assignedBranch,
            'isMasterAdmin' => $isMasterAdmin,
            'hubs' => $hubs,
            'reasons' => self::REASONS,
            'stats' => [
                'needs_action' => $needsActionCount,
                'past_sla' => $pastSlaCount,
                'near_sla' => $nearSlaCount,
                'with_media' => $withMediaTotalCount,
                'with_media_open' => $withMediaOpenCount,
                'media_pct' => $mediaPctOfOpen,
                'damaged_or_wrong' => $damagedOrWrongCases->count(),
                'damaged_count' => $damagedCount,
                'wrong_item_count' => $wrongItemCount,
                'avg_resolution' => $avgResolutionFormatted,
                'resolved_count' => $resolvedDurations->count(),
            ],
        ]);
    }

    /**
     * Filter enriched cases collection by search, reason, hub, and date_range.
     */
    private function applyFilters(
        Collection $cases,
        string $search,
        string $filterReason,
        ?string $effectiveBranch,
        string $dateRange
    ): Collection {
        $now = Carbon::now('Asia/Manila');

        return $cases->filter(function (array $c) use ($search, $filterReason, $effectiveBranch, $dateRange, $now) {
            if ($effectiveBranch !== null && $effectiveBranch !== '') {
                if (strcasecmp($c['hub'], $effectiveBranch) !== 0 && ! str_contains(strtolower($c['destination_short']), strtolower($effectiveBranch))) {
                    return false;
                }
            }

            if ($filterReason !== '') {
                if (! str_contains(strtolower($c['reason']), strtolower($filterReason))) {
                    return false;
                }
            }

            if ($dateRange !== '' && $dateRange !== 'all') {
                /** @var Carbon $rep */
                $rep = $c['reported_at'];
                if ($dateRange === 'today' && ! $rep->isSameDay($now)) {
                    return false;
                }
                if ($dateRange === '7d' && $rep->lt($now->copy()->subDays(7)->startOfDay())) {
                    return false;
                }
                if ($dateRange === '30d' && $rep->lt($now->copy()->subDays(30)->startOfDay())) {
                    return false;
                }
                if ($dateRange === 'overdue' && $c['sla_state'] !== 'overdue') {
                    return false;
                }
            }

            if ($search !== '') {
                $needle = strtolower($search);
                $haystack = strtolower(implode(' ', [
                    $c['track_id'],
                    $c['parcel_number'],
                    $c['barcode'],
                    $c['order_number'] ?? '',
                    $c['customer_name'],
                    $c['customer_phone'],
                    $c['destination_short'],
                    $c['rider_name'],
                    $c['hub_label'],
                    $c['reason'],
                    $c['notes'],
                ]));
                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Resolve a single Return / Refund parcel case from the detail panel.
     */
    public function resolveParcel(Request $request, Parcel $parcel, ParcelShipmentSyncService $syncService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'action' => [
                'required',
                'in:approve_refund,redeliver,return_to_hub,reject,approve_return_refund,receive_returned_parcel,out_for_delivery,in_transit,cancelled',
            ],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $note = trim((string) ($validated['resolution_note'] ?? ''));

        if ($action === 'reject' && $note === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A resolution note is required when rejecting a Return / Refund.',
                ], 422);
            }
            throw ValidationException::withMessages([
                'resolution_note' => 'Please enter a resolution note explaining why this Return / Refund is rejected.',
            ]);
        }

        $operatorName = $request->user()?->name ?? 'Logistics Admin';
        $operatorRole = $request->user()?->role ? ucwords(str_replace('_', ' ', $request->user()->role)) : 'Admin';

        $this->applyCaseResolution($parcel, $action, $note, $operatorName, $operatorRole, $syncService);

        // Optional rider-fault flag (drives commission deductions for the assigned rider)
        if ($request->has('rider_fault')) {
            $fresh = $parcel->fresh();
            $freshMeta = is_array($fresh->metadata) ? $fresh->metadata : [];
            $freshMeta['rider_fault'] = $request->boolean('rider_fault');
            $fresh->update(['metadata' => $freshMeta]);
        }

        $enriched = self::enrichParcelCase($parcel->fresh(['rider.user', 'area', 'shipment', 'order', 'scans.user']));

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Parcel {$parcel->parcel_number} updated: {$enriched['resolution_label']}.",
                'case' => $enriched,
            ]);
        }

        return back()->with('status', "Parcel {$parcel->parcel_number} updated: {$enriched['resolution_label']}.");
    }

    /**
     * Bulk resolve multiple selected Return / Refund parcels.
     */
    public function bulkResolve(Request $request, ParcelShipmentSyncService $syncService): RedirectResponse
    {
        $validated = $request->validate([
            'parcel_ids' => ['required', 'array', 'min:1'],
            'parcel_ids.*' => ['integer', 'exists:parcels,id'],
            'action' => ['required', 'in:approve_refund,redeliver,return_to_hub,reject'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $note = trim((string) ($validated['resolution_note'] ?? ''));

        if ($action === 'reject' && $note === '') {
            throw ValidationException::withMessages([
                'resolution_note' => 'A resolution note is required when bulk rejecting cases.',
            ]);
        }

        $operatorName = $request->user()?->name ?? 'Logistics Admin';
        $operatorRole = $request->user()?->role ? ucwords(str_replace('_', ' ', $request->user()->role)) : 'Admin';
        $branch = $request->user()?->getBranchName();

        $parcels = Parcel::query()
            ->forBranch($branch)
            ->whereIn('id', $validated['parcel_ids'])
            ->get();

        foreach ($parcels as $parcel) {
            $this->applyCaseResolution($parcel, $action, $note, $operatorName, $operatorRole, $syncService);
        }

        return back()->with('status', 'Bulk updated ' . $parcels->count() . ' return / refund ' . ($parcels->count() === 1 ? 'case' : 'cases') . '.');
    }

    /**
     * Export the currently filtered Return / Refund cases as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $assignedBranch = $user?->getBranchName();

        $search = trim((string) $request->query('search', ''));
        $filterReason = trim((string) $request->query('reason', ''));
        $filterHub = trim((string) $request->query('hub', ''));
        $dateRange = trim((string) $request->query('date_range', 'all'));
        $activeTab = strtolower(trim((string) $request->query('tab', 'all')));

        $effectiveBranch = $assignedBranch ?: ($filterHub !== '' ? $filterHub : null);

        $allScopedParcels = self::baseReturnParcelQuery($assignedBranch)
            ->with(['rider.user', 'area', 'shipment', 'order'])
            ->latest('updated_at')
            ->get();

        $cases = $allScopedParcels->map(fn (Parcel $p) => self::enrichParcelCase($p));
        $filtered = $this->applyFilters($cases, $search, $filterReason, $effectiveBranch, $dateRange);
        if (in_array($activeTab, ['pending', 'under_review', 'approved', 'rejected'], true)) {
            $filtered = $filtered->where('return_status', $activeTab);
        }

        $filename = 'lumora-return-refunds-' . now('Asia/Manila')->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($filtered) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Track ID',
                'Barcode',
                'Reported At',
                'Customer Name',
                'Customer Phone',
                'Destination',
                'Rider',
                'Hub',
                'Reason',
                'Rider Note',
                'Has Picture',
                'Has Video',
                'Case Status',
                'SLA Status',
                'Resolution',
                'Resolution Note',
                'Resolved By',
                'Resolved At',
            ]);

            foreach ($filtered as $c) {
                fputcsv($out, [
                    $c['track_id'],
                    $c['barcode'],
                    $c['reported_formatted'],
                    $c['customer_name'],
                    $c['customer_phone'],
                    $c['destination_short'],
                    $c['rider_name'],
                    $c['hub_label'],
                    $c['reason'],
                    $c['notes'],
                    $c['has_photo'] ? 'Yes' : 'No',
                    $c['has_video'] ? 'Yes' : 'No',
                    $c['return_status_label'],
                    $c['sla_text'],
                    $c['resolution_label'] ?? '—',
                    $c['resolution_note'] ?? '',
                    $c['resolved_by'] ?? '—',
                    $c['resolved_at_formatted'] ?? '—',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Apply a resolution action to a Parcel, append to its audit trail, and log a ParcelScan.
     */
    private function applyCaseResolution(
        Parcel $parcel,
        string $action,
        string $note,
        string $operatorName,
        string $operatorRole,
        ParcelShipmentSyncService $syncService
    ): void {
        $metadata = is_array($parcel->metadata) ? $parcel->metadata : [];
        $now = Carbon::now('Asia/Manila');

        if (empty($metadata['return_reported_at'])) {
            $metadata['return_reported_at'] = ($parcel->updated_at ?: $now)->toIso8601String();
        }
        if (empty($metadata['sla_deadline_at'])) {
            $metadata['sla_deadline_at'] = Carbon::parse($metadata['return_reported_at'])->addHours(24)->toIso8601String();
        }

        [$returnStatus, $dbStatus, $resolutionLabel] = match ($action) {
            'approve_refund', 'approve_return_refund' => ['approved', 'exception', 'Approve refund'],
            'redeliver', 'out_for_delivery' => ['rejected', 'out_for_delivery', 'Re-deliver to customer'],
            'return_to_hub', 'receive_returned_parcel' => ['under_review', 'exception', 'Return to hub (Under review)'],
            'reject' => ['rejected', 'exception', 'Rejected with reason'],
            'in_transit' => ['rejected', 'in_transit', 'Resumed transit'],
            'cancelled' => ['approved', 'cancelled', 'Cancelled / Refunded'],
            default => ['under_review', 'exception', ucwords(str_replace('_', ' ', $action))],
        };

        $metadata['return_status'] = $returnStatus;
        $metadata['return_resolution'] = $resolutionLabel;
        $metadata['return_resolved_by'] = $operatorName;
        if (in_array($returnStatus, ['approved', 'rejected'], true)) {
            $metadata['return_resolved_at'] = $now->toIso8601String();
        }
        if ($note !== '') {
            $metadata['return_resolution_note'] = $note;
        }
        $metadata['is_return_refund'] = ! in_array($dbStatus, ['out_for_delivery', 'in_transit'], true);

        $audit = is_array($metadata['return_audit_trail'] ?? null) ? $metadata['return_audit_trail'] : [];
        $audit[] = [
            'actor' => $operatorName,
            'role' => $operatorRole,
            'action' => $resolutionLabel,
            'status' => $returnStatus,
            'note' => $note !== '' ? $note : "Marked as {$resolutionLabel}",
            'timestamp' => $now->toIso8601String(),
        ];
        $metadata['return_audit_trail'] = $audit;

        $parcel->update([
            'status' => $dbStatus,
            'metadata' => $metadata,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => auth()->id(),
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => "Return/Refund [{$resolutionLabel}] by {$operatorName}" . ($note !== '' ? ": {$note}" : ''),
            'scanned_at' => now(),
        ]);

        $syncService->sync($parcel->fresh());
    }

    public function resolve(Request $request, Shipment $shipment, ShipmentStatusService $service): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:in_transit,pending,cancelled']]);
        $service->transition($shipment, [
            'status' => $validated['status'],
            'label' => 'Return / Refund updated',
            'description' => 'Return / Refund status updated to ' . str_replace('_', ' ', $validated['status']) . ' by operations.',
        ], 'exceptions');

        return back()->with('status', 'Return / Refund updated for ' . ($shipment->tracking_number ?: $shipment->external_id) . '.');
    }

    /**
     * Serve Return / Refund Picture (photo) or Video for a parcel.
     */
    public function media(Request $request, Parcel $parcel, string $type): Response
    {
        if (! in_array($type, ['photo', 'video'], true)) {
            abort(404);
        }

        $metadata = is_array($parcel->metadata) ? $parcel->metadata : [];
        $pathKey = $type === 'photo' ? 'return_photo_path' : 'return_video_path';
        $dataKey = $type === 'photo' ? 'return_photo_data' : 'return_video_data';
        $urlKey = $type === 'photo' ? 'proof_url' : 'proof_video_url';

        $storedPath = $metadata[$pathKey] ?? null;
        if (is_string($storedPath) && $storedPath !== '' && Storage::disk('public')->exists($storedPath)) {
            $fullPath = Storage::disk('public')->path($storedPath);
            $mime = Storage::disk('public')->mimeType($storedPath) ?: ($type === 'photo' ? 'image/jpeg' : 'video/mp4');
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $dataUri = $metadata[$dataKey] ?? null;
        if (! $dataUri && is_string($metadata[$urlKey] ?? null) && str_starts_with($metadata[$urlKey], 'data:')) {
            $dataUri = $metadata[$urlKey];
        }

        if (is_string($dataUri) && $dataUri !== '') {
            $mime = $type === 'photo' ? 'image/jpeg' : 'video/mp4';
            $rawB64 = $dataUri;
            if (preg_match('/^data:([a-zA-Z0-9\/+.-]+);base64,(.*)$/s', $dataUri, $matches)) {
                $mime = $matches[1];
                $rawB64 = $matches[2];
            }
            $binary = base64_decode($rawB64, true);
            if ($binary !== false && strlen($binary) > 0) {
                return response($binary, 200, [
                    'Content-Type' => $mime,
                    'Content-Length' => (string) strlen($binary),
                    'Cache-Control' => 'public, max-age=86400',
                    'Accept-Ranges' => 'bytes',
                ]);
            }
        }

        $extUrl = $metadata[$urlKey] ?? null;
        if (is_string($extUrl) && (str_starts_with($extUrl, 'http://') || str_starts_with($extUrl, 'https://'))) {
            return redirect()->away($extUrl);
        }

        abort(404, 'Return/Refund media not found.');
    }
}
