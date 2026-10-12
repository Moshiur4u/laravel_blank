<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * প্রতিটি supplier payment-এর date ও amount আলাদা রেকর্ডে সংরক্ষণের টেবিল তৈরি করে।
     */
    public function up(): void
    {
        Schema::create('supplier_ledger_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplierledger_id')->constrained('supplierledgers')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * rollback হলে শুধু payment history টেবিলটি সরায়।
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_payments');
    }
};
