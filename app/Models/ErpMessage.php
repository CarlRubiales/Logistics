<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpMessage extends Model
{
    protected $table = 'erp_messages';
    protected $fillable = ['sender_id', 'recipient_id', 'parcel_id', 'message', 'read_at'];
    protected function casts(): array { return ['read_at' => 'datetime']; }
    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
    public function parcel() { return $this->belongsTo(Parcel::class); }
}
