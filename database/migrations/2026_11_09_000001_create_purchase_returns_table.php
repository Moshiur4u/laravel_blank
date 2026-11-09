<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 50)->unique();
            $table->foreignId('supplierledger_id')->constrained('supplierledgers')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_no', 100);
            $table->string('supplier_name');
            $table->string('product_name');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->date('return_date')->index();
            $table->decimal('return_amount', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['supplierledger_id', 'return_date']);
            $table->index(['supplier_id', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
