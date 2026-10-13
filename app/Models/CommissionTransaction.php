<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionTransaction extends Model
{
    protected $fillable = ['rider_profile_id', 'parcel_id', 'amount', 'status', 'description', 'earned_at', 'paid_at'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'earned_at' => 'datetime', 'paid_at' => 'datetime']; }
    public function rider() { return $this->belongsTo(RiderProfile::class, 'rider_profile_id'); }
    public function parcel() { return $this->belongsTo(Parcel::class); }
}
