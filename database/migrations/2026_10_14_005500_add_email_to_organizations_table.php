<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organization profile-এ ঐচ্ছিক email address যোগ করে।
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });
    }

    /**
     * rollback হলে organization email column সরায়।
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
