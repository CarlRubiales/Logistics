<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationArea extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'province', 'code', 'coverage_rules', 'cities', 'postal_codes', 'is_active'];

    protected function casts(): array
    {
        return ['cities' => 'array', 'postal_codes' => 'array', 'is_active' => 'boolean'];
    }

    public function store() { return $this->belongsTo(Store::class); }
    public function riderAssignments() { return $this->hasMany(RiderAreaAssignment::class); }
    public function riders() { return $this->belongsToMany(RiderProfile::class, 'rider_area_assignments'); }
    public function parcels() { return $this->hasMany(Parcel::class); }
}
