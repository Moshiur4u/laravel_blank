<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplierledgers()
    {
        return $this->hasMany(Supplierledger::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
