<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DestinationArea;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\Store;
use App\Models\User;
use App\Services\ParcelIntakeService;
use App\Services\ParcelShipmentSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ScannerApiController extends Controller
{
    /**
     * Authenticate warehouse operator / scanner staff.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $passwordMatches = false;
        if (str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$2a$') || str_starts_with($user->password, '$argon2')) {
            $passwordMatches = Hash::check($validated['password'], $user->password);
        } else {
            if ($validated['password'] === $user->password) {
                $passwordMatches = true;
                $user->update(['password' => Hash::make($validated['password'])]);
            }
        }

        if (! $passwordMatches) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken('scanner-app-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Quick status check of sorting queue and scanner connectivity.
     */
    public function status(): JsonResponse
    {
        $counts = Parcel::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentCount = ParcelScan::where('created_at', '>=', now()->startOfDay())->count();

        $latestUpdate = (string) Parcel::max('updated_at');
        $totalParcels = Parcel::count();
        $scannedCount = $counts['scanned'] ?? 0;
        $receivedCount = $counts['received'] ?? 0;

        return response()->json([
            'status' => 'success',
            'service' => 'Lumora Parcel Scanner Engine',
            'online' => true,
            'counts' => [
                'received' => $receivedCount,
                'scanned' => $scannedCount,
                'sorted' => $counts['sorted'] ?? 0,
                'assigned' => $counts['assigned'] ?? 0,
                'in_transit' => ($counts['in_transit'] ?? 0) + ($counts['out_for_delivery'] ?? 0),
                'delivered' => $counts['delivered'] ?? 0,
            ],
            'total_parcels' => $totalParcels,
            'latest_update' => $latestUpdate,
            'scans_today' => $recentCount,
            'signature' => md5("{$totalParcels}-{$latestUpdate}-{$scannedCount}-{$recentCount}"),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Process barcode scan from the Flutter camera scanner.
     * Transitions parcel to 'scanned' status and sends it to the sorting center queue.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:150'],
            'action' => ['nullable', 'string', 'in:auto,scan_only,intake_if_missing'],
            'device' => ['nullable', 'string', 'max:100'],
            'operator' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $code = trim($validated['barcode']);
        $operatorName = $validated['operator'] ?? ($request->user()?->name ?? 'Scanner Device');
        $userId = $request->user()?->id ?? User::whereIn('role', ['admin', 'warehouse_staff', 'operations_manager'])->value('id') ?? 1;
        $storeId = Store::where('is_active', true)->value('id') ?? Store::value('id') ?? 1;

        // Find parcel by barcode, parcel_number (e.g. LM-TRK-000001), or ID
        $parcel = Parcel::with(['area', 'rider.user', 'shipment'])
            ->where('barcode', $code)
            ->orWhere('parcel_number', $code)
            ->orWhere('id', is_numeric($code) ? (int) $code : -1)
            ->orWhereHas('shipment', fn ($q) => $q->where('tracking_number', $code))
            ->first();

        // 1. If parcel already exists in the system
        if ($parcel) {
            // Auto-detect destination area if not set yet
            $area = $parcel->area ?: DestinationArea::where('store_id', $parcel->store_id ?? $storeId)
                ->where('is_active', true)
                ->get()
                ->first(function ($candidate) use ($parcel) {
                    $cities = collect($candidate->cities ?? [])->map(fn ($c) => mb_strtolower(trim($c)));
                    $postalCodes = collect($candidate->postal_codes ?? [])->map(fn ($p) => trim($p));
                    $matchCity = $parcel->city && $cities->contains(mb_strtolower(trim($parcel->city)));
                    $matchPostal = $parcel->postal_code && $postalCodes->contains(trim($parcel->postal_code));
                    $matchProvince = $candidate->province && $parcel->province && mb_strtolower(trim($candidate->province)) === mb_strtolower(trim($parcel->province));
                    return $matchCity || $matchPostal || ($cities->isEmpty() && $postalCodes->isEmpty() && $matchProvince);
                });

            $oldStatus = $parcel->status;
            $isAlreadyScanned = in_array($oldStatus, ['scanned', 'sorted', 'assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'delivered']);

            if ($isAlreadyScanned) {
                return response()->json([
                    'status' => 'already_scanned',
                    'result_type' => 'already_scanned',
                    'is_already_scanned' => true,
                    'message' => "Parcel {$parcel->parcel_number} is ALREADY SCANNED! Current status: " . ucwords(str_replace('_', ' ', $oldStatus)),
                    'parcel' => $this->transformParcel($parcel),
                    'stage' => $oldStatus,
                    'ready_for_sorting' => in_array($oldStatus, ['scanned']),
                ]);
            }

            $newStatus = 'scanned';

            $parcel->update([
                'status' => $newStatus,
                'scanned_at' => $parcel->scanned_at ?? now(),
                'barcode' => $parcel->barcode ?: $code,
                'destination_area_id' => $parcel->destination_area_id ?: $area?->id,
            ]);

            // Record scan history
            ParcelScan::create([
                'parcel_id' => $parcel->id,
                'user_id' => $userId,
                'scan_type' => 'camera_scan',
                'barcode' => $code,
                'result' => 'success',
                'note' => "Scanned by {$operatorName}. Status: " . ucwords(str_replace('_', ' ', $newStatus)),
                'scanned_at' => now(),
            ]);

            // Sync with shipment
            app(ParcelShipmentSyncService::class)->sync($parcel->fresh());

            return response()->json([
                'status' => 'success',
                'result_type' => 'scanned_success',
                'is_already_scanned' => false,
                'message' => "Parcel {$parcel->parcel_number} scanned successfully! Sent to sorting queue.",
                'parcel' => $this->transformParcel($parcel->fresh(['area', 'rider.user', 'shipment'])),
                'stage' => $newStatus,
                'ready_for_sorting' => true,
            ]);
        }

        // 2. If parcel does NOT exist yet, auto-register as new intake parcel
        $recipientName = $validated['recipient_name'] ?? 'Intake Package';
        $city = $validated['city'] ?? 'Santa Cruz';
        $province = $validated['province'] ?? 'Laguna';
        $addressLine = $validated['address_line'] ?? "Delivery destination, {$city}, {$province}";
        $postalCode = $validated['postal_code'] ?? '4009';

        $intakeService = app(ParcelIntakeService::class);
        $newParcel = $intakeService->receive([
            'barcode' => $code,
            'recipient_name' => $recipientName,
            'recipient_phone' => '09170000000',
            'address_line' => $addressLine,
            'city' => $city,
            'province' => $province,
            'postal_code' => $postalCode,
        ], $userId);

        // Mark as scanned immediately
        $area = DestinationArea::where('store_id', $storeId)
            ->where('is_active', true)
            ->get()
            ->first(function ($candidate) use ($city, $province, $postalCode) {
                $cities = collect($candidate->cities ?? [])->map(fn ($c) => mb_strtolower(trim($c)));
                $postalCodes = collect($candidate->postal_codes ?? [])->map(fn ($p) => trim($p));
                $matchCity = $cities->contains(mb_strtolower(trim($city)));
                $matchPostal = $postalCodes->contains(trim($postalCode));
                $matchProvince = $candidate->province && mb_strtolower(trim($candidate->province)) === mb_strtolower(trim($province));
                return $matchCity || $matchPostal || ($cities->isEmpty() && $postalCodes->isEmpty() && $matchProvince);
            });

        $newParcel->update([
            'status' => 'scanned',
            'scanned_at' => now(),
            'destination_area_id' => $area?->id,
        ]);

        ParcelScan::create([
            'parcel_id' => $newParcel->id,
            'user_id' => $userId,
            'scan_type' => 'scanner_app_intake',
            'barcode' => $code,
            'result' => 'success',
            'note' => "New intake scan by {$operatorName}. Sent to sorting center.",
            'scanned_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'result_type' => 'new_intake_created',
            'message' => "New parcel {$newParcel->parcel_number} registered and queued for sorting!",
            'parcel' => $this->transformParcel($newParcel->fresh(['area', 'rider.user', 'shipment'])),
            'stage' => 'scanned',
            'ready_for_sorting' => true,
        ]);
    }

    /**
     * Batch sync offline scans collected by the mobile scanner device.
     */
    public function batchSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scans' => ['required', 'array'],
            'scans.*.barcode' => ['required', 'string', 'max:150'],
            'scans.*.scanned_at' => ['nullable', 'string'],
            'operator' => ['nullable', 'string', 'max:100'],
        ]);

        $results = [];
        $syncedParcels = [];
        $operator = $validated['operator'] ?? 'Offline Scanner Sync';

        foreach ($validated['scans'] as $item) {
            $subReq = new Request([
                'barcode' => trim($item['barcode']),
                'operator' => $operator,
                'device' => 'Mobile Offline Sync',
            ]);

            $response = $this->scan($subReq);
            $data = $response->getData(true);
            $results[] = $data;
            if (($data['status'] ?? '') === 'success' && isset($data['parcel'])) {
                $syncedParcels[] = $data['parcel'];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($syncedParcels) . ' offline parcel scans synced to sorting queue!',
            'synced_count' => count($syncedParcels),
            'parcels' => $syncedParcels,
        ]);
    }

    /**
     * Get recent scans for live feed on mobile device.
     */
    public function recentScans(): JsonResponse
    {
        $scans = ParcelScan::with(['parcel.area', 'parcel.rider.user', 'user'])
            ->latest('scanned_at')
            ->take(25)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $scans->map(fn ($s) => [
                'id' => $s->id,
                'barcode' => $s->barcode,
                'parcel_number' => $s->parcel?->parcel_number,
                'recipient' => $s->parcel?->recipient_name,
                'city' => $s->parcel?->city,
                'province' => $s->parcel?->province,
                'status' => $s->parcel?->status ?? 'scanned',
                'area_name' => $s->parcel?->area?->name ?? 'Determined at Sort',
                'sort_bin' => $s->parcel?->sort_bin ?? 'Unassigned',
                'assigned_rider' => $s->parcel?->rider?->user?->name ?? 'Unassigned',
                'scanned_at' => $s->scanned_at?->format('g:i:s A'),
                'time_ago' => $s->scanned_at?->diffForHumans(),
            ]),
        ]);
    }

    private function transformParcel(Parcel $parcel): array
    {
        return [
            'id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
            'barcode' => $parcel->barcode,
            'recipient_name' => $parcel->recipient_name,
            'recipient_phone' => $parcel->recipient_phone,
            'address_line' => $parcel->address_line,
            'city' => $parcel->city,
            'province' => $parcel->province,
            'postal_code' => $parcel->postal_code,
            'status' => $parcel->status,
            'status_label' => ucwords(str_replace('_', ' ', $parcel->status)),
            'sort_bin' => $parcel->sort_bin ?: 'Pending Sorting',
            'area_name' => $parcel->area?->name ?: 'Determined after scan',
            'area_province' => $parcel->area?->province ?: $parcel->province,
            'assigned_rider' => $parcel->rider?->user?->name ?: 'Not Assigned',
            'scanned_at' => $parcel->scanned_at?->format('M j, Y · g:i A'),
        ];
    }
}

