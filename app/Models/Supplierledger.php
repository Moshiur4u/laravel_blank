<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplierledger extends Model
{
    protected $fillable = [
        'supplier_id',
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'payment',
        'due',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * একটি ledger entry-র সব তারিখভিত্তিক payment-কে নতুন তারিখ আগে রেখে দেখায়।
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierLedgerPayment::class, 'supplierledger_id')
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }
}
