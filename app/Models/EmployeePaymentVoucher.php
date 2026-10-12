<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePaymentVoucher extends Model
{
    public const EXPENSE_TYPES = [
        'salary' => 'Salary',
        'salary_advance' => 'Salary Advance',
        'bonus' => 'Bonus',
        'bonus_advance' => 'Bonus Advance',
        'conveyance' => 'Conveyance',
        'sales_incentive' => 'Sales Incentive',
        'lunch_bill' => 'Lunch Bill',
        'mobile_bill' => 'Mobile Bill',
        'other' => 'Other Employee Expense',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank' => 'Bank',
        'nagad' => 'Nagad',
        'bkash' => 'bKash',
        'rocket' => 'Rocket',
        'upay' => 'Upay',
    ];

    protected $fillable = [
        'voucher_no',
        'employee_id',
        'employee_name',
        'expense_type',
        'payment_date',
        'amount',
        'payment_method',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * ভাউচারটি তৈরি হওয়ার সময় নির্বাচিত কর্মীর সঙ্গে সম্পর্ক ফেরায়।
     * কর্মী মুছে গেলেও employee_name snapshot-এ নামটি থেকে যায়।
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
