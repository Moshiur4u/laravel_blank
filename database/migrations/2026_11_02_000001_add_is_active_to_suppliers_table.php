<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * পুরোনো supplier-সহ সব supplier-কে ডিফল্টে active রেখে status সংরক্ষণ করে।
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('logo');
        });
    }

    /**
     * rollback করলে শুধু supplier status column সরানো হয়।
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
