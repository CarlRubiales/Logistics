<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderAreaAssignment extends Model
{
    protected $fillable = ['rider_profile_id', 'destination_area_id', 'is_primary', 'is_active'];
    protected function casts(): array { return ['is_primary' => 'boolean', 'is_active' => 'boolean']; }
    public function rider() { return $this->belongsTo(RiderProfile::class, 'rider_profile_id'); }
    public function area() { return $this->belongsTo(DestinationArea::class, 'destination_area_id'); }
}
