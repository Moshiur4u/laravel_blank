<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // payment history-র typed Eloquent relation।

class CustomerLedger extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_invoice_id',
        'employee_id',
        'received_by',
        'entry_type',
        'payment_method',
        'sale_date',
        'invoice_no',
        'product_name',
        'paid_by',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_type',
        'payment_amount',
        'due_amount',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    // প্রতিটি লেজার এন্ট্রি একটি গ্রাহকের বিক্রি বা পেমেন্টের সাথে যুক্ত।
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // পেমেন্ট গ্রহণকারী এবং বিক্রির কর্মী users টেবিল থেকে আলাদা করে চিহ্নিত হয়।
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // একটি sale ledger row-তে পরবর্তী প্রতিটি আদায় আলাদা payment history হিসেবে থাকে।
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerLedgerPayment::class)->orderByDesc('payment_date')->orderByDesc('id'); // সাম্প্রতিক কিস্তি আগে ফেরায়।
    }
}
