<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * কর্মীদের সব ধরনের খরচ ও payment voucher সংরক্ষণের table তৈরি করে।
     */
    public function up(): void
    {
        Schema::create('employee_payment_vouchers', function (Blueprint $table) {
            $table->id();
            // record তৈরি হওয়ার পর ID দিয়ে voucher number পূরণ হবে বলে শুরুতে nullable।
            $table->string('voucher_no')->nullable()->unique();
            // কর্মী মুছে গেলেও expense record ও employee_name snapshot অক্ষত থাকে।
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_name');
            $table->string('expense_type', 40);
            $table->date('payment_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * rollback হলে employee payment voucher table সরায়।
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payment_vouchers');
    }
};
