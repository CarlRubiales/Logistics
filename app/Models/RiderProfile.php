<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'hub_id',
        'phone',
        'vehicle_type',
        'license_number',
        'license_photo_path',
        'or_cr_photo_path',
        'registration_status',
        'availability',
        'approved_at',
        'approved_by',
        'registration_note',
    ];
    protected function casts(): array { return ['approved_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function hub() { return $this->belongsTo(Hub::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function areaAssignments() { return $this->hasMany(RiderAreaAssignment::class); }
    public function areas() { return $this->belongsToMany(DestinationArea::class, 'rider_area_assignments'); }
    public function parcels() { return $this->hasMany(Parcel::class, 'assigned_rider_id'); }
    public function commissions() { return $this->hasMany(CommissionTransaction::class); }

    public function scopeForBranch($query, ?string $branch, $areaIds = null)
    {
        if (! $branch) {
            return $query;
        }

        $areaIds = $areaIds ? collect($areaIds)->filter()->values() : DestinationArea::forBranch($branch)->pluck('id');
        $branchLower = mb_strtolower(trim($branch));

        return $query->where(function ($rq) use ($branch, $branchLower, $areaIds) {
            if ($areaIds->isNotEmpty()) {
                $rq->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds));
            }
            $rq->orWhereHas('user', fn ($uq) => $uq->whereRaw('LOWER(branch) = ?', [$branchLower]))
               ->orWhereHas('hub', function ($hq) use ($branchLower) {
                   $hq->whereRaw('LOWER(branch) = ?', [$branchLower])
                      ->orWhereRaw('LOWER(city) = ?', [$branchLower])
                      ->orWhereRaw('LOWER(name) = ?', [$branchLower . ' hub']);
               });
        });
    }

    public function getLicenseUrlAttribute(): ?string
    {
        if (! $this->license_photo_path) return null;
        if (str_starts_with($this->license_photo_path, 'http')) return $this->license_photo_path;
        return asset('storage/' . $this->license_photo_path);
    }

    public function getOrCrUrlAttribute(): ?string
    {
        if (! $this->or_cr_photo_path) return null;
        if (str_starts_with($this->or_cr_photo_path, 'http')) return $this->or_cr_photo_path;
        return asset('storage/' . $this->or_cr_photo_path);
    }
}
