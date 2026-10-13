<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher তৈরিকারী user-এর nullable relation যোগ করে, পুরনো voucher অক্ষত রাখে।
     */
    public function up(): void
    {
        Schema::table('employee_payment_vouchers', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('employee_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * rollback হলে creator relation-সহ কলামটি সরায়।
     */
    public function down(): void
    {
        Schema::table('employee_payment_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
