<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelScan extends Model
{
    protected $fillable = ['parcel_id', 'user_id', 'scan_type', 'barcode', 'result', 'note', 'scanned_at'];
    protected function casts(): array { return ['scanned_at' => 'datetime']; }
    public function parcel() { return $this->belongsTo(Parcel::class); }
    public function user() { return $this->belongsTo(User::class); }
}
