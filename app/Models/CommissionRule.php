<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class CommissionRule extends Model
{
    protected $fillable = [
        'shipping_fee_centavos',
        'admin_commission_centavos',
        'hub_commission_centavos',
        'base_rate_centavos',
        'bonus_tier1_rate_pct',
        'bonus_tier1_centavos',
        'bonus_tier2_rate_pct',
        'bonus_tier2_centavos',
        'deduction_per_return_centavos',
        'period_type',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee_centavos' => 'integer',
            'admin_commission_centavos' => 'integer',
            'hub_commission_centavos' => 'integer',
            'base_rate_centavos' => 'integer',
            'bonus_tier1_rate_pct' => 'integer',
            'bonus_tier1_centavos' => 'integer',
            'bonus_tier2_rate_pct' => 'integer',
            'bonus_tier2_centavos' => 'integer',
            'deduction_per_return_centavos' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Retrieve the active commission rules row, creating or upgrading to the default rule set if none exists.
     */
    public static function current(): self
    {
        try {
            if (! Schema::hasTable('commission_rules')) {
                return new self(self::defaultAttributes());
            }

            $rule = static::query()->latest('id')->first();
            if (! $rule) {
                $rule = static::query()->create(self::defaultAttributes());
            } elseif ($rule->bonus_tier1_centavos > 0 || $rule->bonus_tier2_centavos > 0 || $rule->base_rate_centavos === 1800 || empty($rule->shipping_fee_centavos) || $rule->period_type !== 'daily') {
                // Enforce commission on delivered parcels only (no bonuses), ₱50 shipping fee split, and daily payout cadence
                $rule->update([
                    'shipping_fee_centavos' => 5000,
                    'admin_commission_centavos' => 1500,
                    'hub_commission_centavos' => 1500,
                    'base_rate_centavos' => 2000,
                    'bonus_tier1_rate_pct' => 0,
                    'bonus_tier1_centavos' => 0,
                    'bonus_tier2_rate_pct' => 0,
                    'bonus_tier2_centavos' => 0,
                    'deduction_per_return_centavos' => 2000,
                    'period_type' => 'daily',
                ]);
            }

            return $rule;
        } catch (\Throwable $e) {
            return new self(self::defaultAttributes());
        }
    }

    public static function defaultAttributes(): array
    {
        return [
            'shipping_fee_centavos' => 5000,        // ₱50 default shipping fee per parcel
            'admin_commission_centavos' => 1500,     // ₱15 Logistics Admin share
            'hub_commission_centavos' => 1500,       // ₱15 Branch Hub share
            'base_rate_centavos' => 2000,            // ₱20 Rider commission per delivered parcel
            'bonus_tier1_rate_pct' => 0,             // Bonuses removed per business rules
            'bonus_tier1_centavos' => 0,
            'bonus_tier2_rate_pct' => 0,
            'bonus_tier2_centavos' => 0,
            'deduction_per_return_centavos' => 2000, // -₱20 per rider-fault return/refund
            'period_type' => 'daily',                // Day by day payout
        ];
    }

    public function toSnapshotArray(): array
    {
        return [
            'shipping_fee_centavos' => (int) ($this->shipping_fee_centavos ?: 5000),
            'admin_commission_centavos' => (int) ($this->admin_commission_centavos ?: 1500),
            'hub_commission_centavos' => (int) ($this->hub_commission_centavos ?: 1500),
            'base_rate_centavos' => (int) ($this->base_rate_centavos ?: 2000),
            'bonus_tier1_rate_pct' => (int) $this->bonus_tier1_rate_pct,
            'bonus_tier1_centavos' => (int) $this->bonus_tier1_centavos,
            'bonus_tier2_rate_pct' => (int) $this->bonus_tier2_rate_pct,
            'bonus_tier2_centavos' => (int) $this->bonus_tier2_centavos,
            'deduction_per_return_centavos' => (int) ($this->deduction_per_return_centavos ?: 2000),
            'period_type' => (string) ($this->period_type ?: 'semi_monthly'),
        ];
    }

    public function getFormattedShippingFeeAttribute(): string
    {
        return self::formatPeso((int) ($this->shipping_fee_centavos ?: 5000));
    }

    public function getFormattedAdminCommissionAttribute(): string
    {
        return self::formatPeso((int) ($this->admin_commission_centavos ?: 1500));
    }

    public function getFormattedHubCommissionAttribute(): string
    {
        return self::formatPeso((int) ($this->hub_commission_centavos ?: 1500));
    }

    /**
     * Format integer centavos as Philippine Peso (e.g. ₱12,816 or ₱18.50 when fractional centavos exist).
     */
    public static function formatPeso(int $centavos, string $prefix = '₱'): string
    {
        $abs = abs($centavos);
        $pesos = $abs / 100;
        $hasCents = ($abs % 100) !== 0;
        $formatted = number_format($pesos, $hasCents ? 2 : 0);

        if ($centavos < 0) {
            return '−' . $prefix . $formatted;
        }

        return $prefix . $formatted;
    }
}
