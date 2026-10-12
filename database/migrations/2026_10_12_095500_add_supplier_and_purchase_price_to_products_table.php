<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * বিদ্যমান product row অপরিবর্তিত রেখে supplier ও ক্রয়মূল্যের nullable column যোগ করে।
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // nullable রাখায় পুরোনো product এবং supplier ছাড়া product-এর তথ্য অক্ষত থাকে।
            $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 10, 2)->nullable()->after('supplier_id');
        });
    }

    /**
     * শুধু এই migration-এ যোগ করা column সরিয়ে schema-কে আগের অবস্থায় ফেরায়।
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'purchase_price']);
        });
    }
};
