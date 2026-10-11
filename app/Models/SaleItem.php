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
        'unit_price',
        'line_total',
    ];

    protected $casts = [
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
}
