<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remittance extends Model
{
    public const STATUS_NOT_REMITTED = 'not_remitted';
    public const STATUS_AWAITING_CASHIER = 'awaiting_cashier';
    public const STATUS_REMITTED = 'remitted';
    public const STATUS_SHORT = 'short';
    public const STATUS_OVER = 'over';
    public const STATUS_NOTHING_DUE = 'nothing_due';

    protected $fillable = [
        'rider_profile_id',
        'hub_id',
        'hub_branch',
        'remit_date',
        'status',
        'expected_amount_centavos',
        'declared_amount_centavos',
        'counted_amount_centavos',
        'difference_centavos',
        'submitted_at',
        'confirmed_at',
        'confirmed_by',
        'cashier_note',
        'rider_note',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'remit_date' => 'date',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'expected_amount_centavos' => 'integer',
            'declared_amount_centavos' => 'integer',
            'counted_amount_centavos' => 'integer',
            'difference_centavos' => 'integer',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_profile_id');
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function parcels(): HasMany
    {
        return $this->hasMany(RemittanceParcel::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')->where('auditable_type', self::class);
    }

    public function getExpectedAmountAttribute(): float
    {
        return round($this->expected_amount_centavos / 100, 2);
    }

    public function getDeclaredAmountAttribute(): float
    {
        return round($this->declared_amount_centavos / 100, 2);
    }

    public function getCountedAmountAttribute(): ?float
    {
        return $this->counted_amount_centavos !== null ? round($this->counted_amount_centavos / 100, 2) : null;
    }

    public function getDifferenceAttribute(): float
    {
        return round($this->difference_centavos / 100, 2);
    }

    public function getBalanceDueAttribute(): float
    {
        // If short, the shortage is the balance due
        if ($this->status === self::STATUS_SHORT) {
            return abs($this->difference);
        }
        if ($this->status === self::STATUS_NOT_REMITTED) {
            return $this->expected_amount;
        }
        return 0.00;
    }

    public function getFormattedExpectedAttribute(): string
    {
        return '₱' . number_format($this->expected_amount, 2);
    }

    public function getFormattedDeclaredAttribute(): string
    {
        return '₱' . number_format($this->declared_amount, 2);
    }

    public function getFormattedCountedAttribute(): string
    {
        return $this->counted_amount !== null ? ('₱' . number_format($this->counted_amount, 2)) : '—';
    }

    public function getFormattedDifferenceAttribute(): string
    {
        if ($this->difference == 0) {
            return '₱0.00';
        }
        $prefix = $this->difference > 0 ? '+₱' : '-₱';
        return $prefix . number_format(abs($this->difference), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NOT_REMITTED => 'Not remitted',
            self::STATUS_AWAITING_CASHIER => 'Awaiting cashier',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_SHORT => 'Short',
            self::STATUS_OVER => 'Over',
            self::STATUS_NOTHING_DUE => 'Nothing due',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function scopeForBranch($query, ?string $branch)
    {
        if (! $branch) {
            return $query;
        }
        $branchLower = mb_strtolower(trim($branch));
        return $query->where(function ($q) use ($branchLower) {
            $q->whereRaw('LOWER(hub_branch) = ?', [$branchLower])
              ->orWhereHas('hub', function ($hq) use ($branchLower) {
                  $hq->whereRaw('LOWER(branch) = ?', [$branchLower])
                     ->orWhereRaw('LOWER(name) = ?', [$branchLower])
                     ->orWhereRaw('LOWER(city) = ?', [$branchLower]);
              });
        });
    }

    public function scopeForDate($query, $date)
    {
        if (! $date) {
            return $query;
        }
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        return $query->whereDate('remit_date', $dateStr);
    }
}
