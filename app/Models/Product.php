<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    protected $fillable = [
            'productName',
            'product_categorie_id',
            'brand_id',
            'price',
            'quantity',
            'image'
    ];
    public function productCategory(){
        return $this->belongsTo(ProductCategory::class);
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
