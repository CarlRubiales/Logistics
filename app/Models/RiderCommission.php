<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderCommission extends Model
{
    protected $fillable = [
        'rider_profile_id',
        'hub_branch',
        'period_key',
        'period_label',
        'period_start',
        'period_end',
        'delivered_count',
        'on_time_count',
        'on_time_rate_bp',
        'return_fault_count',
        'return_parcel_ids',
        'base_centavos',
        'bonus_centavos',
        'deductions_centavos',
        'net_centavos',
        'rules_snapshot',
        'status',
        'is_flagged',
        'flagged_note',
        'flagged_at',
        'flagged_by',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'delivered_count' => 'integer',
            'on_time_count' => 'integer',
            'on_time_rate_bp' => 'integer',
            'return_fault_count' => 'integer',
            'return_parcel_ids' => 'array',
            'base_centavos' => 'integer',
            'bonus_centavos' => 'integer',
            'deductions_centavos' => 'integer',
            'net_centavos' => 'integer',
            'rules_snapshot' => 'array',
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_profile_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function flagger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    public function getOnTimeRatePercentAttribute(): float
    {
        return round(((int) $this->on_time_rate_bp) / 100, 1);
    }

    public function getFormattedBaseAttribute(): string
    {
        return CommissionRule::formatPeso((int) $this->base_centavos);
    }

    public function getFormattedBonusAttribute(): string
    {
        $val = (int) $this->bonus_centavos;
        return $val > 0 ? '+' . CommissionRule::formatPeso($val) : CommissionRule::formatPeso(0);
    }

    public function getFormattedDeductionsAttribute(): string
    {
        $val = (int) $this->deductions_centavos;
        return $val > 0 ? '−' . CommissionRule::formatPeso($val) : CommissionRule::formatPeso(0);
    }

    public function getFormattedNetAttribute(): string
    {
        return CommissionRule::formatPeso((int) $this->net_centavos);
    }

    public function scopeForHub($query, ?string $hub)
    {
        if (empty($hub)) {
            return $query;
        }
        $hubLower = strtolower(trim($hub));
        return $query->whereRaw('LOWER(hub_branch) = ?', [$hubLower]);
    }
}
