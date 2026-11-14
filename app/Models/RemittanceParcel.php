<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemittanceParcel extends Model
{
    protected $fillable = [
        'remittance_id',
        'parcel_id',
        'parcel_number',
        'cod_amount_centavos',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cod_amount_centavos' => 'integer',
        ];
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(Remittance::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function getCodAmountAttribute(): float
    {
        return round(((int) ($this->cod_amount_centavos ?? 0)) / 100, 2);
    }

    public function getFormattedCodAttribute(): string
    {
        return '₱' . number_format($this->cod_amount, 2);
    }
}
