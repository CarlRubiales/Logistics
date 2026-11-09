<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class CommissionRule extends Model
{
    protected $fillable = [
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
     * Retrieve the active commission rules row, creating the default rule set if none exists.
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
            }

            return $rule;
        } catch (\Throwable $e) {
            return new self(self::defaultAttributes());
        }
    }

    public static function defaultAttributes(): array
    {
        return [
            'base_rate_centavos' => 1800,           // ₱18 per delivered parcel
            'bonus_tier1_rate_pct' => 90,           // On-time rate >= 90%
            'bonus_tier1_centavos' => 15000,        // +₱150
            'bonus_tier2_rate_pct' => 95,           // On-time rate >= 95%
            'bonus_tier2_centavos' => 30000,        // +₱300
            'deduction_per_return_centavos' => 1800, // -₱18 per rider-fault return/refund
            'period_type' => 'semi_monthly',        // 1st–15th and 16th–end of month
        ];
    }

    public function toSnapshotArray(): array
    {
        return [
            'base_rate_centavos' => (int) $this->base_rate_centavos,
            'bonus_tier1_rate_pct' => (int) $this->bonus_tier1_rate_pct,
            'bonus_tier1_centavos' => (int) $this->bonus_tier1_centavos,
            'bonus_tier2_rate_pct' => (int) $this->bonus_tier2_rate_pct,
            'bonus_tier2_centavos' => (int) $this->bonus_tier2_centavos,
            'deduction_per_return_centavos' => (int) $this->deduction_per_return_centavos,
            'period_type' => (string) ($this->period_type ?: 'semi_monthly'),
        ];
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
