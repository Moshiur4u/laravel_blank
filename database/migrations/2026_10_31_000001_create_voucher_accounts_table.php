<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher account list তৈরি করে এবং voucher-এর সঙ্গে account link যোগ করে।
     */
    public function up(): void
    {
        Schema::create('voucher_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->foreignId('voucher_account_id')
                ->nullable()
                ->after('expense_income_type')
                ->constrained('voucher_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Voucher link সরিয়ে account list table rollback করে।
     */
    public function down(): void
    {
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_account_id');
        });

        Schema::dropIfExists('voucher_accounts');
    }
};
