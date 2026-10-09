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
            'purchase_date',
            'mfg_date',
            'expiry_date',
            'description',
            'img_url'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function productCategory(){
        return $this->belongsTo(ProductCategory::class,'product_categorie_id');
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function shelfDurationDays(): ?int
    {
        if (! $this->mfg_date || ! $this->expiry_date) {
            return null;
        }

        return $this->mfg_date->diffInDays($this->expiry_date);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->lt(today());
    }

    public function isExpiringSoon(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->between(today(), today()->addMonth());
    }

    public function stockQuantity(): int
    {
        return (int) $this->unit;
    }

    public function isOutOfStock(): bool
    {
        return $this->stockQuantity() <= 0;
    }

    public function isLowStock(): bool
    {
        $qty = $this->stockQuantity();

        return $qty > 0 && $qty <= 10;
    }
}
