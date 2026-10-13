<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\SupportBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Buyer/Seller: get (or create) their own conversation with admin.
     */
    public function myConversation()
    {
        $conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::id()],
        );

        $messages = $conversation->messages()->with('sender')->orderBy('created_at')->get();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages->map(fn ($m) => $m->toChatArray()),
        ]);
    }

    /**
     * Send a message into a conversation (buyer, seller, or admin).
     * Accepts an optional attachment (image or PDF) and an optional "report" flag.
     */
    public function send(Request $request, SupportBot $bot)
    {
        $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'body'            => ['nullable', 'string', 'max:2000'],
            'attachment'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:5120'],
            'is_report'       => ['nullable', 'boolean'],
        ]);

        $body = trim((string) $request->input('body'));

        if ($body === '' && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Please type a message or attach a file.'], 422);
        }

        $conversation = Conversation::findOrFail($request->conversation_id);

        // Only the conversation owner or an admin may post into it
        abort_unless(
            Auth::id() === $conversation->user_id || Auth::user()->role === 'admin',
            403
        );

        $isReport = $request->boolean('is_report') && Auth::id() === $conversation->user_id;

        $attachment = [];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachment = [
                'attachment_path'  => $file->store('chat-attachments', 'public'),
                'attachment_name'  => mb_substr($file->getClientOriginalName(), 0, 200),
                'attachment_mime'  => $file->getMimeType(),
            ];
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => Auth::id(),
            'body'            => ($isReport ? '[Product report] ' : '').$body,
        ] + $attachment);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        $botPayload = null;

        // The assistant answers the owner's messages, but never on reports,
        // and steps aside while a human admin is active.
        if (! $isReport && Auth::id() === $conversation->user_id && Auth::user()->role !== 'admin' && $body !== '') {
            $adminActive = $conversation->messages()
                ->where('created_at', '>=', now()->subMinutes(30))
                ->whereHas('sender', fn ($q) => $q->where('role', 'admin'))
                ->exists();

            if (! $adminActive) {
                $replyText = $bot->reply($conversation);

                if ($replyText !== null) {
                    $botMessage = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => null,
                        'body'            => $replyText,
                        'is_bot'          => true,
                        'is_read'         => true,
                        'read_at'         => now(),
                    ]);
                    $conversation->update(['last_message_at' => now()]);

                    $botPayload = $botMessage->toChatArray();
                }
            }
        }

        return response()->json([
            'status'     => 'sent',
            'message_id' => $message->id,
            'message'    => $message->load('sender')->toChatArray(),
            'bot'        => $botPayload,
        ]);
    }

    /**
     * Admin: list every conversation, newest activity first.
     */
    public function adminInbox()
    {
        $conversations = Conversation::with('user')
            ->orderByDesc('last_message_at')
            ->get();

        return view('admin.chat', compact('conversations'));
    }

    /**
     * Admin: view a single conversation's full message history.
     */
    public function adminShow($id)
    {
        $conversation = Conversation::with('user')->findOrFail($id);
        $messages = $conversation->messages()->with('sender')->orderBy('created_at')->get();

        return response()->json([
            'conversation_id' => $conversation->id,
            'user_name'       => $conversation->user->name,
            'messages' => $messages->map(fn ($m) => $m->toChatArray()),
        ]);
    }
}