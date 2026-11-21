<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'warranty_cover')) {
                $table->boolean('warranty_cover')->default(false);
            }

            if (! Schema::hasColumn('sale_items', 'warranty_year')) {
                $table->unsignedSmallInteger('warranty_year')->nullable();
            }

            if (! Schema::hasColumn('sale_items', 'warranty_months')) {
                $table->unsignedTinyInteger('warranty_months')->nullable();
            }

            if (! Schema::hasColumn('sale_items', 'serial_mac')) {
                $table->string('serial_mac')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep repaired columns and warranty data intact when rolling back.
    }
};
