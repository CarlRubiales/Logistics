<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionTransaction;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RiderApiController extends Controller
{
    /**
     * Authenticate rider and return API bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
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
                // Automatically upgrade plain-text password to bcrypt
                $user->update(['password' => Hash::make($validated['password'])]);
            }
        }

        if (! $passwordMatches) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->role !== 'rider') {
            return response()->json([
                'status' => 'error',
                'message' => 'This account does not have rider access.',
            ], 403);
        }

        $profile = $user->riderProfile;

        // Ensure rider profile exists
        if (! $profile) {
            $profile = RiderProfile::create([
                'user_id' => $user->id,
                'registration_status' => 'approved',
                'availability' => 'offline',
            ]);
        }

        // Check if rider registration is approved
        if ($profile->registration_status === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your rider account is awaiting administrator approval in the logistics portal.',
            ], 403);
        }

        if ($profile->registration_status === 'disapproved' || $profile->registration_status === 'rejected') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your rider registration was not approved. Please contact logistics support.',
            ], 403);
        }

        // Revoke previous mobile tokens if any
        $user->tokens()->where('name', 'flutter-rider-app')->delete();
        $token = $user->createToken('flutter-rider-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone ?? '',
                'vehicle_type' => $profile->vehicle_type ?? 'Motorcycle',
                'license_number' => $profile->license_number ?? '',
                'availability' => $profile->availability ?? 'offline',
                'registration_status' => $profile->registration_status ?? 'approved',
            ],
        ]);
    }

    /**
     * Register a new rider.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:40'],
            'vehicle_type' => ['nullable', 'string', 'max:80'],
            'license_number' => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'rider',
        ]);

        $profile = RiderProfile::create([
            'user_id' => $user->id,
            'phone' => $validated['phone'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? 'Motorcycle',
            'license_number' => $validated['license_number'] ?? null,
            'registration_status' => 'pending',
            'availability' => 'offline',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration submitted successfully. Awaiting administrator approval.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'registration_status' => $profile->registration_status,
            ],
        ], 201);
    }

    /**
     * Return authenticated rider info and profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone ?? '',
                'vehicle_type' => $profile->vehicle_type ?? 'Motorcycle',
                'license_number' => $profile->license_number ?? '',
                'availability' => $profile->availability ?? 'offline',
                'registration_status' => $profile->registration_status ?? 'approved',
            ],
        ]);
    }

    /**
     * Update rider availability (online / offline).
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'available' => ['sometimes', 'boolean'],
            'availability' => ['sometimes', 'string', 'in:online,offline'],
        ]);

        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $isOnline = isset($validated['available'])
            ? (bool) $validated['available']
            : (($validated['availability'] ?? '') === 'online');

        $profile->update([
            'availability' => $isOnline ? 'online' : 'offline',
        ]);

        return response()->json([
            'status' => 'success',
            'availability' => $profile->availability,
            'is_online' => $isOnline,
        ]);
    }

    /**
     * Return deliveries assigned to the authenticated rider.
     */
    public function deliveries(Request $request): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $parcels = Parcel::where('assigned_rider_id', $profile->id)
            ->latest('assigned_at')
            ->get();

        $data = $parcels->map(fn (Parcel $p) => $this->transformParcel($p));

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Return available deliveries that can be accepted.
     */
    public function availableDeliveries(Request $request): JsonResponse
    {
        $parcels = Parcel::whereNull('assigned_rider_id')
            ->whereIn('status', ['received', 'sorted', 'pending'])
            ->latest()
            ->take(20)
            ->get();

        $data = $parcels->map(fn (Parcel $p) => $this->transformParcel($p));

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Accept a delivery request.
     */
    public function acceptDelivery(Request $request, $id): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $parcel = Parcel::where('id', $id)
            ->orWhere('parcel_number', $id)
            ->first();

        if (! $parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel not found.'], 404);
        }

        $parcel->update([
            'assigned_rider_id' => $profile->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()->id,
            'scan_type' => 'assigned',
            'barcode' => $parcel->barcode,
            'result' => 'success',
            'note' => 'Accepted by rider ' . $request->user()->name,
            'scanned_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $this->transformParcel($parcel),
        ]);
    }

    /**
     * Update delivery progress stage (pickedUp / in_transit / delivered).
     */
    public function updateDeliveryStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'in:accepted,pickedUp,delivered,in_transit'],
            'status' => ['sometimes', 'string'],
        ]);

        $stageInput = $validated['stage'] ?? $validated['status'] ?? 'in_transit';

        $dbStatus = match ($stageInput) {
            'pickedUp', 'in_transit' => 'in_transit',
            'delivered' => 'delivered',
            default => 'assigned',
        };

        $parcel = Parcel::where('id', $id)
            ->orWhere('parcel_number', $id)
            ->first();

        if (! $parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel not found.'], 404);
        }

        $parcel->update([
            'status' => $dbStatus,
            'rider_received_at' => $dbStatus === 'in_transit' ? now() : $parcel->rider_received_at,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()->id,
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode,
            'result' => 'success',
            'note' => 'Status updated to ' . $dbStatus . ' by rider ' . $request->user()->name,
            'scanned_at' => now(),
        ]);

        // If delivered, record commission transaction if none exists yet
        if ($dbStatus === 'delivered' && $parcel->assigned_rider_id) {
            CommissionTransaction::firstOrCreate(
                [
                    'rider_profile_id' => $parcel->assigned_rider_id,
                    'parcel_id' => $parcel->id,
                ],
                [
                    'amount' => 150.00,
                    'status' => 'pending',
                    'description' => 'Delivery payout for ' . $parcel->parcel_number,
                    'earned_at' => now(),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->transformParcel($parcel),
        ]);
    }

    /**
     * Return rider earnings and commission history.
     */
    public function earnings(Request $request): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json([
                'status' => 'success',
                'today' => '₱0.00',
                'balance' => '₱0.00',
                'completed' => '0 deliveries completed',
                'history' => [],
            ]);
        }

        $todayStart = now()->startOfDay();

        $todayEarnings = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->where('earned_at', '>=', $todayStart)
            ->sum('amount');

        $totalBalance = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->where('status', 'pending')
            ->sum('amount');

        $todayDeliveriesCount = Parcel::where('assigned_rider_id', $profile->id)
            ->where('status', 'delivered')
            ->where('updated_at', '>=', $todayStart)
            ->count();

        $historyTransactions = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->with('parcel')
            ->latest('earned_at')
            ->take(10)
            ->get();

        $history = $historyTransactions->map(function ($tx) {
            return [
                'id' => $tx->parcel?->parcel_number ?? ('TX-' . $tx->id),
                'store' => $tx->description ?? 'Standard Delivery',
                'fee' => '₱' . number_format($tx->amount, 2),
                'date' => $tx->earned_at ? $tx->earned_at->format('M d, Y') : now()->format('M d, Y'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'today' => '₱' . number_format($todayEarnings, 2),
            'balance' => '₱' . number_format($totalBalance, 2),
            'completed' => "{$todayDeliveriesCount} deliveries completed",
            'history' => $history,
        ]);
    }

    /**
     * Logout and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Helper to transform parcel into Flutter-compatible JSON.
     */
    private function transformParcel(Parcel $p): array
    {
        return [
            'id' => $p->parcel_number,
            'db_id' => $p->id,
            'store' => 'Lumora Express Hub',
            'pickup' => 'Central Warehouse Hub',
            'dropoff' => $p->address_line ?: ($p->city . ', ' . $p->province),
            'fee' => '₱150.00',
            'distance' => '3.8 km',
            'eta' => '25 min',
            'status' => $p->status,
            'stage' => match ($p->status) {
                'in_transit' => 'pickedUp',
                'delivered' => 'delivered',
                default => 'accepted',
            },
            'recipient_name' => $p->recipient_name,
            'recipient_phone' => $p->recipient_phone ?? '',
            'barcode' => $p->barcode ?? '',
        ];
    }
}

