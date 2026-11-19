<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('billing_month');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'billing_month']);
            $table->index(['organization_id', 'due_date']);
        });

        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_entry_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('received_at');
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 150)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['billing_entry_id', 'reversed_at']);
        });

        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->text('alert_message')->nullable();
            $table->boolean('show_marquee_alert')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
        Schema::dropIfExists('billing_records');
        Schema::dropIfExists('billing_entries');
    }
};
