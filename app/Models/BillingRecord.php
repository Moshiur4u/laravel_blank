<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRecord extends Model
{
    protected $fillable = [
        'billing_entry_id',
        'amount',
        'received_at',
        'payment_method',
        'reference',
        'note',
        'received_by',
        'updated_by',
        'reversed_at',
        'reversed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_at' => 'date',
            'reversed_at' => 'datetime',
        ];
    }

    public function billingEntry(): BelongsTo
    {
        return $this->belongsTo(BillingEntry::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
