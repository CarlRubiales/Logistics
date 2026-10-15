<?php

namespace App\Http\Controllers;

use App\Models\ErpMessage;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $adminId = $request->user()->id;

        // Load all approved riders with their user, areas, and province
        $riders = RiderProfile::with(['user', 'areas'])->where('registration_status', 'approved')->get();

        // Calculate latest message, unread count, and active deliveries per rider
        $riderSummaries = $riders->map(function ($rider) use ($adminId) {
            $latestMsg = ErpMessage::where(function ($q) use ($rider) {
                $q->where('sender_id', $rider->user_id)
                  ->orWhere('recipient_id', $rider->user_id);
            })->latest()->first();

            $unreadCount = ErpMessage::where('sender_id', $rider->user_id)
                ->where('recipient_id', $adminId)
                ->whereNull('read_at')
                ->count();

            $activeDeliveriesCount = $rider->parcels()
                ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])
                ->count();

            return [
                'rider' => $rider,
                'latest_message' => $latestMsg,
                'unread_count' => $unreadCount,
                'active_deliveries' => $activeDeliveriesCount,
                'last_activity' => $latestMsg?->created_at ?? $rider->updated_at,
            ];
        })->sortByDesc(fn ($item) => [
            $item['unread_count'] > 0 ? 1 : 0,
            $item['last_activity'] ? $item['last_activity']->timestamp : 0,
        ])->values();

        // Determine currently selected rider
        $selectedRiderUserId = $request->input('rider_user_id');
        $selectedRider = null;

        if ($selectedRiderUserId) {
            $selectedRider = $riders->firstWhere('user_id', (int) $selectedRiderUserId);
        }

        if (! $selectedRider && $riderSummaries->isNotEmpty()) {
            $selectedRider = $riderSummaries->first()['rider'];
        }

        $threadMessages = collect();
        $selectedRiderParcels = collect();

        if ($selectedRider) {
            // Mark incoming unread messages from this rider as read
            ErpMessage::where('sender_id', $selectedRider->user_id)
                ->where('recipient_id', $adminId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $threadMessages = ErpMessage::with(['sender', 'recipient', 'parcel'])
                ->where(function ($q) use ($selectedRider) {
                    $q->where('sender_id', $selectedRider->user_id)
                      ->orWhere('recipient_id', $selectedRider->user_id);
                })
                ->when($request->filled('parcel_id'), fn ($q) => $q->where('parcel_id', $request->input('parcel_id')))
                ->orderBy('created_at', 'asc')
                ->get();

            $selectedRiderParcels = $selectedRider->parcels()
                ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])
                ->latest('assigned_at')
                ->get();
        }

        // Return JSON for live auto-polling
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'selected_rider_user_id' => $selectedRider?->user_id,
                'messages' => $threadMessages->map(fn ($m) => [
                    'id' => $m->id,
                    'is_mine' => $m->sender_id === $adminId,
                    'sender_name' => $m->sender?->name ?? 'Courier',
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

        $allActiveParcels = \App\Models\Parcel::whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])->latest()->take(40)->get();

        return view('messages.index', compact(
            'riderSummaries',
            'selectedRider',
            'threadMessages',
            'selectedRiderParcels',
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
