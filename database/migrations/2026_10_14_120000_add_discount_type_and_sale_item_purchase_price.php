<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. sale_invoices টেবিলে discount_type ও discount_value যোগ করা।
     * 2. sale_items টেবিলে বিক্রির সময়কার purchase_price snapshot রাখার column যোগ করা।
     */
    public function up(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_invoices', 'discount_type')) {
                // ডিসকাউন্ট টাইপ: 'amount' (সরাসরি টাকা) অথবা 'percentage' (শতাংশ)
                $table->string('discount_type', 20)->default('amount')->after('subtotal');
            }
            if (! Schema::hasColumn('sale_invoices', 'discount_value')) {
                // ব্যবহারকারীর দেওয়া ইনপুট মান (টাকা বা শতাংশের হার)
                $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'purchase_price')) {
                // বিক্রির সময় পণ্যের ক্রয়মূল্য snapshot আকারে রাখা হয় যাতে ভবিষ্যতে পণ্যের ক্রয়মূল্য পরিবর্তন হলেও আগের বিক্রির লাভ সঠিক থাকে
                $table->decimal('purchase_price', 15, 2)->nullable()->after('quantity');
            }
        });

        // পূর্ববর্তী sale_items-এর জন্য products টেবিলের purchase_price দিয়ে ব্যাকফিল করা হয়
        try {
            DB::statement("
                UPDATE sale_items si
                INNER JOIN products p ON si.product_id = p.id
                SET si.purchase_price = p.purchase_price
                WHERE si.purchase_price IS NULL AND p.purchase_price IS NOT NULL
            ");
        } catch (\Throwable $e) {
            // যদি কোনো কারণে ব্যাকফিল কোয়েরি ব্যর্থ হয় তবে মাইগ্রেশন থামবে না
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sale_invoices', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
            if (Schema::hasColumn('sale_invoices', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });
    }
};
