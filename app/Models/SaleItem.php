<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_invoice_id',
        'product_id',
        'product_name',
        'quantity',
        'purchase_price',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice()
    {
        // sale_invoice_id দিয়ে এই পণ্যটি তার মূল ইনভয়েসের সাথে যুক্ত থাকে।
        return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
    }

    public function product()
    {
        // পণ্য পরে মুছে গেলেও product_name-এ ইনভয়েসের জন্য নামের কপি রাখা হয়।
        return $this->belongsTo(Product::class);
    }

    /**
     * বিক্রির সময়কার snapshot ক্রয়মূল্য ফেরত দেয়, না থাকলে পণ্যের বর্তমান ক্রয়মূল্য নেয়।
     */
    public function getEffectivePurchasePriceAttribute(): float
    {
        if ($this->purchase_price !== null) {
            return (float) $this->purchase_price;
        }

        return (float) ($this->product?->purchase_price ?? 0);
    }

    /**
     * এই একক আইটেমের মোট লাভ (লাইন মোট − মোট ক্রয়মূল্য)।
     * এগ্রিগেট কোয়েরিতে 'profit' সিলেক্ট করা থাকলে সরাসরি সেই মান ফেরত দেয়।
     */
    public function getProfitAttribute(): float
    {
        if (array_key_exists('profit', $this->attributes) && $this->attributes['profit'] !== null) {
            return (float) $this->attributes['profit'];
        }

        $lineTotal = (float) ($this->line_total ?? $this->attributes['line_total'] ?? 0);
        $cost = $this->effective_purchase_price * (float) ($this->quantity ?? $this->attributes['quantity'] ?? 0);

        return round($lineTotal - $cost, 2);
    }
}
