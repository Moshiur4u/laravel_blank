<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_no',
        'quotation_date',
        'customer_name',
        'customer_phone',
        'customer_address',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'vat_percentage',
        'vat_amount',
        'ait_percentage',
        'ait_amount',
        'total_amount',
        'terms_conditions',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'subtotal' => 'float',
        'discount_value' => 'float',
        'discount_amount' => 'float',
        'vat_percentage' => 'float',
        'vat_amount' => 'float',
        'ait_percentage' => 'float',
        'ait_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
