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
        $messages = ErpMessage::with(['sender', 'recipient', 'parcel'])->latest()->paginate(30);
        $riders = RiderProfile::with('user')->where('registration_status', 'approved')->get();
        return view('messages.index', compact('messages', 'riders'));
    }

    public function send(Request $request)
    {
        $data = $request->validate(['recipient_id' => ['nullable', 'exists:users,id'], 'message' => ['required', 'string', 'max:2000'], 'parcel_id' => ['nullable', 'exists:parcels,id']]);
        $recipientId = $data['recipient_id'] ?? User::whereIn('role', ['admin', 'operations_manager'])->orderBy('id')->value('id');
        abort_unless($recipientId, 422, 'No operations manager is available to receive messages.');
        ErpMessage::create(['sender_id' => $request->user()->id, 'recipient_id' => $recipientId, 'parcel_id' => $data['parcel_id'] ?? null, 'message' => $data['message']]);
        return back()->with('success', 'Message sent.');
    }

    public function riderIndex(Request $request)
    {
        $messages = ErpMessage::with(['sender', 'recipient', 'parcel'])->where(fn ($q) => $q->where('sender_id', $request->user()->id)->orWhere('recipient_id', $request->user()->id))->latest()->paginate(30);
        return view('riders.messages', compact('messages'));
    }
}
