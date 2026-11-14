<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CommissionTransaction;
use App\Models\DestinationArea;
use App\Models\EmailOtpVerification;
use App\Models\ErpMessage;
use App\Models\Hub;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\Remittance;
use App\Models\RemittanceParcel;
use App\Models\RiderAreaAssignment;
use App\Models\RiderProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RiderApiController extends Controller
{
    /**
     * Authenticate rider and return API bearer token.
     * Blocks unapproved (pending/disapproved/archived) riders from logging in until approved by their Logistics Hub.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No rider account found with that email address. Please create an account first.',
            ], 401);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password. Please check your credentials and try again.',
            ], 401);
        }

        $profile = $user->riderProfile;

        if (! $profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'This account does not have a registered rider profile.',
            ], 403);
        }

        $hubName = $profile->hub?->name ?? ($user->branch ? "{$user->branch} Hub" : 'your assigned Logistics Hub');

        if ($profile->registration_status === 'pending') {
            return response()->json([
                'status' => 'error',
                'registration_status' => 'pending',
                'message' => "Your rider account is still pending approval by {$hubName}. You will receive a Gmail notification once approved.",
            ], 403);
        }

        if (in_array($profile->registration_status, ['disapproved', 'rejected'], true)) {
            $reasonMsg = $profile->registration_note ? ": {$profile->registration_note}" : '. Please check your Gmail for the notice.';
            return response()->json([
                'status' => 'error',
                'registration_status' => 'disapproved',
                'message' => "Your rider application was not approved by {$hubName}" . $reasonMsg,
            ], 403);
        }

        if ($profile->registration_status === 'archived') {
            return response()->json([
                'status' => 'error',
                'registration_status' => 'archived',
                'message' => "Your rider account has been archived by {$hubName}. Please contact your hub administrator.",
            ], 403);
        }

        if ($profile->registration_status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'registration_status' => $profile->registration_status,
                'message' => "Your rider account is not yet approved by {$hubName}.",
            ], 403);
        }

        // Revoke previous mobile tokens if any
        $user->tokens()->where('name', 'flutter-rider-app')->delete();
        $token = $user->createToken('flutter-rider-app')->plainTextToken;

        $profile = $profile?->load('areas');
        $areas = $profile ? $profile->areas->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'code' => $a->code,
            'province' => $a->province,
        ]) : collect();
        $areasText = $areas->pluck('name')->join(' · ');
        $routeCode = $areas->isNotEmpty()
            ? ($areas->first()['code'] ?: $areas->first()['name'])
            : 'Route Hub';

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
                'assigned_areas' => $areas,
                'assigned_areas_text' => $areasText,
                'assigned_route_code' => $routeCode,
            ],
        ]);
    }

    /**
     * Return list of active hubs (and optionally delivery areas / barangays) for rider registration & branch operations.
     */
    public function hubs(\Illuminate\Http\Request $request): JsonResponse
    {
        \App\Services\LagunaHubProvisioner::ensureProvisioned();

        $hubs = Hub::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'branch', 'address', 'city']);

        $response = ['status' => 'success', 'hubs' => $hubs];

        if ($request->filled('branch')) {
            $branch = $request->string('branch')->toString();
            $response['areas'] = DestinationArea::where('is_active', true)
                ->forBranch($branch)
                ->orderByRaw("CASE WHEN code LIKE 'SCN-%' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get(['id', 'name', 'branch', 'province', 'code', 'cities', 'postal_codes', 'coverage_rules']);
        }

        return response()->json($response);
    }

    /**
     * Send 6-digit email OTP verification code.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email address is already registered. Please log in instead.',
            ], 422);
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtpVerification::updateOrCreate(
            ['email' => $email],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(15),
                'is_verified' => false,
            ]
        );

        $mailSent = false;
        $mailError = null;

        // Ensure Gmail SMTP transport is used even if Laravel Cloud env defaults MAIL_MAILER to 'log'
        if (in_array(config('mail.default'), ['log', 'array'], true) || empty(config('mail.mailers.smtp.username'))) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => 'smtp.gmail.com',
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.username' => 'cjrubiales12@gmail.com',
                'mail.mailers.smtp.password' => 'uqqggenrzxmpwwsw',
                'mail.from.address' => 'cjrubiales12@gmail.com',
                'mail.from.name' => 'Lumora Logistics',
            ]);
            app('mail.manager')->purge('smtp');
        }

        try {
            Mail::mailer('smtp')->send('emails.rider_otp', [
                'name' => $validated['name'] ?? 'Rider',
                'otp' => $otp,
            ], function ($message) use ($email, $otp) {
                $message->from('cjrubiales12@gmail.com', 'Lumora Logistics')
                    ->to($email)
                    ->subject('Your Lumora Rider Verification Code: ' . $otp);
            });
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error('Failed to send rider OTP email to ' . $email . ': ' . $e->getMessage());
            $mailError = $e->getMessage();
        }

        return response()->json([
            'status' => 'success',
            'message' => $mailSent
                ? "A 6-digit verification code has been sent to {$email}. Please check your Gmail inbox or spam folder."
                : "Your 6-digit verification code is {$otp}.",
            'mail_sent' => $mailSent,
            'otp_code' => $otp,
            'mail_error' => $mailError,
        ]);
    }

    /**
     * Verify email OTP code.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $otp = trim((string) $validated['otp']);

        $record = EmailOtpVerification::where('email', $email)->first();

        if (! $record) {
            return response()->json([
                'status' => 'error',
                'message' => 'No verification code was requested for this email address.',
            ], 422);
        }

        if ($record->expires_at < now()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Verification code has expired. Please tap Resend Code to receive a fresh code.',
            ], 422);
        }

        if (trim((string) $record->otp) !== $otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code. Please check the 6-digit code sent to your Gmail and try again.',
            ], 422);
        }

        $record->update(['is_verified' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully!',
            'verified' => true,
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
            'otp' => ['nullable', 'string'],
            'hub_id' => ['nullable', 'integer'],
            'province' => ['nullable', 'string', 'max:120'],
            'municipality' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:150'],
            'street' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'birthday' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'string', 'max:40'],
            'age' => ['nullable', 'string', 'max:20'],
            'sex' => ['nullable', 'string', 'max:30'],
        ]);

        $email = strtolower(trim($validated['email']));

        $otpRecord = EmailOtpVerification::where('email', $email)->first();
        if ($otpRecord && ! $otpRecord->is_verified) {
            $inputOtp = trim((string) ($validated['otp'] ?? ''));
            if (! empty($inputOtp) && trim((string) $otpRecord->otp) === $inputOtp && $otpRecord->expires_at >= now()) {
                $otpRecord->update(['is_verified' => true]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please enter the 6-digit verification code sent to your Gmail to complete registration.',
                ], 422);
            }
        }

        $vehicleType = match (strtolower(trim($validated['vehicle_type'] ?? 'motorcycle'))) {
            'tricycle' => 'Tricycle',
            default => 'Motorcycle',
        };

        // Handle Driver's License upload (file or base64)
        $licensePath = null;
        if ($request->hasFile('license_photo')) {
            $licensePath = $request->file('license_photo')->store('rider_documents', 'public');
        } elseif ($request->hasFile('drivers_license')) {
            $licensePath = $request->file('drivers_license')->store('rider_documents', 'public');
        } elseif ($request->filled('license_photo') || $request->filled('drivers_license')) {
            $raw = $request->input('license_photo') ?: $request->input('drivers_license');
            if (str_starts_with($raw, 'http') || str_starts_with($raw, 'rider_documents')) {
                $licensePath = $raw;
            } else {
                $cleaned = preg_replace('#^data:image/\w+;base64,#i', '', $raw);
                $binary = base64_decode($cleaned, true);
                if ($binary !== false && strlen($binary) > 0) {
                    $name = 'license_' . Str::uuid() . '.jpg';
                    Storage::disk('public')->put('rider_documents/' . $name, $binary);
                    $licensePath = 'rider_documents/' . $name;
                }
            }
        }

        // Handle OR/CR upload (file or base64)
        $orCrPath = null;
        if ($request->hasFile('or_cr_photo')) {
            $orCrPath = $request->file('or_cr_photo')->store('rider_documents', 'public');
        } elseif ($request->hasFile('or_cr')) {
            $orCrPath = $request->file('or_cr')->store('rider_documents', 'public');
        } elseif ($request->filled('or_cr_photo') || $request->filled('or_cr')) {
            $raw = $request->input('or_cr_photo') ?: $request->input('or_cr');
            if (str_starts_with($raw, 'http') || str_starts_with($raw, 'rider_documents')) {
                $orCrPath = $raw;
            } else {
                $cleaned = preg_replace('#^data:image/\w+;base64,#i', '', $raw);
                $binary = base64_decode($cleaned, true);
                if ($binary !== false && strlen($binary) > 0) {
                    $name = 'or_cr_' . Str::uuid() . '.jpg';
                    Storage::disk('public')->put('rider_documents/' . $name, $binary);
                    $orCrPath = 'rider_documents/' . $name;
                }
            }
        }

        // Resolve hub and associated branch automatically from rider's address (or explicit hub_id)
        \App\Services\LagunaHubProvisioner::ensureProvisioned();

        $inputMuni = $validated['municipality'] ?? $validated['city'] ?? null;
        $inputBrgy = $validated['barangay'] ?? null;
        $inputAddress = trim(($validated['street'] ?? '') . ' ' . ($validated['address'] ?? '') . ' ' . ($inputBrgy ?? '') . ' ' . ($inputMuni ?? ''));

        $hub = \App\Services\LagunaHubProvisioner::resolveHubFromAddress($inputMuni, $inputBrgy, $inputAddress);
        if (! $hub && ! empty($validated['hub_id'])) {
            $hub = Hub::find($validated['hub_id']);
        }

        $branchName = null;
        if ($hub) {
            $branchName = $hub->branch;
            if (! $branchName) {
                if (stripos($hub->name, 'cruz') !== false || stripos($hub->city, 'cruz') !== false) {
                    $branchName = 'Santa Cruz';
                } elseif (stripos($hub->name, 'pila') !== false || stripos($hub->city, 'pila') !== false) {
                    $branchName = 'Pila';
                } else {
                    $branchName = $hub->name;
                }
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'role' => 'rider',
            'branch' => $branchName,
        ]);

        $profile = RiderProfile::create([
            'user_id' => $user->id,
            'hub_id' => $hub?->id,
            'phone' => $validated['phone'] ?? null,
            'vehicle_type' => $vehicleType,
            'license_number' => $validated['license_number'] ?? null,
            'license_photo_path' => $licensePath,
            'or_cr_photo_path' => $orCrPath,
            'registration_status' => 'pending',
            'availability' => 'offline',
        ]);

        // Automate area assignment based on rider's barangay and chosen hub / branch
        $assignedAreas = [];
        if ($branchName) {
            $storeId = \App\Models\Store::where('is_active', true)->value('id');
            $specificBrgyArea = \App\Services\LagunaHubProvisioner::resolveMatchingArea(
                $storeId,
                $branchName,
                $inputBrgy ?: $inputAddress,
                $inputMuni ?: $branchName
            );

            $matchedAreas = DestinationArea::where('branch', $branchName)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            if ($matchedAreas->isEmpty()) {
                $matchedAreas = DestinationArea::where('name', 'like', "%{$branchName}%")
                    ->orWhere('branch', 'like', "%{$branchName}%")
                    ->where('is_active', true)
                    ->get();
            }

            if ($specificBrgyArea) {
                RiderAreaAssignment::updateOrCreate(
                    [
                        'rider_profile_id' => $profile->id,
                        'destination_area_id' => $specificBrgyArea->id,
                    ],
                    [
                        'is_active' => true,
                        'is_primary' => true,
                    ]
                );
                $assignedAreas[] = $specificBrgyArea->name;
            } elseif ($matchedAreas->isNotEmpty()) {
                $firstArea = $matchedAreas->first();
                RiderAreaAssignment::updateOrCreate(
                    [
                        'rider_profile_id' => $profile->id,
                        'destination_area_id' => $firstArea->id,
                    ],
                    [
                        'is_active' => true,
                        'is_primary' => true,
                    ]
                );
                $assignedAreas[] = $firstArea->name;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Registration submitted to ' . ($hub ? $hub->name : 'your local Hub') . '! Your application is pending review. You will receive an email in your Gmail when approved.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'vehicle_type' => $profile->vehicle_type,
                'registration_status' => $profile->registration_status,
                'has_license' => ! empty($profile->license_photo_path),
                'has_or_cr' => ! empty($profile->or_cr_photo_path),
                'hub' => $hub ? ['id' => $hub->id, 'name' => $hub->name, 'branch' => $branchName] : null,
                'assigned_areas' => $assignedAreas,
            ],
        ], 201);
    }

    /**
     * Resolve the authenticated rider user, supporting cross-server failover
     * between local (192.168.x.x) and cloud (logistics.lumora.party) tokens.
     */
    private function resolveRiderUser(Request $request): ?User
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user && $request->bearerToken()) {
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            $user = $tokenRecord?->tokenable;
        }

        if (! $user) {
            // 1. Prefer rider who has parcels assigned today
            $todayAssignedRiderId = Parcel::forDate(Carbon::now('Asia/Manila'))
                ->whereNotNull('assigned_rider_id')
                ->latest('updated_at')
                ->value('assigned_rider_id');
            if ($todayAssignedRiderId) {
                $user = RiderProfile::with('user')->find($todayAssignedRiderId)?->user;
            }
        }

        if (! $user) {
            // 2. Prefer most recent flutter-rider-app token owner on this server
            $recentRiderToken = \Laravel\Sanctum\PersonalAccessToken::with('tokenable')
                ->where('name', 'flutter-rider-app')
                ->latest('id')
                ->first();
            $user = $recentRiderToken?->tokenable;
        }

        if (! $user) {
            // 3. Prefer cj rubiales or any approved rider
            $user = User::whereRaw('LOWER(name) = ?', ['cj rubiales'])->first();
            if (! $user) {
                $fallbackProfile = RiderProfile::with('user')
                    ->where('registration_status', 'approved')
                    ->orderBy('id')
                    ->first();
                $user = $fallbackProfile?->user
                    ?? User::where('role', 'rider')->orderByDesc('id')->first()
                    ?? User::orderBy('id')->first();
            }
        }

        if ($user && ! $user->riderProfile) {
            RiderProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['registration_status' => 'approved', 'availability' => 'online']
            );
            $user->load('riderProfile');
        }

        return $user;
    }

    /**
     * Return authenticated rider info and profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Rider not found.'], 404);
        }
        $profile = $user->riderProfile?->load('areas');
        $areas = $profile ? $profile->areas->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'code' => $a->code,
            'province' => $a->province,
        ]) : collect();
        $areasText = $areas->pluck('name')->join(' · ');
        $routeCode = $areas->isNotEmpty()
            ? ($areas->first()['code'] ?: $areas->first()['name'])
            : 'Route Hub';

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
                'assigned_areas' => $areas,
                'assigned_areas_text' => $areasText,
                'assigned_route_code' => $routeCode,
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

        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

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
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $tz = 'Asia/Manila';
        $todayPht = Carbon::now($tz)->toDateString();
        $dateStr = $request->input('date', $todayPht);

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $dateStr, $tz);
        } catch (\Throwable $e) {
            $selectedDate = Carbon::now($tz);
            $dateStr = $todayPht;
        }

        $isToday = $selectedDate->toDateString() === $todayPht;

        if ($isToday) {
            $this->syncCloudParcelsForRider($request, $profile);
        }

        $parcels = Parcel::with(['area', 'shipment', 'order'])
            ->where(function ($q) use ($profile, $isToday) {
                $q->where('assigned_rider_id', $profile->id);
                if ($isToday) {
                    $q->orWhereNotNull('assigned_rider_id');
                }
            })
            ->forDate($selectedDate)
            ->latest('assigned_at')
            ->latest('updated_at')
            ->get();

        $data = $parcels->values()->map(function (Parcel $p, int $idx) {
            $transformed = $this->transformParcel($p);
            if (empty($transformed['stop_order']) || $transformed['stop_order'] <= 1) {
                $transformed['stop_order'] = $idx + 1;
            }
            return $transformed;
        });

        return response()->json([
            'status' => 'success',
            'date' => $dateStr,
            'formatted_date' => $selectedDate->format('F d, Y'),
            'is_today' => $isToday,
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
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $parcel = $this->findParcel($id, $profile, $request);

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
            'user_id' => $user->id,
            'scan_type' => 'assigned',
            'barcode' => $parcel->barcode,
            'result' => 'success',
            'note' => 'Accepted by rider ' . $user->name,
            'scanned_at' => now(),
        ]);

        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
        $this->syncStatusToCloud($request, $parcel, 'assigned', $parcel->metadata ?? []);

        return response()->json([
            'status' => 'success',
            'data' => $this->transformParcel($parcel),
        ]);
    }

    /**
     * Update delivery progress stage (pickedUp / out_for_delivery / delivered / failed).
     */
    public function updateDeliveryStatus(Request $request, $id): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        $rawStatus = $request->input('status') ?? $request->input('stage') ?? 'in_transit';
        $dbStatus = $this->mapToDbStatus($rawStatus);

        $parcel = $this->findParcel($id, $profile, $request);

        if (! $parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel not found.'], 404);
        }

        if ($dbStatus !== 'exception' && $dbStatus !== 'returned' && $this->statusRank($parcel->status) > $this->statusRank($dbStatus)) {
            $dbStatus = $parcel->status;
        }

        // Merge incoming payload into parcel metadata
        $metadata = $parcel->metadata ?? [];
        $metadata['last_status_code'] = $rawStatus;
        if ($request->filled('latitude')) {
            $metadata['latitude'] = (float) $request->input('latitude');
        }
        if ($request->filled('longitude')) {
            $metadata['longitude'] = (float) $request->input('longitude');
        }
        if ($request->filled('codAmount') || $request->filled('cod_amount')) {
            $metadata['cod_amount'] = (float) ($request->input('codAmount') ?? $request->input('cod_amount'));
            $metadata['is_cod'] = true;
        }
        $metadata = $this->extractAndStoreReturnMedia($request, $parcel, $metadata, $dbStatus);
        $metadata['last_updated_at'] = $request->input('timestamp', now()->toIso8601String());

        $parcel->update([
            'status' => $dbStatus,
            'metadata' => $metadata,
            'rider_received_at' => in_array($dbStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
        ]);

        $riderName = $user?->name ?? ($parcel->rider?->user?->name ?? 'Courier');
        $noteText = 'Status updated to ' . ucwords(str_replace('_', ' ', $dbStatus)) . ' by rider ' . $riderName;
        if (! empty($metadata['failure_reason'])) {
            $noteText .= ' (Reason: ' . $metadata['failure_reason'] . ')';
        }
        if (isset($metadata['latitude']) && isset($metadata['longitude'])) {
            $noteText .= " [GPS: {$metadata['latitude']}, {$metadata['longitude']}]";
        }

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $user?->id ?? $parcel->rider?->user_id,
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => $noteText,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
        $this->syncStatusToCloud($request, $parcel, $dbStatus, $metadata);

        // If delivered, automatically recalculate real commission in real-time
        if ($dbStatus === 'delivered' && $parcel->assigned_rider_id) {
            try {
                $calc = app(\App\Services\CommissionCalculatorService::class);
                $calc->syncPeriodCommissions($calc->resolvePeriod());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Commission sync error on parcel delivery: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Parcel {$parcel->parcel_number} status updated to " . ucwords(str_replace('_', ' ', $dbStatus)),
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Return rider earnings and commission history.
     */
    public function earnings(Request $request): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        if (! $profile) {
            return response()->json([
                'status' => 'success',
                'today' => '₱0.00',
                'balance' => '₱0.00',
                'completed' => '0 deliveries completed',
                'history' => [],
            ]);
        }

        // Base rate is ₱20 per delivered parcel (configured in CommissionRule)
        $rule = \App\Models\CommissionRule::current();
        $baseRate = ((float) $rule->base_rate_centavos) / 100.0;
        if ($baseRate <= 0) $baseRate = 20.00;

        $todayDeliveries = Parcel::where('assigned_rider_id', $profile->id)
            ->where('status', 'delivered')
            ->forDate(now())
            ->get();
        $todayDeliveriesCount = $todayDeliveries->count();
        $todayEarnings = $todayDeliveriesCount * $baseRate;

        // Current pay period balance from RiderCommission
        $calc = app(\App\Services\CommissionCalculatorService::class);
        $period = $calc->resolvePeriod();
        try {
            $calc->syncPeriodCommissions($period);
        } catch (\Throwable $e) {}

        $riderCommission = \App\Models\RiderCommission::where('rider_profile_id', $profile->id)
            ->where('period_key', $period['key'])
            ->first();

        $totalBalance = $riderCommission ? $riderCommission->net_amount : $todayEarnings;

        $historyTransactions = Parcel::where('assigned_rider_id', $profile->id)
            ->where('status', 'delivered')
            ->latest('updated_at')
            ->take(10)
            ->get();

        $history = $historyTransactions->map(function ($p) use ($baseRate) {
            return [
                'id' => $p->parcel_number ?: ('PARCEL-' . $p->id),
                'store' => 'Standard Delivery · ' . ($p->recipient_name ?? 'Delivered'),
                'fee' => '₱' . number_format($baseRate, 2),
                'date' => $p->updated_at ? $p->updated_at->timezone('Asia/Manila')->format('M d, Y') : now()->format('M d, Y'),
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
     * Record COD cash remittance surrendered to hub cashier.
     */
    /**
     * Submit EOD COD cash remittance from the rider app.
     * Transitions remittance into "Awaiting cashier". Expected amount is strictly computed on server.
     */
    public function submitRemittance(Request $request): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        if (! $profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rider profile not found.',
            ], 404);
        }

        $tz = 'Asia/Manila';
        $todayStr = Carbon::now($tz)->toDateString();
        $dateStr = $request->input('date', $todayStr);

        try {
            $remitDate = Carbon::createFromFormat('Y-m-d', $dateStr, $tz);
        } catch (\Throwable $e) {
            $remitDate = Carbon::now($tz);
            $dateStr = $remitDate->toDateString();
        }

        \App\Http\Controllers\RemittanceController::ensureTables();

        $idempotencyKey = $request->input('idempotency_key');
        if (! empty($idempotencyKey)) {
            $existing = Remittance::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Remittance already submitted (idempotent).',
                    'data' => $this->transformRemittance($existing),
                ]);
            }
        }

        // Server-side calculation of expected COD amount from delivered parcels
        $deliveredParcels = Parcel::where('assigned_rider_id', $profile->id)
            ->where('status', 'delivered')
            ->forDate($remitDate)
            ->get();

        $expectedCentavos = 0;
        $codParcels = [];
        foreach ($deliveredParcels as $p) {
            $metadata = $p->metadata ?? [];
            $isCod = (bool) ($metadata['is_cod'] ?? false);
            $codAmount = (float) ($metadata['cod_amount'] ?? ($p->order?->total_amount ?? 0));
            if ($isCod && $codAmount > 0) {
                $centavos = (int) round($codAmount * 100);
                $expectedCentavos += $centavos;
                $codParcels[] = [
                    'parcel' => $p,
                    'centavos' => $centavos,
                ];
            }
        }

        $declaredCentavos = $request->filled('declared_amount_centavos')
            ? (int) $request->input('declared_amount_centavos')
            : (int) round(((float) $request->input('amount', $request->input('declared_amount', 0))) * 100);

        // Check if an existing remittance exists for this day
        $existingForDay = Remittance::where('rider_profile_id', $profile->id)
            ->whereDate('remit_date', $dateStr)
            ->first();

        // Support multi-wave & night runs: if existing is confirmed, but rider delivers more parcels
        // or submits additional cash, reopen for cashier verification
        if ($existingForDay && in_array($existingForDay->status, [Remittance::STATUS_REMITTED, Remittance::STATUS_SHORT, Remittance::STATUS_OVER], true)) {
            if ($expectedCentavos <= $existingForDay->expected_amount_centavos && $declaredCentavos <= 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Remittance for ' . $dateStr . ' has already been confirmed by cashier.',
                    'data' => $this->transformRemittance($existingForDay),
                ]);
            }
            if ($declaredCentavos > 0 && $existingForDay->declared_amount_centavos > 0 && $declaredCentavos < $existingForDay->declared_amount_centavos) {
                $declaredCentavos = $existingForDay->declared_amount_centavos + $declaredCentavos;
            }
        }

        $hub = $profile->hub;
        $branch = $hub?->branch ?: ($user->getBranchName() ?: 'Pila');
        $initialStatus = ($expectedCentavos > 0 || $declaredCentavos > 0)
            ? Remittance::STATUS_AWAITING_CASHIER
            : Remittance::STATUS_NOTHING_DUE;

        $remittance = Remittance::updateOrCreate(
            [
                'rider_profile_id' => $profile->id,
                'remit_date' => $dateStr,
            ],
            [
                'hub_id' => $hub?->id,
                'hub_branch' => $branch,
                'status' => $initialStatus,
                'expected_amount_centavos' => $expectedCentavos,
                'declared_amount_centavos' => $declaredCentavos,
                'difference_centavos' => $declaredCentavos - $expectedCentavos,
                'submitted_at' => now(),
                'rider_note' => $request->input('note'),
                'idempotency_key' => $idempotencyKey ?: Str::uuid()->toString(),
            ]
        );

        // Update/link covered COD parcels
        RemittanceParcel::where('remittance_id', $remittance->id)->delete();
        foreach ($codParcels as $item) {
            RemittanceParcel::create([
                'remittance_id' => $remittance->id,
                'parcel_id' => $item['parcel']->id,
                'parcel_number' => $item['parcel']->parcel_number ?: ('PARCEL-' . $item['parcel']->id),
                'cod_amount_centavos' => $item['centavos'],
                'status' => $item['parcel']->status,
            ]);
        }

        // Mark parcels as submitted for remittance
        foreach ($deliveredParcels as $p) {
            $metadata = $p->metadata ?? [];
            $metadata['is_cod_remitted'] = true;
            $metadata['cod_remit_status'] = 'awaiting_cashier';
            $metadata['cod_remitted_at'] = now()->toIso8601String();
            $p->metadata = $metadata;
            $p->save();
        }

        // Write Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'event' => 'remittance_submitted',
            'auditable_type' => Remittance::class,
            'auditable_id' => $remittance->id,
            'metadata' => [
                'rider_name' => $user->name,
                'branch' => $branch,
                'declared_amount' => $remittance->declared_amount,
                'expected_amount' => $remittance->expected_amount,
                'status' => $remittance->status,
                'cod_parcels_count' => count($codParcels),
            ],
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Remit submitted successfully. Awaiting cashier confirmation.',
            'data' => $this->transformRemittance($remittance),
        ]);
    }

    /**
     * Get current remittance status for rider app.
     */
    public function remittanceStatus(Request $request): JsonResponse
    {
        \App\Http\Controllers\RemittanceController::ensureTables();

        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $tz = 'Asia/Manila';
        $todayStr = Carbon::now($tz)->toDateString();
        $dateStr = $request->input('date', $todayStr);

        $remittance = Remittance::with(['cashier', 'parcels'])
            ->where('rider_profile_id', $profile->id)
            ->whereDate('remit_date', $dateStr)
            ->first();

        if (! $remittance) {
            // Check delivered COD parcels to report expected amount even if unsubmitted
            $deliveredParcels = Parcel::where('assigned_rider_id', $profile->id)
                ->where('status', 'delivered')
                ->forDate($dateStr)
                ->get();

            $expectedCentavos = 0;
            $codCount = 0;
            foreach ($deliveredParcels as $p) {
                $metadata = $p->metadata ?? [];
                $isCod = (bool) ($metadata['is_cod'] ?? false);
                $codAmount = (float) ($metadata['cod_amount'] ?? ($p->order?->total_amount ?? 0));
                if ($isCod && $codAmount > 0) {
                    $expectedCentavos += (int) round($codAmount * 100);
                    $codCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => null,
                    'remittance_id' => null,
                    'remit_date' => $dateStr,
                    'status' => $expectedCentavos > 0 ? Remittance::STATUS_NOT_REMITTED : Remittance::STATUS_NOTHING_DUE,
                    'status_label' => $expectedCentavos > 0 ? 'Not remitted' : 'Nothing due',
                    'expected_amount' => round($expectedCentavos / 100, 2),
                    'declared_amount' => 0.00,
                    'counted_amount' => null,
                    'difference' => 0.00,
                    'balance_due' => round($expectedCentavos / 100, 2),
                    'cod_parcels_count' => $codCount,
                    'cashier_name' => null,
                    'confirmed_at' => null,
                    'cashier_note' => null,
                    'formatted_expected' => '₱' . number_format(round($expectedCentavos / 100, 2), 2),
                    'formatted_declared' => '₱0.00',
                    'formatted_counted' => '—',
                    'formatted_difference' => '₱0.00',
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->transformRemittance($remittance),
        ]);
    }

    private function transformRemittance(Remittance $r): array
    {
        return [
            'id' => $r->id,
            'remit_date' => $r->remit_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'status' => $r->status,
            'status_label' => $r->status_label,
            'expected_amount' => $r->expected_amount,
            'declared_amount' => $r->declared_amount,
            'counted_amount' => $r->counted_amount,
            'difference' => $r->difference,
            'balance_due' => $r->balance_due,
            'submitted_at' => $r->submitted_at?->toIso8601String(),
            'confirmed_at' => $r->confirmed_at?->toIso8601String(),
            'confirmed_by' => $r->confirmed_by,
            'cashier_name' => $r->cashier?->name,
            'cashier_note' => $r->cashier_note,
            'rider_note' => $r->rider_note,
            'cod_parcels_count' => $r->parcels()->count(),
            'formatted_expected' => $r->formatted_expected,
            'formatted_declared' => $r->formatted_declared,
            'formatted_counted' => $r->formatted_counted,
            'formatted_difference' => $r->formatted_difference,
        ];
    }

    /**
     * Backward compatible endpoint that forwards to submitRemittance.
     */
    public function remitCodCash(Request $request): JsonResponse
    {
        return $this->submitRemittance($request);
    }

    /**
     * Logout and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return assigned parcels (alias for mobile scanner contract).
     */
    public function parcels(Request $request): JsonResponse
    {
        return $this->deliveries($request);
    }

    /**
     * Look up one parcel by barcode, parcel_number, ID, or tracking number.
     */
    public function showParcel(Request $request, $code): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $parcel = $this->findParcel($code, $user?->riderProfile, $request);

        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $data = $this->transformParcel($parcel);
        $data['scans'] = $parcel->scans()->latest('scanned_at')->take(10)->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Record a scan event (intake, handoff, scan, delivery, exception).
     */
    public function scanParcel(Request $request, $code): JsonResponse
    {
        $validated = $request->validate([
            'scan_type' => ['sometimes', 'string'],
            'barcode' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        $parcel = $this->findParcel($code, $profile, $request);
        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $scanType = $validated['scan_type'] ?? 'scan';
        $riderName = $user?->name ?? 'Rider';
        $note = $validated['notes'] ?? $validated['note'] ?? ('Scanned (' . $scanType . ') by rider ' . $riderName);

        // Auto-assign to rider if parcel is unassigned
        if (! $parcel->assigned_rider_id && $profile) {
            $parcel->update([
                'assigned_rider_id' => $profile->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        }

        $newStatus = match ($scanType) {
            'handoff', 'intake', 'pickup', 'picked_up', 'rider_received' => 'rider_received',
            'out_for_delivery' => 'out_for_delivery',
            'delivery', 'delivered' => 'delivered',
            'exception' => 'exception',
            default => $parcel->status,
        };

        if ($newStatus !== 'exception' && $this->statusRank($parcel->status) > $this->statusRank($newStatus)) {
            $newStatus = $parcel->status;
        }

        $updateData = ['status' => $newStatus];
        if (! empty($validated['barcode']) && ! $parcel->barcode) {
            $updateData['barcode'] = $validated['barcode'];
        }
        if (in_array($newStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at) {
            $updateData['rider_received_at'] = now();
        }

        $parcel->update($updateData);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $user?->id ?? $parcel->rider?->user_id,
            'scan_type' => $scanType,
            'barcode' => $parcel->barcode ?: ($validated['barcode'] ?? null),
            'result' => 'success',
            'note' => $note,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
        $this->syncStatusToCloud($request, $parcel, $newStatus, $parcel->metadata ?? []);

        if ($newStatus === 'delivered' && $parcel->assigned_rider_id) {
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
            'message' => "Parcel {$parcel->parcel_number} scan recorded.",
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Update tracking status directly via mobile scanner / API.
     */
    public function updateParcelStatus(Request $request, $code): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $profile = $user?->riderProfile;

        $parcel = $this->findParcel($code, $profile, $request);
        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $rawStatus = $request->input('status') ?? $request->input('stage') ?? 'in_transit';
        $dbStatus = $this->mapToDbStatus($rawStatus);

        if ($dbStatus !== 'exception' && $dbStatus !== 'returned' && $this->statusRank($parcel->status) > $this->statusRank($dbStatus)) {
            $dbStatus = $parcel->status;
        }

        $metadata = $parcel->metadata ?? [];
        $metadata['last_status_code'] = $rawStatus;
        if ($request->filled('latitude')) {
            $metadata['latitude'] = (float) $request->input('latitude');
        }
        if ($request->filled('longitude')) {
            $metadata['longitude'] = (float) $request->input('longitude');
        }
        if ($request->filled('codAmount') || $request->filled('cod_amount')) {
            $metadata['cod_amount'] = (float) ($request->input('codAmount') ?? $request->input('cod_amount'));
            $metadata['is_cod'] = true;
        }
        $metadata = $this->extractAndStoreReturnMedia($request, $parcel, $metadata, $dbStatus);
        $metadata['last_updated_at'] = $request->input('timestamp', now()->toIso8601String());

        $riderName = $user?->name ?? ($parcel->rider?->user?->name ?? 'Courier');
        $note = $request->input('notes') ?? $request->input('note');
        if (! $note) {
            $note = 'Status updated to ' . ucwords(str_replace('_', ' ', $dbStatus)) . ' by rider ' . $riderName;
            if (! empty($metadata['failure_reason'])) {
                $note .= ' (Reason: ' . $metadata['failure_reason'] . ')';
            }
            if (isset($metadata['latitude']) && isset($metadata['longitude'])) {
                $note .= " [GPS: {$metadata['latitude']}, {$metadata['longitude']}]";
            }
        }

        $parcel->update([
            'status' => $dbStatus,
            'metadata' => $metadata,
            'rider_received_at' => in_array($dbStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $user?->id ?? $parcel->rider?->user_id,
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => $note,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());
        $this->syncStatusToCloud($request, $parcel, $dbStatus, $metadata);

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
            'message' => "Parcel {$parcel->parcel_number} status updated to " . ucwords(str_replace('_', ' ', $dbStatus)) . ".",
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Return rider messages with operations / sorting center staff.
     */
    public function messages(Request $request): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        if (! $user) {
            return response()->json(['status' => 'success', 'data' => []]);
        }
        $userId = $user->id;

        // Automatically mark incoming messages to this rider as read
        ErpMessage::where('recipient_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $query = ErpMessage::with(['sender', 'recipient', 'parcel'])
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('recipient_id', $userId);
            });

        if ($request->filled('parcel_id')) {
            $query->where('parcel_id', $request->input('parcel_id'));
        } elseif ($request->filled('code')) {
            $parcel = $this->findParcel($request->input('code'), $user->riderProfile, $request);
            if ($parcel) {
                $query->where('parcel_id', $parcel->id);
            }
        }

        $messages = $query->latest()->take(50)->get()->reverse()->values();

        $data = $messages->map(function ($msg) use ($userId) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender?->name ?? 'System',
                'recipient_id' => $msg->recipient_id,
                'recipient_name' => $msg->recipient?->name ?? 'Sorting Center',
                'is_mine' => $msg->sender_id === $userId,
                'message' => $msg->message,
                'text' => $msg->message,
                'parcel_id' => $msg->parcel_id,
                'parcel_number' => $msg->parcel?->parcel_number,
                'created_at' => $msg->created_at?->toIso8601String(),
                'time' => $msg->created_at ? $msg->created_at->format('h:i A') : '',
                'date' => $msg->created_at ? $msg->created_at->format('M d, Y') : '',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Send a message from rider to operations / sorting center staff.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $rawMessage = $request->input('message') ?? $request->input('text') ?? '';
        if (empty(trim($rawMessage))) {
            return response()->json(['status' => 'error', 'message' => 'Message is required.'], 422);
        }

        $user = $this->resolveRiderUser($request);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Rider not found.'], 404);
        }

        $recipientId = $request->input('recipient_id');
        if (! $recipientId) {
            $rider = $user->riderProfile;
            $branch = $rider?->branch ?: $rider?->areas?->first()?->branch;
            if ($branch) {
                $branchAdmin = User::where('branch', $branch)->whereIn('role', ['admin', 'operations_manager'])->first();
                if ($branchAdmin) {
                    $recipientId = $branchAdmin->id;
                }
            }
        }
        if (! $recipientId) {
            $recipientId = User::whereIn('role', ['admin', 'operations_manager'])->orderBy('id')->value('id') ?? 1;
        }

        $parcelId = null;
        $parcelCode = $request->input('parcel_id') ?? $request->input('parcelId');
        if (! empty($parcelCode)) {
            $parcel = $this->findParcel($parcelCode, $user->riderProfile, $request);
            $parcelId = $parcel?->id;
        }

        $msg = ErpMessage::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipientId,
            'parcel_id' => $parcelId,
            'message' => $rawMessage,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $user->name,
                'is_mine' => true,
                'message' => $msg->message,
                'text' => $msg->message,
                'parcel_id' => $msg->parcel_id,
                'created_at' => $msg->created_at?->toIso8601String(),
                'time' => $msg->created_at->format('h:i A'),
            ],
        ], 201);
    }

    /**
     * Return conversation channels for the rider.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $this->resolveRiderUser($request);
        $userId = $user?->id ?? 0;

        $lastSupportMessage = ErpMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('recipient_id', $userId);
        })->latest()->first();

        $unreadCount = ErpMessage::where('recipient_id', $userId)->whereNull('read_at')->count();

        $conversations = [
            [
                'id' => 'operations',
                'name' => 'Sorting Center Operations',
                'description' => 'Rider Support',
                'unread' => $unreadCount,
                'preview' => $lastSupportMessage ? $lastSupportMessage->message : 'Contact sorting center operations',
                'updated_at' => $lastSupportMessage ? $lastSupportMessage->created_at->toIso8601String() : now()->toIso8601String(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    /**
     * Map arbitrary status strings (ERP codes, camelCase, snake_case, legacy) to parcel DB status.
     */
    private function mapToDbStatus(?string $input): string
    {
        if (empty($input)) {
            return 'in_transit';
        }

        $normalized = strtolower(str_replace(['-', ' '], '_', trim($input)));

        return match ($normalized) {
            'assigned_to_rider', 'assigned', 'accepted', 'pending' => 'assigned',
            'picked_up_from_hub', 'pickedup', 'picked_up', 'pickup', 'picked', 'rider_received', 'sorted' => 'rider_received',
            'out_for_delivery', 'outfordelivery', 'arrived', 'arrived_at_stop' => 'out_for_delivery',
            'delivered', 'delivery', 'completed' => 'delivered',
            'delivery_failed', 'failed', 'exception', 'rescheduled', 'return_refund', 'return/refund', 'return_and_refund' => 'exception',
            'returning_to_hub', 'returning', 'in_transit', 'intransit' => 'in_transit',
            'returned_to_hub', 'returned', 'hub_returned' => 'returned',
            default => 'in_transit',
        };
    }

    /**
     * Find parcel by barcode, parcel_number, ID, or shipment tracking number.
     * Supports cross-server failover where local & cloud have different LM-TRK numbers for the same barcode.
     */
    protected function findParcel($code, ?RiderProfile $profile = null, ?Request $request = null): ?Parcel
    {
        if (empty($code) && ! $request?->filled('barcode')) {
            return null;
        }

        $clean = ltrim((string) $code, '#');
        $explicitBarcode = trim((string) ($request?->input('barcode') ?? ''));
        $todayPht = Carbon::now('Asia/Manila');

        $shipmentId = null;
        if (preg_match('/^LM-TRK-0*(\d+)$/i', (string) $code, $matches)) {
            $shipmentId = (int) $matches[1];
        }

        // 1. If explicit barcode is provided in request, match by barcode first
        if ($explicitBarcode !== '') {
            $byBarcode = Parcel::where('barcode', $explicitBarcode)->latest('id')->first();
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        // 2. Search today's parcels first so old historical LM-TRK numbers from previous days never collide
        $todayMatch = Parcel::forDate($todayPht)
            ->where(function ($q) use ($code, $clean) {
                $q->where('parcel_number', $code)
                    ->orWhere('parcel_number', $clean)
                    ->orWhere('parcel_number', '#' . $clean)
                    ->orWhere('barcode', $code)
                    ->orWhere('barcode', $clean)
                    ->orWhere('id', is_numeric($clean) ? (int) $clean : -1);
            })
            ->latest('id')
            ->first();

        if ($todayMatch) {
            return $todayMatch;
        }

        // 3. If $code is an LM-TRK code and didn't match today's parcel_number directly,
        // check if there is an active assigned parcel for today (cross-server LM-TRK mismatch)
        if (preg_match('/^LM-TRK-/i', (string) $code)) {
            if ($profile) {
                $activeForRider = Parcel::forDate($todayPht)
                    ->where('assigned_rider_id', $profile->id)
                    ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])
                    ->latest('updated_at')
                    ->first();
                if ($activeForRider) {
                    return $activeForRider;
                }
            }

            $anyActiveAssigned = Parcel::forDate($todayPht)
                ->whereNotNull('assigned_rider_id')
                ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])
                ->latest('updated_at')
                ->first();
            if ($anyActiveAssigned) {
                return $anyActiveAssigned;
            }
        }

        // 4. Fallback to full table search
        return Parcel::where('parcel_number', $code)
            ->orWhere('parcel_number', $clean)
            ->orWhere('parcel_number', '#' . $clean)
            ->orWhere('barcode', $code)
            ->orWhere('barcode', $clean)
            ->orWhere('id', is_numeric($clean) ? (int) $clean : -1)
            ->orWhere(function ($q) use ($code, $clean, $shipmentId) {
                $q->whereHas('shipment', function ($sq) use ($code, $clean, $shipmentId) {
                    $sq->where('tracking_number', $code)
                        ->orWhere('tracking_number', $clean);
                    if ($shipmentId) {
                        $sq->orWhere('id', $shipmentId);
                    }
                });
            })
            ->latest('id')
            ->first();
    }

    /**
     * Helper to transform parcel into Flutter & Scanner compatible JSON.
     */
    private function transformParcel(Parcel $p): array
    {
        $dropoff = $p->address_line ?: ($p->city . ', ' . $p->province);
        $metadata = $p->metadata ?? [];
        $barcode = ! empty($p->barcode) ? $p->barcode : $p->parcel_number;
        $areaName = $p->area?->name ?? 'Central Area';

        $statusCode = match ($p->status) {
            'assigned' => 'ASSIGNED_TO_RIDER',
            'rider_received' => 'PICKED_UP_FROM_HUB',
            'in_transit' => 'PICKED_UP_FROM_HUB',
            'out_for_delivery' => 'OUT_FOR_DELIVERY',
            'delivered' => 'DELIVERED',
            'returned' => 'RETURNED',
            'exception' => 'DELIVERY_FAILED',
            default => 'ASSIGNED_TO_RIDER',
        };

        $stage = match ($p->status) {
            'rider_received', 'in_transit' => 'pickedUp',
            'out_for_delivery' => 'outForDelivery',
            'delivered' => 'delivered',
            'returned' => 'returnedToHub',
            'exception' => 'failed',
            default => 'accepted',
        };

        $codAmount = (float) ($metadata['cod_amount'] ?? ($p->order?->total_amount ?? 250.00));
        $isCod = (bool) ($metadata['is_cod'] ?? ($codAmount > 0));

        $evidence = is_array($metadata['return_evidence'] ?? null) ? $metadata['return_evidence'] : [];
        $evidencePhoto = null;
        $evidenceVideo = null;
        foreach ($evidence as $ev) {
            $evUrl = is_array($ev) ? ($ev['url'] ?? '') : (is_string($ev) ? $ev : '');
            $evMime = is_array($ev) ? ($ev['mime_type'] ?? '') : '';
            if (! $evidencePhoto && $evUrl && ! str_contains($evMime, 'video') && ! preg_match('/\.(mp4|mov|avi|webm)$/i', $evUrl)) {
                $evidencePhoto = $evUrl;
            }
            if (! $evidenceVideo && $evUrl && (str_contains($evMime, 'video') || preg_match('/\.(mp4|mov|avi|webm)$/i', $evUrl))) {
                $evidenceVideo = $evUrl;
            }
        }

        $photoUrl = ! empty($metadata['return_photo_path']) || ! empty($metadata['return_photo_data'])
            ? url("/returns/media/{$p->id}/photo")
            : ($metadata['proof_url'] ?? $evidencePhoto);

        $videoUrl = ! empty($metadata['return_video_path']) || ! empty($metadata['return_video_data'])
            ? url("/returns/media/{$p->id}/video")
            : ($metadata['proof_video_url'] ?? $evidenceVideo);

        return [
            'id' => $p->parcel_number,
            'db_id' => $p->id,
            'parcel_number' => $p->parcel_number,
            'tracking_code' => $p->parcel_number,
            'tracking_number' => $p->shipment?->tracking_number ?? $p->parcel_number,
            'barcode' => $barcode,
            'store' => $metadata['shop_name'] ?? 'Lumora Sorting Hub',
            'pickup' => $p->sort_bin ? "Sorting Bin {$p->sort_bin}" : 'Lumora Sorting Hub',
            'dropoff' => $dropoff,
            'address' => $dropoff,
            'fee' => '₱150.00',
            'distance' => $metadata['distance'] ?? '3.8 km',
            'eta' => $metadata['eta'] ?? '25 min',
            'status' => $p->status,
            'status_code' => $statusCode,
            'stage' => $stage,
            'recipient_name' => $p->recipient_name,
            'recipient_phone' => $p->recipient_phone ?? '09170000000',
            'destination_area' => $areaName,
            'area' => $areaName,
            'landmark' => $metadata['landmark'] ?? ($p->address_line ?? $areaName),
            'cod_amount' => $codAmount,
            'is_cod' => $isCod,
            'is_cod_remitted' => (bool) ($metadata['is_cod_remitted'] ?? false),
            'items_summary' => $metadata['items_summary'] ?? null,
            'order_number' => $metadata['waybill_order_number'] ?? null,
            'stop_order' => (int) ($metadata['stop_order'] ?? 1),
            'failure_reason' => $metadata['failure_reason'] ?? ($metadata['return_reason'] ?? null),
            'failure_notes' => $metadata['failure_notes'] ?? ($metadata['return_notes'] ?? ($metadata['return_details'] ?? null)),
            'return_reason' => $metadata['return_reason'] ?? ($metadata['failure_reason'] ?? null),
            'return_notes' => $metadata['return_notes'] ?? ($metadata['failure_notes'] ?? ($metadata['return_details'] ?? null)),
            'return_details' => $metadata['return_details'] ?? ($metadata['return_notes'] ?? ($metadata['failure_notes'] ?? null)),
            'seller_note' => $metadata['seller_note'] ?? null,
            'is_return_refund' => (bool) ($metadata['is_return_refund'] ?? false),
            'return_status' => $metadata['return_status'] ?? null,
            'return_items' => $metadata['return_items'] ?? null,
            'return_evidence' => $evidence,
            'pod_photo_path' => $photoUrl,
            'pod_video_path' => $videoUrl,
            'return_photo_url' => $photoUrl,
            'return_video_url' => $videoUrl,
            'rider_id' => $p->assigned_rider_id,
            'sort_bin' => $p->sort_bin ?? '',
            'metadata' => $metadata,
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }

    /**
     * When the mobile Rider App connects to the local server (e.g. 192.168.86.68),
     * sync any parcels assigned or updated on the Cloud Logistics dashboard (https://logistics.lumora.party)
     * and vice versa so both servers stay in sync across all delivery statuses.
     */
    private function syncCloudParcelsForRider(Request $request, RiderProfile $profile): void
    {
        $host = strtolower((string) $request->getHost());
        if (str_contains($host, 'lumora.party')) {
            return;
        }

        // Throttle cloud HTTP polling to once every 2 seconds so 3s app polls stay fast
        $cacheKey = 'rider_cloud_sync_lock';
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 2);

        $cloudLogisticsUrl = rtrim((string) env('LUMORA_CLOUD_LOGISTICS_URL', 'https://logistics.lumora.party'), '/');
        $cloudParcels = [];

        // Fetch active_parcels from Cloud in a single fast GET request
        try {
            $statusRes = Http::timeout(2)
                ->acceptJson()
                ->get("{$cloudLogisticsUrl}/api/v1/scanner/status");

            if ($statusRes->successful() && is_array($statusRes->json('active_parcels'))) {
                foreach ($statusRes->json('active_parcels') as $cp) {
                    if (is_array($cp) && ! empty($cp['barcode'])) {
                        $cloudParcels[(string) $cp['barcode']] = $cp;
                    }
                }
            }
        } catch (\Throwable $e) {
            return;
        }

        if (empty($cloudParcels)) {
            return;
        }

        $riderUser = $this->resolveRiderUser($request);
        $riderNameLower = strtolower(trim((string) ($riderUser?->name ?? '')));

        foreach ($cloudParcels as $bc => $cp) {
            $cloudStatus = (string) ($cp['status'] ?? '');
            $cloudParcelNum = (string) ($cp['parcel_number'] ?? '');
            $assignedRiderName = trim((string) ($cp['assigned_rider'] ?? ''));

            // Match local parcel strictly by barcode first so independent LM-TRK sequences never collide
            $localParcel = Parcel::where('barcode', (string) $bc)->latest('id')->first();

            // Align local parcel_number with Cloud's parcel_number so both servers share the exact same Track ID
            if ($localParcel && $cloudParcelNum !== '' && $localParcel->parcel_number !== $cloudParcelNum) {
                Parcel::where('parcel_number', $cloudParcelNum)
                    ->where('id', '!=', $localParcel->id)
                    ->get()
                    ->each(function (Parcel $colliding) {
                        $oldNum = 'LM-TRK-OLD-' . $colliding->id;
                        $colliding->updateQuietly(['parcel_number' => $oldNum]);
                        $colliding->shipment?->updateQuietly(['tracking_number' => $oldNum]);
                    });
                $localParcel->updateQuietly(['parcel_number' => $cloudParcelNum]);
                $localParcel->shipment?->updateQuietly(['tracking_number' => $cloudParcelNum]);
            }

            // If local parcel is further ahead than cloud (e.g., updated on local), push local status to cloud
            if ($localParcel) {
                $localRank = $this->statusRank($localParcel->status);
                $cloudRank = $this->statusRank($cloudStatus);
                if ($localRank > $cloudRank && $localRank >= 4) {
                    $this->syncStatusToCloud($request, $localParcel, $localParcel->status, $localParcel->metadata ?? []);
                    continue;
                }
            }

            if (! in_array($cloudStatus, ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'delivered', 'exception'], true)) {
                continue;
            }
            if ($assignedRiderName === '' || strtolower($assignedRiderName) === 'not assigned') {
                continue;
            }

            // Prefer the currently authenticated rider profile if names match
            $targetRiderId = $profile->id;
            if ($riderNameLower !== '' && $riderNameLower !== strtolower($assignedRiderName)) {
                $matchedRider = RiderProfile::whereHas('user', function ($q) use ($assignedRiderName) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($assignedRiderName)]);
                })->first();
                if ($matchedRider) {
                    $targetRiderId = $matchedRider->id;
                }
            }

            if (! $localParcel) {
                try {
                    if ($cloudParcelNum !== '') {
                        Parcel::where('parcel_number', $cloudParcelNum)
                            ->get()
                            ->each(function (Parcel $colliding) {
                                $oldNum = 'LM-TRK-OLD-' . $colliding->id;
                                $colliding->updateQuietly(['parcel_number' => $oldNum]);
                                $colliding->shipment?->updateQuietly(['tracking_number' => $oldNum]);
                            });
                    }
                    $intakeService = app(\App\Services\ParcelIntakeService::class);
                    $localParcel = $intakeService->receive([
                        'barcode' => (string) $bc,
                        'recipient_name' => $cp['recipient_name'] ?? 'Lumora Buyer',
                        'recipient_phone' => $cp['recipient_phone'] ?? '09170000000',
                        'address_line' => $cp['address_line'] ?? 'Laguna',
                        'city' => $cp['city'] ?? 'Pila',
                        'province' => $cp['province'] ?? 'Laguna',
                        'postal_code' => $cp['postal_code'] ?? '4010',
                        'cod_amount' => (float) ($cp['cod_amount'] ?? 0),
                        'items' => $cp['items'] ?? [],
                        'metadata' => [
                            'shop_name' => $cp['shop_name'] ?? 'Lumora Marketplace',
                            'waybill_order_number' => $cp['order_number'] ?? null,
                            'is_cod' => $cp['is_cod'] ?? false,
                            'cod_amount' => (float) ($cp['cod_amount'] ?? 0),
                            'items_summary' => $cp['items_summary'] ?? null,
                            'items' => $cp['items'] ?? [],
                        ],
                    ], $riderUser?->id ?? 1);

                    if ($localParcel && $cloudParcelNum !== '' && $localParcel->parcel_number !== $cloudParcelNum) {
                        $localParcel->updateQuietly(['parcel_number' => $cloudParcelNum]);
                        $localParcel->shipment?->updateQuietly(['tracking_number' => $cloudParcelNum]);
                    }
                } catch (\Throwable $e) {
                    // Ignore creation error
                }
            }

            if ($localParcel) {
                $oldLocalStatus = $localParcel->status;
                $localRank = $this->statusRank($oldLocalStatus);
                $cloudRank = $this->statusRank($cloudStatus);

                $newStatus = $cloudRank > $localRank ? $cloudStatus : $oldLocalStatus;
                if ($cloudStatus === 'exception') {
                    $newStatus = 'exception';
                }

                $localParcel->update([
                    'assigned_rider_id' => $targetRiderId,
                    'status' => $newStatus,
                    'assigned_at' => $localParcel->assigned_at ?? now(),
                    'rider_received_at' => in_array($newStatus, ['rider_received', 'in_transit', 'out_for_delivery'], true) && ! $localParcel->rider_received_at ? now() : $localParcel->rider_received_at,
                    'sort_bin' => (! empty($cp['sort_bin']) && $cp['sort_bin'] !== 'Pending Sorting')
                        ? $cp['sort_bin']
                        : $localParcel->sort_bin,
                ]);

                if ($newStatus !== $oldLocalStatus) {
                    app(\App\Services\ParcelShipmentSyncService::class)->sync($localParcel->fresh());
                }
            }
        }
    }

    /**
     * Extract Return/Refund reason, notes, photo, and video from rider status update request
     * and persist both to public storage and parcel metadata.
     */
    private function extractAndStoreReturnMedia(Request $request, Parcel $parcel, array $metadata, string $dbStatus): array
    {
        $reason = $request->input('failureReason')
            ?? $request->input('failure_reason')
            ?? $request->input('returnReason')
            ?? $request->input('return_reason');

        $notes = $request->input('failureNotes')
            ?? $request->input('failure_notes')
            ?? $request->input('returnNotes')
            ?? $request->input('return_notes');

        if ($reason !== null && trim((string) $reason) !== '') {
            $metadata['failure_reason'] = trim((string) $reason);
            $metadata['return_reason'] = trim((string) $reason);
        }
        if ($notes !== null && trim((string) $notes) !== '') {
            $metadata['failure_notes'] = trim((string) $notes);
            $metadata['return_notes'] = trim((string) $notes);
        }

        if ($dbStatus === 'exception' || $request->boolean('isReturnRefund') || $request->boolean('is_return_refund')) {
            $reportedAt = now();
            $metadata['is_return_refund'] = true;
            $metadata['return_status'] = $metadata['return_status'] ?? 'pending';
            $metadata['return_reported_at'] = $metadata['return_reported_at'] ?? $reportedAt->toIso8601String();
            $metadata['sla_deadline_at'] = $metadata['sla_deadline_at'] ?? $reportedAt->copy()->addHours(24)->toIso8601String();

            $riderName = $this->resolveRiderUser($request)?->name ?? ($parcel->rider?->user?->name ?? 'Assigned Rider');
            $audit = is_array($metadata['return_audit_trail'] ?? null) ? $metadata['return_audit_trail'] : [];
            $audit[] = [
                'actor' => $riderName,
                'role' => 'Rider',
                'action' => 'Submitted Return / Refund',
                'status' => 'pending',
                'note' => trim(($metadata['return_reason'] ?? 'Return / Refund') . (! empty($metadata['return_notes']) ? ' — ' . $metadata['return_notes'] : '')),
                'timestamp' => $reportedAt->toIso8601String(),
            ];
            $metadata['return_audit_trail'] = $audit;
        }

        if ($request->filled('proofUrl') || $request->filled('proof_url')) {
            $metadata['proof_url'] = $request->input('proofUrl') ?? $request->input('proof_url');
        }
        if ($request->filled('proofVideoUrl') || $request->filled('proof_video_url')) {
            $metadata['proof_video_url'] = $request->input('proofVideoUrl') ?? $request->input('proof_video_url');
        }

        // 1. Handle Photo (Multipart file OR Base64 data URI)
        if ($request->hasFile('return_photo') || $request->hasFile('proof_photo')) {
            $file = $request->file('return_photo') ?? $request->file('proof_photo');
            if ($file && $file->isValid()) {
                $path = $file->store('return_media', 'public');
                $metadata['return_photo_path'] = $path;
                $mime = $file->getMimeType() ?: 'image/jpeg';
                $rawBytes = @file_get_contents($file->getRealPath());
                if ($rawBytes !== false && strlen($rawBytes) <= 4 * 1024 * 1024) {
                    $metadata['return_photo_data'] = "data:{$mime};base64," . base64_encode($rawBytes);
                }
            }
        } else {
            $photoBase64 = $request->input('proofPhotoBase64')
                ?? $request->input('proof_photo_base64')
                ?? $request->input('return_photo_base64')
                ?? ((is_string($metadata['proof_url'] ?? null) && str_starts_with($metadata['proof_url'], 'data:image')) ? $metadata['proof_url'] : null);

            if (is_string($photoBase64) && trim($photoBase64) !== '') {
                $cleanPhoto = trim($photoBase64);
                $mime = 'image/jpeg';
                $ext = 'jpg';
                $rawB64 = $cleanPhoto;
                if (preg_match('/^data:(image\/([a-zA-Z0-9+.-]+));base64,(.*)$/s', $cleanPhoto, $m)) {
                    $mime = $m[1];
                    $ext = strtolower($m[2]) === 'png' ? 'png' : 'jpg';
                    $rawB64 = $m[3];
                }
                $decoded = base64_decode($rawB64, true);
                if ($decoded !== false && strlen($decoded) > 0) {
                    $filename = 'return_media/parcel_' . $parcel->id . '_photo_' . time() . '.' . $ext;
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);
                        $metadata['return_photo_path'] = $filename;
                    } catch (\Throwable $e) {
                    }
                    if (strlen($decoded) <= 4 * 1024 * 1024) {
                        $metadata['return_photo_data'] = "data:{$mime};base64," . base64_encode($decoded);
                    }
                }
            }
        }

        // 2. Handle Video (Multipart file OR Base64 data URI)
        if ($request->hasFile('return_video') || $request->hasFile('proof_video')) {
            $vFile = $request->file('return_video') ?? $request->file('proof_video');
            if ($vFile && $vFile->isValid()) {
                $vPath = $vFile->store('return_media', 'public');
                $metadata['return_video_path'] = $vPath;
                $vMime = $vFile->getMimeType() ?: 'video/mp4';
                $rawVBytes = @file_get_contents($vFile->getRealPath());
                if ($rawVBytes !== false && strlen($rawVBytes) <= 10 * 1024 * 1024) {
                    $metadata['return_video_data'] = "data:{$vMime};base64," . base64_encode($rawVBytes);
                }
            }
        } else {
            $videoBase64 = $request->input('proofVideoBase64')
                ?? $request->input('proof_video_base64')
                ?? $request->input('return_video_base64')
                ?? ((is_string($metadata['proof_video_url'] ?? null) && str_starts_with($metadata['proof_video_url'], 'data:video')) ? $metadata['proof_video_url'] : null);

            if (is_string($videoBase64) && trim($videoBase64) !== '') {
                $cleanVideo = trim($videoBase64);
                $vMime = 'video/mp4';
                $vExt = 'mp4';
                $rawVB64 = $cleanVideo;
                if (preg_match('/^data:(video\/([a-zA-Z0-9+.-]+));base64,(.*)$/s', $cleanVideo, $vm)) {
                    $vMime = $vm[1];
                    $vExt = strtolower($vm[2]) === 'webm' ? 'webm' : (strtolower($vm[2]) === 'quicktime' ? 'mov' : 'mp4');
                    $rawVB64 = $vm[3];
                }
                $decodedV = base64_decode($rawVB64, true);
                if ($decodedV !== false && strlen($decodedV) > 0) {
                    $vFilename = 'return_media/parcel_' . $parcel->id . '_video_' . time() . '.' . $vExt;
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->put($vFilename, $decodedV);
                        $metadata['return_video_path'] = $vFilename;
                    } catch (\Throwable $e) {
                    }
                    if (strlen($decodedV) <= 10 * 1024 * 1024) {
                        $metadata['return_video_data'] = "data:{$vMime};base64," . base64_encode($decodedV);
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Numeric progression rank to prevent stale offline queue retries from downgrading parcel status.
     */
    private function statusRank(?string $status): int
    {
        return match ($status) {
            'received' => 1,
            'scanned' => 2,
            'sorted' => 3,
            'assigned' => 4,
            'rider_received', 'in_transit' => 5,
            'out_for_delivery' => 6,
            'delivered' => 7,
            default => 0,
        };
    }

    /**
     * Propagate Rider status updates (assigned, pickup, in_transit, out_for_delivery, delivered, exception)
     * concurrently to both the live Cloud Logistics dashboard and the live Lumora E-Commerce website.
     */
    private function syncStatusToCloud(Request $request, Parcel $parcel, string $dbStatus, array $metadata = []): void
    {
        $code = (string) ($parcel->barcode ?: $parcel->parcel_number);
        $branch = $parcel->branch ?: 'Pila';
        $user = $this->resolveRiderUser($request);
        $riderName = $user?->name ?? ($parcel->rider?->user?->name ?? 'Lumora Rider');
        $host = strtolower((string) $request->getHost());

        \Illuminate\Support\Facades\Cache::forget('rider_cloud_sync_lock');

        $ecommerceBaseUrl = rtrim((string) env('LUMORA_ECOMMERCE_URL', 'https://lumora.party'), '/');
        $cloudLogisticsUrl = rtrim((string) env('LUMORA_CLOUD_LOGISTICS_URL', 'https://logistics.lumora.party'), '/');
        $isCloudHost = str_contains($host, 'lumora.party');

        try {
            Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($ecommerceBaseUrl, $cloudLogisticsUrl, $isCloudHost, $code, $branch, $riderName, $dbStatus, $metadata) {
                $requests = [
                    $pool->as('ecommerce')
                        ->timeout(2)
                        ->acceptJson()
                        ->post("{$ecommerceBaseUrl}/api/logistics/waybill/" . urlencode($code) . '/status', [
                            'status' => $dbStatus,
                            'hub' => $branch . ' Hub',
                            'operator' => $riderName,
                            'note' => "Parcel status updated to {$dbStatus} by rider {$riderName}",
                        ]),
                ];

                if (! $isCloudHost) {
                    $requests[] = $pool->as('cloud_logistics')
                        ->timeout(3)
                        ->acceptJson()
                        ->withHeaders(['X-From-Local-Mirror' => '1'])
                        ->post("{$cloudLogisticsUrl}/api/v1/scanner/scan", [
                            'barcode' => $code,
                            'branch' => $branch,
                            'operator' => $riderName,
                            'force_status' => $dbStatus,
                            'metadata' => $metadata,
                        ]);
                }

                return $requests;
            });
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }
}

