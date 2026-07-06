<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('productName');
            $table->foreignId('product_categorie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->decimal('price');
            $table->string('unit',100);
            $table->string('stock_unit',100)->default(0);
            $table->string('img_url',100)->nullable();

            // $table->foreign('categories_id')->references('id')->on('ProductCategory')->onDelete('cascade');
            // $table->foreign('brands_id')->references('id')->on('brand')->onDelete('cascade');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
