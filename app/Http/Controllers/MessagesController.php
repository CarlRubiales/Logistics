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
        $query = ErpMessage::with(['sender', 'recipient', 'parcel'])->latest();

        if ($request->filled('rider_user_id')) {
            $riderUserId = $request->input('rider_user_id');
            $query->where(function ($q) use ($riderUserId) {
                $q->where('sender_id', $riderUserId)
                    ->orWhere('recipient_id', $riderUserId);
            });
        }

        if ($request->filled('parcel_id')) {
            $query->where('parcel_id', $request->input('parcel_id'));
        }

        $messages = $query->paginate(30)->withQueryString();
        $riders = RiderProfile::with('user')->where('registration_status', 'approved')->get();
        $activeParcels = \App\Models\Parcel::whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery', 'exception'])->latest()->take(30)->get();

        return view('messages.index', compact('messages', 'riders', 'activeParcels'));
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

        ErpMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipientId,
            'parcel_id' => $data['parcel_id'] ?? null,
            'message' => $data['message'],
        ]);

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
