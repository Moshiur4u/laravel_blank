<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_invoices', 'invoice_no')) {
                $table->string('invoice_no')->unique();
            }
            if (! Schema::hasColumn('sale_invoices', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('sale_invoices', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            }
            if (! Schema::hasColumn('sale_invoices', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (! Schema::hasColumn('sale_invoices', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('sale_invoices', 'invoice_date')) {
                $table->dateTime('invoice_date')->useCurrent();
            }
            if (! Schema::hasColumn('sale_invoices', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'previous_due')) {
                $table->decimal('previous_due', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'include_previous_due')) {
                $table->boolean('include_previous_due')->default(false);
            }
            if (! Schema::hasColumn('sale_invoices', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('sale_invoices', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (! Schema::hasColumn('sale_invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'sale_invoice_id')) {
                $table->foreignId('sale_invoice_id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('sale_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('sale_items', 'product_name')) {
                $table->string('product_name');
            }
            if (! Schema::hasColumn('sale_items', 'quantity')) {
                $table->unsignedInteger('quantity');
            }
            if (! Schema::hasColumn('sale_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2);
            }
            if (! Schema::hasColumn('sale_items', 'line_total')) {
                $table->decimal('line_total', 15, 2);
            }
        });

        Schema::table('customer_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_ledgers', 'sale_invoice_id')) {
                $table->foreignId('sale_invoice_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('customer_ledgers', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep invoice and ledger data intact when rolling back this schema upgrade.
    }
};
