<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sale_invoices', 'ait_amount')) {
            Schema::table('sale_invoices', function (Blueprint $table): void {
                $table->decimal('ait_amount', 15, 2)->default(0)->after('vat_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_invoices', 'ait_amount')) {
            Schema::table('sale_invoices', function (Blueprint $table): void {
                $table->dropColumn('ait_amount');
            });
        }
    }
};
