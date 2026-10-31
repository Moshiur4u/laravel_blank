<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'customer_id',
        'customer_phone',
        'customer_name',
        'customer_address',
        'user_id',
        'invoice_date',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'vat_amount',
        'ait_amount',
        'previous_due',
        'include_previous_due',
        'total_amount',
        'amount_paid',
        'due_amount',
        'payment_method',
        'notes',
        'terms_conditions',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'include_previous_due' => 'boolean',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'ait_amount' => 'decimal:2',
        'previous_due' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function customer()
    {
        // ইনভয়েসের customer_id শুধু তখনই থাকবে যখন গ্রাহক আগেই ছিল বা সেভ করতে সম্মতি দিয়েছেন।
        return $this->belongsTo(Customer::class);
    }

    public function employee()
    {
        // user_id-তে বিক্রির সময় লগ-ইন থাকা কর্মীর পরিচয় সংরক্ষিত থাকে।
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        // একটি ইনভয়েসের প্রতিটি পণ্য আলাদা sale_items সারিতে থাকে।
        return $this->hasMany(SaleItem::class);
    }
}
