<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherEntry extends Model
{
    public const TYPES = [
        'debit' => 'Debit',
        'credit' => 'Credit',
    ];

    protected $fillable = [
        'voucher_code',
        'voucher_type',
        'expense_income_type',
        'voucher_date',
        'amount',
        'reference',
        'description',
        'created_by',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Voucher তৈরিকারী account-এর সঙ্গে সম্পর্ক দেয়; নামের snapshot আলাদাভাবে সংরক্ষিত।
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
