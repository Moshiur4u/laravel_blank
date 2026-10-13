<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Debit/Credit voucher-এর detail ও তৈরিকারীর snapshot সংরক্ষণের table তৈরি করে।
     */
    public function up(): void
    {
        Schema::create('voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_code')->nullable()->unique();
            $table->string('voucher_type', 10)->index();
            $table->string('expense_income_type', 100);
            $table->date('voucher_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();

            $table->index(['voucher_date', 'created_by']);
        });
    }

    /**
     * rollback হলে শুধু general voucher table সরায়।
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_entries');
    }
};
