<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->string('expense_income_type', 255)->change()->constrained('supplier_ledger_payments')->nullOnDelete();;
        });
    }

    public function down(): void
    {
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->string('expense_income_type', 100)->change();
        });
    }
};
