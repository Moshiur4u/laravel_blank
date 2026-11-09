<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'return_no',
        'supplierledger_id',
        'supplier_id',
        'product_id',
        'returned_by',
        'invoice_no',
        'supplier_name',
        'product_name',
        'quantity',
        'unit_price',
        'return_date',
        'return_amount',
        'refund_amount',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'return_date' => 'date',
        'return_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function supplierLedger(): BelongsTo
    {
        return $this->belongsTo(Supplierledger::class, 'supplierledger_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
