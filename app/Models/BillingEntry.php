<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingEntry extends Model
{
    protected $fillable = [
        'organization_id',
        'billing_month',
        'amount',
        'due_date',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(BillingRecord::class)->orderByDesc('received_at')->orderByDesc('id');
    }

    public function activeRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class)
            ->whereNull('reversed_at')
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    public function paidAmount(): float
    {
        if ($this->relationLoaded('activeRecords')) {
            return (float) $this->activeRecords->sum('amount');
        }

        if ($this->relationLoaded('records')) {
            return (float) $this->records->whereNull('reversed_at')->sum('amount');
        }

        return (float) $this->activeRecords()->sum('amount');
    }

    public function outstandingAmount(): float
    {
        return max(0, round((float) $this->amount - $this->paidAmount(), 2));
    }
}
