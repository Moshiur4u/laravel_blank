<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * প্রতিষ্ঠানের একক profile ও report/invoice-এ ব্যবহৃত তথ্য সংরক্ষণের table তৈরি করে।
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * rollback হলে শুধু organization profile table সরায়।
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
