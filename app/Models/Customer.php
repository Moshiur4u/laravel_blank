<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'customer_type',
        'employee_id',
        'shop_name',
        'customer_code',
        'opening_due',
        'status',
        'notes',
    ];

    // স্থায়ী গ্রাহকের দায়িত্বপ্রাপ্ত কর্মী users টেবিলের একজন ব্যবহারকারী।
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // একজন গ্রাহকের সব বিক্রি ও পেমেন্ট customer_ledgers টেবিলে থাকে।
    public function ledgers()
    {
        return $this->hasMany(CustomerLedger::class);
    }
}
