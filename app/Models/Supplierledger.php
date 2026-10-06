<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplierledger extends Model
{
    protected $fillable = [
        'supplier_id',
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'payment',
        'due',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
