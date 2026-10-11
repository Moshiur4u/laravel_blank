<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'name')) {
                $table->string('name');
            }
            if (! Schema::hasColumn('customers', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('customers', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->default('temporary');
            }
            if (! Schema::hasColumn('customers', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'shop_name')) {
                $table->string('shop_name')->nullable();
            }
            if (! Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code')->nullable()->unique();
            }
            if (! Schema::hasColumn('customers', 'opening_due')) {
                $table->decimal('opening_due', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('customers', 'status')) {
                $table->tinyInteger('status')->default(1);
            }
            if (! Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // পুরনো কাস্টমার ডেটা যাতে rollback-এ মুছে না যায়, তাই এই schema upgrade-এর কলাম রাখা হয়।
    }
};
