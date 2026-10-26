<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
        'voucherable_type',
        'voucherable_id',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public static function createWithCode(array $attributes): self
    {
        $voucher = static::create([
            ...$attributes,
            'voucher_code' => null,
        ]);

        $prefix = $voucher->voucher_type === 'debit' ? 'DBV' : 'CRV';
        $voucher->update([
            'voucher_code' => $prefix.'-'.Carbon::parse($voucher->voucher_date)->format('Ymd')
                .'-'.str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT),
        ]);

        return $voucher;
    }

    public static function deleteForSource(
        Model $source,
        string $voucherType,
        string $voucherHead,
        string $description,
        ?string $reference,
        array $payments
    ): void {
        static::query()
            ->where('voucherable_type', $source::class)
            ->where('voucherable_id', $source->getKey())
            ->delete();

        foreach ($payments as $payment) {
            $legacyQuery = static::query()
                ->whereNull('voucherable_type')
                ->whereNull('voucherable_id')
                ->where('voucher_type', $voucherType)
                ->where('expense_income_type', $voucherHead)
                ->where('description', $description)
                ->whereDate('voucher_date', $payment['date'])
                ->where('amount', $payment['amount']);

            if ($reference === null) {
                $legacyQuery->whereNull('reference');
            } else {
                $legacyQuery->where('reference', $reference);
            }

            $legacyQuery->orderBy('id')->first()?->delete();
        }
    }

    public function voucherable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Voucher তৈরিকারী account-এর সঙ্গে সম্পর্ক দেয়; নামের snapshot আলাদাভাবে সংরক্ষিত।
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
