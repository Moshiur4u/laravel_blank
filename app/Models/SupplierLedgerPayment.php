<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedgerPayment extends Model
{
    protected $fillable = [
        'supplierledger_id',
        'payment_date',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * প্রতিটি payment record-কে যে supplier ledger entry-তে জমা হয়েছে তার সঙ্গে যুক্ত করে।
     */
    public function supplierledger(): BelongsTo
    {
        return $this->belongsTo(Supplierledger::class, 'supplierledger_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
