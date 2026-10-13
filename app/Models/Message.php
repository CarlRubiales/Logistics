<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'body', 'attachment_path', 'attachment_name', 'attachment_mime',
        'is_bot', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    /**
     * JSON shape shared by the widget, admin inbox and broadcasts.
     */
    public function toChatArray(): array
    {
        return [
            'id'              => $this->id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name ?? 'Lumora Assistant',
            'body'            => $this->body,
            'is_bot'          => (bool) $this->is_bot,
            'attachment_url'  => $this->attachmentUrl(),
            'attachment_name' => $this->attachment_name,
            'attachment_mime' => $this->attachment_mime,
            'created_at'      => $this->created_at->toIso8601String(),
        ];
    }
}
