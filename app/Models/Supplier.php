<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo'
    ];

    public function supplierledgers()
    {
        return $this->hasMany(Supplierledger::class);
    }
}
