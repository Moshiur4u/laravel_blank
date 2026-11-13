<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'product_name',
        'quantity_size', // নির্বাচিত unit-এর snapshot mass assignment-এ অনুমোদন করে।
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'integer',
        'line_total' => 'float',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDisplayProductNameAttribute(): string
    {
        $name = trim((string) $this->product_name);
        $quantitySize = trim((string) ($this->quantity_size ?: ($this->product?->quantity_size ?? ''))); // item-এর সংরক্ষিত unit-কে product-এর বর্তমান unit-এর আগে প্রাধান্য দেয়।
        $suffix = ' - '.$quantitySize;

        if ($quantitySize === '' || str_ends_with(mb_strtolower($name), mb_strtolower($suffix))) {
            return $name;
        }

        return $name.$suffix;
    }
}
