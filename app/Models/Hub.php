<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hub extends Model
{
    protected $fillable = ['name', 'branch', 'address', 'city', 'is_active', 'remit_cutoff_time'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function riders(): HasMany
    {
        return $this->hasMany(RiderProfile::class);
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(Remittance::class);
    }
}
