<?php

namespace App\Http\Controllers;

use App\Models\DestinationArea;
use App\Models\ErpMessage;
use App\Models\Parcel;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $authUserId = $currentUser->id;
        $isMasterAdmin = $currentUser->isMasterAdmin();
        $branch = $currentUser->getBranchName();

        // 1. Gather Branch Contacts
        if ($isMasterAdmin) {
            // Master Admin communicates with Branch accounts
            $branchUsers = User::whereIn('role', ['admin', 'operations_manager', 'warehouse_staff'])
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->orderBy('branch')
                ->get();
        } else {
            // Branch accounts communicate with Master Admin
            $masterAdmin = User::getMasterAdmin();
            $branchUsers = $masterAdmin ? collect([$masterAdmin]) : collect();
        }

        $branchSummaries = $branchUsers->map(function ($bUser) use ($authUserId, $isMasterAdmin) {
            $latestMsg = ErpMessage::where(function ($q) use ($bUser, $authUserId) {
                $q->where(fn ($sq) => $sq->where('sender_id', $bUser->id)->where('recipient_id', $authUserId))
                  ->orWhere(fn ($sq) => $sq->where('sender_id', $authUserId)->where('recipient_id', $bUser->id));
            })->latest()->first();

            $unreadCount = ErpMessage::where('sender_id', $bUser->id)
                ->where('recipient_id', $authUserId)
                ->whereNull('read_at')
                ->count();

            $label = $isMasterAdmin
                ? "{$bUser->branch} Hub"
                : "Central Logistics HQ (Master Admin)";

            $subLabel = $isMasterAdmin
                ? "{$bUser->name} · {$bUser->email}"
                : "{$bUser->name} · {$bUser->email}";

            return [
                'type' => 'branch',
                'user' => $bUser,
                'name' => $label,
                'sub_label' => $subLabel,
                'branch' => $bUser->branch ?: 'Central HQ',
                'latest_message' => $latestMsg,
                'unread_count' => $unreadCount,
                'last_activity' => $latestMsg?->created_at ?? $bUser->updated_at,
            ];
        })->sortByDesc(fn ($item) => [
            $item['unread_count'] > 0 ? 1 : 0,
            $item['last_activity'] ? $item['last_activity']->timestamp : 0,
        ])->values();

        // 2. Gather Courier / Rider Contacts
        $areas = DestinationArea::where('is_active', true)->forBranch($branch)->pluck('id');
        $ridersQuery = RiderProfile::with(['user', 'areas'])->where('registration_status', 'approved');
        if ($branch && $areas->isNotEmpty()) {
            $ridersQuery->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areas));
        }
        $riders = $ridersQuery->get();

        $riderSummaries = $riders->map(function ($rider) use ($authUserId) {
            $latestMsg = ErpMessage::where(function ($q) use ($rider, $authUserId) {
                $q->where(fn ($sq) => $sq->where('sender_id', $rider->user_id)->where('recipient_id', $authUserId))
                  ->orWhere(fn ($sq) => $sq->where('sender_id', $authUserId)->where('recipient_id', $rider->user_id));
            })->latest()->first();

            $unreadCount = ErpMessage::where('sender_id', $rider->user_id)
                ->where('recipient_id', $authUserId)
                ->whereNull('read_at')
                ->count();

            $activeDeliveriesCount = $rider->parcels()
                ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])
                ->count();

            return [
                'type' => 'rider',
                'rider' => $rider,
                'user' => $rider->user,
                'name' => $rider->user->name,
                'sub_label' => ($rider->vehicle_type ?: 'Motorcycle') . ' · ' . ($rider->areas->pluck('name')->join(', ') ?: 'Branch Courier'),
                'branch' => $rider->areas->first()?->branch ?? 'General',
                'latest_message' => $latestMsg,
                'unread_count' => $unreadCount,
                'active_deliveries' => $activeDeliveriesCount,
                'last_activity' => $latestMsg?->created_at ?? $rider->updated_at,
            ];
        })->sortByDesc(fn ($item) => [
            $item['unread_count'] > 0 ? 1 : 0,
            $item['last_activity'] ? $item['last_activity']->timestamp : 0,
        ])->values();

        // 3. Determine Selected Contact
        $selectedUserId = $request->input('contact_user_id')
            ?? $request->input('user_id')
            ?? $request->input('rider_user_id');

        $selectedContact = null;
        $selectedType = 'branch';

        if ($selectedUserId) {
            // Find in branch summaries
            $foundBranch = $branchSummaries->first(fn ($item) => $item['user']->id == $selectedUserId);
            if ($foundBranch) {
                $selectedContact = $foundBranch['user'];
                $selectedType = 'branch';
            } else {
                // Find in rider summaries
                $foundRider = $riderSummaries->first(fn ($item) => $item['user']->id == $selectedUserId);
                if ($foundRider) {
                    $selectedContact = $foundRider['user'];
                    $selectedType = 'rider';
                }
            }
        }

        // If not selected yet, default to first available branch contact (Branch <-> Admin messaging priority!)
        if (! $selectedContact) {
            if ($branchSummaries->isNotEmpty()) {
                $selectedContact = $branchSummaries->first()['user'];
                $selectedType = 'branch';
            } elseif ($riderSummaries->isNotEmpty()) {
                $selectedContact = $riderSummaries->first()['user'];
                $selectedType = 'rider';
            }
        }

        // 4. Load Thread Messages
        $threadMessages = collect();
        $selectedContactParcels = collect();

        if ($selectedContact) {
            // Mark incoming unread messages as read
            ErpMessage::where('sender_id', $selectedContact->id)
                ->where('recipient_id', $authUserId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $threadMessages = ErpMessage::with(['sender', 'recipient', 'parcel'])
                ->where(function ($q) use ($selectedContact, $authUserId) {
                    $q->where(fn ($sq) => $sq->where('sender_id', $selectedContact->id)->where('recipient_id', $authUserId))
                      ->orWhere(fn ($sq) => $sq->where('sender_id', $authUserId)->where('recipient_id', $selectedContact->id));
                })
                ->when($request->filled('parcel_id'), fn ($q) => $q->where('parcel_id', $request->input('parcel_id')))
                ->orderBy('created_at', 'asc')
                ->get();

            // If contact is rider, load parcels assigned to them
            if ($selectedContact->riderProfile) {
                $selectedContactParcels = $selectedContact->riderProfile->parcels()
                    ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])
                    ->latest('assigned_at')
                    ->get();
            } elseif (! empty($selectedContact->branch)) {
                // If contact is a branch account, load active parcels for that branch
                $selectedContactParcels = Parcel::forBranch($selectedContact->branch)
                    ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])
                    ->latest()
                    ->take(20)
                    ->get();
            }
        }

        // 5. Return JSON for live auto-polling
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'selected_contact_id' => $selectedContact?->id,
                'messages' => $threadMessages->map(fn ($m) => [
                    'id' => $m->id,
                    'is_mine' => $m->sender_id === $authUserId,
                    'sender_name' => $m->sender?->name ?? 'User',
                    'message' => $m->message,
                    'created_at_human' => $m->created_at->diffForHumans(),
                    'created_at_time' => $m->created_at->format('g:i A'),
                    'is_read' => $m->read_at !== null,
                    'parcel' => $m->parcel ? [
                        'id' => $m->parcel->id,
                        'number' => $m->parcel->parcel_number,
                        'recipient' => $m->parcel->recipient_name,
                        'city' => $m->parcel->city,
                        'status' => $m->parcel->status,
                    ] : null,
                ]),
                'last_message_id' => $threadMessages->last()?->id,
            ]);
        }

        $allActiveParcels = Parcel::forBranch($branch)
            ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])
            ->latest()
            ->take(30)
            ->get();

        return view('messages.index', compact(
            'isMasterAdmin',
            'branchSummaries',
            'riderSummaries',
            'selectedContact',
            'selectedType',
            'threadMessages',
            'selectedContactParcels',
            'allActiveParcels'
        ));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => ['nullable', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
            'parcel_id' => ['nullable', 'exists:parcels,id'],
        ]);

        $recipientId = $data['recipient_id'] ?? null;
        if (! $recipientId) {
            if ($request->user()->role === 'rider') {
                $recipientId = User::whereIn('role', ['admin', 'operations_manager'])->orderBy('id')->value('id')
                    ?? User::where('role', 'admin')->value('id')
                    ?? 1;
            } elseif (! $request->user()->isMasterAdmin()) {
                // Branch operator sending to Master Admin HQ
                $masterAdmin = User::getMasterAdmin();
                $recipientId = $masterAdmin?->id ?? 1;
            }
        }

        abort_unless($recipientId, 422, 'A message recipient must be specified.');

        $message = ErpMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipientId,
            'parcel_id' => $data['parcel_id'] ?? null,
            'message' => $data['message'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully.',
                'data' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'created_at_human' => $message->created_at->diffForHumans(),
                    'created_at_time' => $message->created_at->format('g:i A'),
                    'is_mine' => true,
                    'parcel' => $message->parcel ? [
                        'id' => $message->parcel->id,
                        'number' => $message->parcel->parcel_number,
                        'recipient' => $message->parcel->recipient_name,
                        'city' => $message->parcel->city,
                        'status' => $message->parcel->status,
                    ] : null,
                ],
            ]);
        }

        return back()->with('success', 'Message sent.');
    }

    public function riderIndex(Request $request)
    {
        $rider = $request->user()->riderProfile;
        abort_unless($rider && $rider->registration_status === 'approved', 403, 'Rider account not approved.');

        $query = ErpMessage::with(['sender', 'recipient', 'parcel'])
            ->where(function ($q) use ($request) {
                $q->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            });

        if ($request->filled('parcel_id')) {
            $query->where('parcel_id', $request->input('parcel_id'));
        }

        $messages = $query->latest()->paginate(30)->withQueryString();

        // Mark incoming messages as read
        ErpMessage::where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $assignedParcels = $rider->parcels()->latest('assigned_at')->take(25)->get();

        return view('riders.messages', compact('messages', 'rider', 'assignedParcels'));
    }
}
