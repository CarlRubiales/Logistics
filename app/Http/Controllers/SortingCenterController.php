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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SortingCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = Parcel::with(['area', 'rider.user', 'shipment'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) { $q->where('parcel_number', 'like', "%{$term}%")->orWhere('recipient_name', 'like', "%{$term}%")->orWhere('city', 'like', "%{$term}%"); });
        }

        $parcels = $query->paginate(15)->withQueryString();
        $areas = DestinationArea::where('is_active', true)->orderBy('name')->get();
        $riders = RiderProfile::with(['user', 'areas'])->where('registration_status', 'approved')->get();
        $counts = Parcel::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'counts' => $counts,
                'total' => $parcels->total(),
                'latest_update' => Parcel::max('updated_at'),
            ]);
        }

        return view('sorting-center.index', compact('parcels', 'areas', 'riders', 'counts'));
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
        $data = $request->validate(['barcode' => ['nullable', 'string', 'max:100', Rule::unique('parcels', 'barcode')->ignore($parcel->id)], 'note' => ['nullable', 'string', 'max:500']]);
        $area = $parcel->area ?: DestinationArea::where('store_id', $parcel->store_id)->where('is_active', true)->get()->first(function ($candidate) use ($parcel) {
            $cities = collect($candidate->cities ?? [])->map(fn ($city) => mb_strtolower(trim($city)));
            $postalCodes = collect($candidate->postal_codes ?? [])->map(fn ($code) => trim($code));
            $matchCity = $parcel->city && $cities->contains(mb_strtolower(trim($parcel->city)));
            $matchPostal = $parcel->postal_code && $postalCodes->contains(trim($parcel->postal_code));
            $matchProvince = $candidate->province && $parcel->province && mb_strtolower(trim($candidate->province)) === mb_strtolower(trim($parcel->province));
            return $matchCity || $matchPostal || ($cities->isEmpty() && $postalCodes->isEmpty() && $matchProvince);
        });
        DB::transaction(function () use ($request, $parcel, $data, $area) {
        $parcel->update(['status' => 'scanned', 'scanned_at' => now(), 'barcode' => ($data['barcode'] ?? null) ?: $parcel->barcode, 'destination_area_id' => $parcel->destination_area_id ?: $area?->id]);
        $parcel->scans()->create(['user_id' => $request->user()->id, 'scan_type' => 'scan', 'barcode' => ($data['barcode'] ?? null) ?: $parcel->barcode, 'note' => $data['note'] ?? null, 'scanned_at' => now()]);
        });
        return back()->with('success', "Parcel {$parcel->parcel_number} scanned.");
    }

    public function sort(Request $request, Parcel $parcel)
    {
        $data = $request->validate(['destination_area_id' => ['required', 'exists:destination_areas,id'], 'sort_bin' => ['nullable', 'string', 'max:50']]);
        DB::transaction(function () use ($request, $parcel, $data) {
        $parcel->update(['destination_area_id' => $data['destination_area_id'], 'sort_bin' => $data['sort_bin'] ?? null, 'status' => 'sorted', 'sorted_at' => now()]);
        $parcel->scans()->create(['user_id' => $request->user()->id, 'scan_type' => 'sort', 'note' => 'Sorted to bin '.(($data['sort_bin'] ?? null) ?: 'unassigned'), 'scanned_at' => now()]);
        });
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
}
