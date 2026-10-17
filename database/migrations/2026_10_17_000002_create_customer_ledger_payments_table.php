<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // customer sale-এর প্রতিটি পরবর্তী payment-এর ইতিহাস রাখার টেবিল তৈরি করে।
    public function up(): void
    {
        Schema::create('customer_ledger_payments', function (Blueprint $table) {
            $table->id(); // payment history-র স্বতন্ত্র primary key।
            $table->foreignId('customer_ledger_id')->constrained('customer_ledgers')->cascadeOnDelete(); // sale মুছলে তার payment history-ও মুছে যায়।
            $table->date('payment_date'); // payment গ্রহণের তারিখ।
            $table->decimal('amount', 15, 2); // এই payment-এর পরিমাণ।
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete(); // user মুছলে payment record রেখে receiver ফাঁকা হয়।
            $table->text('notes')->nullable(); // ঐচ্ছিক payment note।
            $table->timestamps(); // record তৈরির ও পরিবর্তনের সময়।
        });
    }

    // rollback করলে শুধু নতুন payment history table সরানো হয়।
    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_payments');
    }
};
