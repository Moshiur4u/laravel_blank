<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('warranty_cover')->default(false);
            $table->unsignedSmallInteger('warranty_year')->nullable();
            $table->string('serial_mac')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['warranty_cover', 'warranty_year', 'serial_mac']);
        });
    }
};
