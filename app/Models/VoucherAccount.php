<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherAccount extends Model
{
    protected $fillable = [
        'name',
    ];

    public function voucherEntries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }
}
