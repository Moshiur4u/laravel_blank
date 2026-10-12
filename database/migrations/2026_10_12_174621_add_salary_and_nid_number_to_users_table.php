<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ব্যবহারকারীর টেবিলে কর্মীর বেতন ও NID/Smart Card নম্বর যোগ করে।
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('nid_number', 30)->nullable();
        });
    }

    /**
     * rollback হলে নতুন যোগ করা কর্মী-তথ্যের কলাম দুটি সরায়।
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salary', 'nid_number']);
        });
    }
};
