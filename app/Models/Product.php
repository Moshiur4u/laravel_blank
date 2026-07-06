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
            'unit',
            'stock_unit',
            'img_url'
    ];
    public function productCategory(){
        return $this->belongsTo(ProductCategory::class,'product_categorie_id');
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
