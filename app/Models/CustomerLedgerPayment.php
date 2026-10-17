<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedgerPayment extends Model
{
    protected $fillable = [
        'customer_ledger_id', // যে customer sale-এর due থেকে payment আদায় হয়েছে।
        'payment_date', // টাকা গ্রহণের তারিখ।
        'amount', // এই payment-এ পাওয়া টাকা।
        'received_by', // টাকা গ্রহণকারী ব্যবহারকারীর ID।
        'notes', // payment সম্পর্কে অতিরিক্ত তথ্য।
    ];

    protected $casts = [
        'payment_date' => 'date', // view-তে payment date-কে তারিখ হিসেবে ফরম্যাট করা যায়।
        'amount' => 'decimal:2', // amount দুই ঘর দশমিকসহ সংরক্ষিত হয়।
    ];

    // payment history row-টি যে sale ledger row-র, সেই সম্পর্ক ফেরায়।
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(CustomerLedger::class, 'customer_ledger_id');
    }

    // payment কে গ্রহণ করেছেন তা users টেবিল থেকে ফেরায়।
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
