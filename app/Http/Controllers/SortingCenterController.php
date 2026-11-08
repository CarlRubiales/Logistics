<?php

namespace App\Http\Controllers;

use App\Models\CommissionTransaction;
use App\Models\DestinationArea;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Services\ParcelIntakeService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use App\Models\RiderProfile;
use App\Models\RiderAreaAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SortingCenterController extends Controller
{
    public function index(Request $request)
    {
        \App\Services\LagunaHubProvisioner::ensureProvisioned();

        if ($request->user()?->isMasterAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $tz = 'Asia/Manila';
        $todayPht = Carbon::now($tz)->toDateString();
        $selectedDateStr = $request->input('date', $todayPht);

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDateStr, $tz);
        } catch (\Throwable $e) {
            $selectedDate = Carbon::now($tz);
            $selectedDateStr = $todayPht;
        }

        $isToday = $selectedDate->toDateString() === $todayPht;
        $formattedDate = $selectedDate->format('F d, Y');

        $branch = $request->user()?->getBranchName();
        $query = Parcel::with(['area', 'rider.user', 'shipment'])
            ->forBranch($branch)
            ->forDate($selectedDate)
            ->latest();

        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) { $q->where('parcel_number', 'like', "%{$term}%")->orWhere('recipient_name', 'like', "%{$term}%")->orWhere('city', 'like', "%{$term}%"); });
        }

        $parcels = $query->paginate(15)->withQueryString();
        $areas = DestinationArea::where('is_active', true)
            ->forBranch($branch)
            ->orderByRaw("CASE WHEN code LIKE 'SCN-%' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $areaIds = $areas->pluck('id');

        $ridersQuery = RiderProfile::with(['user', 'areas', 'hub'])->where('registration_status', 'approved');
        if ($branch) {
            $ridersQuery->forBranch($branch, $areaIds);
        }
        $riders = $ridersQuery->get();

        $counts = Parcel::query()
            ->forBranch($branch)
            ->forDate($selectedDate)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'branch' => $branch,
                'date' => $selectedDateStr,
                'counts' => $counts,
                'total' => $parcels->total(),
                'latest_update' => Parcel::query()->forBranch($branch)->forDate($selectedDate)->max('updated_at'),
            ]);
        }

        $hubs = \App\Models\Hub::where('is_active', true)->orderBy('name')->get(['id', 'name', 'branch', 'city']);

        return view('sorting-center.index', compact('parcels', 'areas', 'riders', 'counts', 'branch', 'hubs', 'selectedDateStr', 'formattedDate', 'isToday'));
    }

    public function receive(Request $request, ParcelIntakeService $intake)
    {
        $storeId = \App\Models\Store::where('is_active', true)->value('id');
        $data = $request->validate([
            'barcode' => ['nullable', 'string', 'max:100', 'unique:parcels,barcode'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address_line' => ['required', 'string', 'max:2000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'digits:4'],
            'destination_area_id' => ['nullable', Rule::exists('destination_areas', 'id')->where('store_id', $storeId)->where('is_active', true)],
        ], [
            'barcode.unique' => 'This barcode is already registered to a shipment.',
            'recipient_phone.regex' => 'Enter a Philippine mobile number such as 09171234567 or +639171234567.',
        ]);

        $branch = $request->user()?->getBranchName();
        if ($branch) {
            $data['branch'] = $branch;
        }

        if (empty($data['destination_area_id'])) {
            $matchedArea = \App\Services\LagunaHubProvisioner::resolveMatchingArea(
                $storeId,
                $branch ?: ($data['city'] ?? null),
                $data['address_line'] ?? null,
                $data['city'] ?? null,
                $data['postal_code'] ?? null
            );
            if ($matchedArea) {
                $data['destination_area_id'] = $matchedArea->id;
            }
        }

        if (! empty($data['barcode'])) {
            $data['barcode'] = trim($data['barcode'], "*# \t\n\r\0\x0B");
            $wb = app(\App\Http\Controllers\Api\ScannerApiController::class)->resolveWaybillData($data['barcode']);
            if ($wb) {
                $data['order_number'] = $wb['order_number'] ?? null;
                $data['total'] = $wb['total_amount'] ?? 0;
                $data['cod_amount'] = $wb['cod_amount'] ?? 0;
                $data['items'] = $wb['items'] ?? [];
                $data['metadata'] = [
                    'source' => 'lumora_waybill',
                    'waybill_order_id' => $wb['order_id'] ?? null,
                    'waybill_order_number' => $wb['order_number'] ?? null,
                    'shop_name' => $wb['shop_name'] ?? 'Lumora Marketplace',
                    'payment_method' => $wb['payment_method'] ?? 'cod',
                    'is_cod' => $wb['is_cod'] ?? true,
                    'cod_amount' => (float) ($wb['cod_amount'] ?? 0),
                    'total_amount' => (float) ($wb['total_amount'] ?? 0),
                    'items_summary' => $wb['items_summary'] ?? null,
                    'items' => $wb['items'] ?? [],
                ];
            }
        }

        try {
            $parcel = $intake->receive($data, $request->user()->id);
        } catch (QueryException $exception) {
            // The unique barcode constraint also protects simultaneous submissions.
            if ($exception instanceof \Illuminate\Database\UniqueConstraintViolationException && $request->filled('barcode') && Parcel::where('barcode', $data['barcode'])->exists()) {
                return back()->withInput()->withErrors(['barcode' => 'This barcode is already registered to a shipment.']);
            }
            report($exception);
            return back()->withInput()->withErrors(['receive' => 'The parcel could not be saved. Please try again. No partial shipment was saved.']);
        }

        return back()->with('success', "Parcel received successfully. Track ID: {$parcel->parcel_number}");
    }
    public function scan(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('parcels', 'barcode')->ignore($parcel->id)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $branch = $parcel->branch ?: $request->user()?->getBranchName();
        $resolvedArea = \App\Services\LagunaHubProvisioner::resolveMatchingArea(
            $parcel->store_id,
            $branch,
            $parcel->address_line,
            $parcel->city,
            $parcel->postal_code
        );
        $area = $resolvedArea ?: $parcel->area;
        $autoRider = \App\Services\LagunaHubProvisioner::resolveRiderForArea($area, $branch);

        DB::transaction(function () use ($request, $parcel, $data, $area, $autoRider) {
            $newBarcode = ($data['barcode'] ?? null) ?: $parcel->barcode;

            if ($autoRider && $area) {
                $parcel->update([
                    'status' => 'assigned',
                    'scanned_at' => now(),
                    'sorted_at' => $parcel->sorted_at ?: now(),
                    'assigned_at' => now(),
                    'barcode' => $newBarcode,
                    'destination_area_id' => $area->id,
                    'assigned_rider_id' => $autoRider->id,
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'scan',
                    'barcode' => $newBarcode,
                    'note' => ($data['note'] ? $data['note'] . ' · ' : '') . "Auto-assigned to {$autoRider->user->name} ({$area->name})",
                    'scanned_at' => now(),
                ]);
            } else {
                $parcel->update([
                    'status' => 'scanned',
                    'scanned_at' => now(),
                    'barcode' => $newBarcode,
                    'destination_area_id' => $area?->id ?: $parcel->destination_area_id,
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'scan',
                    'barcode' => $newBarcode,
                    'note' => $data['note'] ?? null,
                    'scanned_at' => now(),
                ]);
            }
        });

        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        if ($autoRider && $area) {
            return back()->with('success', "Parcel {$parcel->parcel_number} scanned, matched to {$area->name}, and automatically assigned to {$autoRider->user->name}!");
        }

        return back()->with('success', "Parcel {$parcel->parcel_number} scanned" . ($area ? " ({$area->name})." : "."));
    }

    public function sort(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'destination_area_id' => ['required', 'exists:destination_areas,id'],
            'sort_bin' => ['nullable', 'string', 'max:50'],
        ]);

        $area = DestinationArea::find($data['destination_area_id']);
        $branch = $area?->branch ?: ($parcel->branch ?: $request->user()?->getBranchName());
        $autoRider = \App\Services\LagunaHubProvisioner::resolveRiderForArea($area, $branch);

        DB::transaction(function () use ($request, $parcel, $data, $area, $autoRider) {
            if ($autoRider) {
                $parcel->update([
                    'destination_area_id' => $data['destination_area_id'],
                    'sort_bin' => $data['sort_bin'] ?? null,
                    'assigned_rider_id' => $autoRider->id,
                    'status' => 'assigned',
                    'sorted_at' => now(),
                    'assigned_at' => now(),
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'sort',
                    'note' => 'Sorted to ' . ($area?->name ?? 'area') . ' and auto-assigned to ' . $autoRider->user->name,
                    'scanned_at' => now(),
                ]);
            } else {
                $parcel->update([
                    'destination_area_id' => $data['destination_area_id'],
                    'sort_bin' => $data['sort_bin'] ?? null,
                    'status' => 'sorted',
                    'sorted_at' => now(),
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'sort',
                    'note' => 'Sorted to bin ' . (($data['sort_bin'] ?? null) ?: 'unassigned'),
                    'scanned_at' => now(),
                ]);
            }
        });

        if ($autoRider) {
            app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
            return back()->with('success', "Parcel {$parcel->parcel_number} sorted to {$area?->name} and automatically assigned to {$autoRider->user->name}!");
        }

        return back()->with('success', "Parcel {$parcel->parcel_number} sorted.");
    }

    public function assign(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'assigned_rider_id' => ['required', 'exists:rider_profiles,id'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rider = RiderProfile::with('areas')
            ->where('id', $data['assigned_rider_id'])
            ->where('registration_status', 'approved')
            ->first();

        if (! $rider) {
            return back()->withErrors(['assigned_rider_id' => 'The selected rider is not an approved rider.']);
        }

        // If the rider is not yet assigned to this parcel's destination area, auto-assign the area so dispatch succeeds
        if ($parcel->destination_area_id && ! $rider->areas->contains('id', $parcel->destination_area_id)) {
            RiderAreaAssignment::updateOrCreate(
                ['rider_profile_id' => $rider->id, 'destination_area_id' => $parcel->destination_area_id],
                ['is_active' => true, 'is_primary' => $rider->areaAssignments()->doesntExist()]
            );
            $rider->load('areas');
        }

        // If parcel does not have an area yet but the rider has one, adopt the rider's primary area
        if (! $parcel->destination_area_id && $rider->areas->isNotEmpty()) {
            $parcel->destination_area_id = $rider->areas->first()->id;
        }

        DB::transaction(function () use ($request, $parcel, $data, $rider) {
            $parcel->update([
                'assigned_rider_id' => $rider->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'destination_area_id' => $parcel->destination_area_id,
            ]);

            if ((float) ($data['commission_amount'] ?? 0) > 0) {
                CommissionTransaction::updateOrCreate(
                    ['parcel_id' => $parcel->id, 'rider_profile_id' => $rider->id],
                    [
                        'amount' => $data['commission_amount'],
                        'status' => 'pending',
                        'description' => 'Delivery commission for ' . $parcel->parcel_number,
                        'earned_at' => now(),
                    ]
                );
            }

            $parcel->scans()->create([
                'user_id' => $request->user()->id,
                'scan_type' => 'assign',
                'note' => 'Assigned to ' . $rider->user->name,
                'scanned_at' => now(),
            ]);
        });

        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        return back()->with('success', "Parcel {$parcel->parcel_number} successfully assigned to {$rider->user->name}.");
    }

    public function riderAccept(Request $request, Parcel $parcel)
    {
        $rider = $request->user()->riderProfile;
        abort_unless($rider && $parcel->assigned_rider_id === $rider->id, 403);
        DB::transaction(function () use ($parcel) {
        $parcel->update(['status' => 'rider_received', 'rider_received_at' => now()]);
        });
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
        return back()->with('success', 'Delivery assignment accepted.');
    }

    /**
     * Hub-to-Hub transfer: Dispatch a parcel from the current branch hub to another destination branch hub.
     */
    public function transferHub(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'target_branch' => ['required', 'string', 'max:100'],
            'assigned_rider_id' => ['nullable', 'exists:rider_profiles,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $originBranch = $parcel->branch ?: ($request->user()?->getBranchName() ?: 'Origin');
        $targetBranch = trim($data['target_branch']);

        $storeId = $parcel->store_id ?: \App\Models\Store::where('is_active', true)->value('id');
        $targetArea = \App\Services\LagunaHubProvisioner::resolveMatchingArea(
            $storeId,
            $targetBranch,
            $parcel->address_line,
            $parcel->city ?: $targetBranch,
            $parcel->postal_code
        );

        $existingMeta = is_array($parcel->metadata) ? $parcel->metadata : [];
        $history = $existingMeta['hub_transfers'] ?? [];
        $history[] = [
            'from_hub' => "{$originBranch} Hub",
            'to_hub' => "{$targetBranch} Hub",
            'dispatched_at' => now()->toIso8601String(),
            'dispatched_by' => $request->user()?->name ?? 'Hub Admin',
        ];

        $newMeta = array_merge($existingMeta, [
            'is_hub_transfer' => true,
            'transfer_state' => 'in_transit_to_hub',
            'origin_branch' => $originBranch,
            'destination_branch' => $targetBranch,
            'hub_transfers' => $history,
        ]);

        DB::transaction(function () use ($request, $parcel, $data, $originBranch, $targetBranch, $targetArea, $newMeta) {
            $parcel->update([
                'branch' => $targetBranch,
                'destination_area_id' => $targetArea?->id ?? $parcel->destination_area_id,
                'assigned_rider_id' => $data['assigned_rider_id'] ?? $parcel->assigned_rider_id,
                'status' => 'in_transit',
                'metadata' => $newMeta,
            ]);

            $parcel->scans()->create([
                'user_id' => $request->user()->id,
                'scan_type' => 'hub_transfer_dispatch',
                'barcode' => $parcel->barcode ?: $parcel->parcel_number,
                'note' => $data['note'] ?: "Dispatched for Hub-to-Hub transfer from {$originBranch} Hub to {$targetBranch} Hub",
                'scanned_at' => now(),
            ]);

            if ($parcel->shipment) {
                $parcel->shipment->trackingEvents()->create([
                    'status' => 'in_transit',
                    'label' => "In Transit to {$targetBranch} Hub",
                    'description' => "Parcel departed {$originBranch} Hub and is in transit to {$targetBranch} Hub.",
                    'location' => "{$originBranch} Hub → {$targetBranch} Hub, Laguna",
                    'occurred_at' => now(),
                    'source' => 'sorting_center',
                ]);
            }
        });

        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        return back()->with('success', "Parcel {$parcel->parcel_number} dispatched from {$originBranch} Hub to {$targetBranch} Hub.");
    }

    /**
     * Hub-to-Hub arrival: Receive a transferred parcel at the destination branch hub so it can be sorted and assigned to a local rider.
     */
    public function receiveHubTransfer(Request $request, Parcel $parcel)
    {
        $receivingBranch = $request->user()?->getBranchName()
            ?: ($parcel->metadata['destination_branch'] ?? $parcel->branch ?: 'Destination');
        $originBranch = $parcel->metadata['origin_branch'] ?? 'Origin';

        $storeId = $parcel->store_id ?: \App\Models\Store::where('is_active', true)->value('id');
        $targetArea = \App\Services\LagunaHubProvisioner::resolveMatchingArea(
            $storeId,
            $receivingBranch,
            $parcel->address_line,
            $parcel->city ?: $receivingBranch,
            $parcel->postal_code
        );

        $existingMeta = is_array($parcel->metadata) ? $parcel->metadata : [];
        $newMeta = array_merge($existingMeta, [
            'is_hub_transfer' => true,
            'transfer_state' => 'received_at_hub',
            'destination_branch' => $receivingBranch,
            'received_at_hub_time' => now()->toIso8601String(),
        ]);

        $autoRider = \App\Services\LagunaHubProvisioner::resolveRiderForArea($targetArea, $receivingBranch);

        DB::transaction(function () use ($request, $parcel, $originBranch, $receivingBranch, $targetArea, $newMeta, $autoRider) {
            if ($autoRider && $targetArea) {
                $parcel->update([
                    'branch' => $receivingBranch,
                    'destination_area_id' => $targetArea->id,
                    'assigned_rider_id' => $autoRider->id,
                    'status' => 'assigned',
                    'scanned_at' => now(),
                    'sorted_at' => now(),
                    'assigned_at' => now(),
                    'metadata' => $newMeta,
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'hub_transfer_arrive',
                    'barcode' => $parcel->barcode ?: $parcel->parcel_number,
                    'note' => "Arrived at {$receivingBranch} Hub (from {$originBranch} Hub) and auto-assigned to {$autoRider->user->name} ({$targetArea->name})",
                    'scanned_at' => now(),
                ]);
            } else {
                $parcel->update([
                    'branch' => $receivingBranch,
                    'destination_area_id' => $targetArea?->id ?? $parcel->destination_area_id,
                    'assigned_rider_id' => null,
                    'status' => 'scanned',
                    'scanned_at' => now(),
                    'metadata' => $newMeta,
                ]);
                $parcel->scans()->create([
                    'user_id' => $request->user()->id,
                    'scan_type' => 'hub_transfer_arrive',
                    'barcode' => $parcel->barcode ?: $parcel->parcel_number,
                    'note' => "Arrived and received at {$receivingBranch} Hub (transferred from {$originBranch} Hub)",
                    'scanned_at' => now(),
                ]);
            }

            if ($parcel->shipment) {
                $parcel->shipment->trackingEvents()->create([
                    'status' => 'in_transit',
                    'label' => "Arrived at {$receivingBranch} Hub",
                    'description' => $autoRider
                        ? "Parcel arrived at {$receivingBranch} Hub and was automatically assigned to courier {$autoRider->user->name} ({$targetArea?->name})."
                        : "Parcel arrived at {$receivingBranch} Hub from {$originBranch} Hub and is queued for local sorting & dispatch.",
                    'location' => "{$receivingBranch} Hub, Laguna",
                    'occurred_at' => now(),
                    'source' => 'sorting_center',
                ]);
            }
        });

        if ($autoRider && $targetArea) {
            app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
            return back()->with('success', "Parcel {$parcel->parcel_number} received at {$receivingBranch} Hub, matched to {$targetArea->name}, and automatically assigned to {$autoRider->user->name}!");
        }

        return back()->with('success', "Parcel {$parcel->parcel_number} received at {$receivingBranch} Hub! Ready to sort and assign to a local rider.");
    }
}
