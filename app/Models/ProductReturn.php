<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductReturn extends Model
{
    protected $fillable = [
        'return_no',
        'sale_invoice_id',
        'sale_item_id',
        'product_id',
        'customer_id',
        'returned_by',
        'invoice_no',
        'product_name',
        'quantity',
        'return_date',
        'return_amount',
        'refund_amount',
        'reason',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function vouchers(): MorphMany
    {
        return $this->morphMany(VoucherEntry::class, 'voucherable');
    }
}
