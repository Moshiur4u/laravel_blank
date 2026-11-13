<?php // PHP migration file শুরু করে।

use Illuminate\Database\Migrations\Migration; // Laravel migration base class আনে।
use Illuminate\Database\Schema\Blueprint; // schema পরিবর্তনের table blueprint আনে।
use Illuminate\Support\Facades\Schema; // database schema facade আনে।

return new class extends Migration // anonymous migration class ফেরায়।
{ // migration class শুরু করে।
    public function up(): void // migration চালানোর method ঘোষণা করে।
    { // up method শুরু করে।
        Schema::table('quotation_items', function (Blueprint $table) { // বিদ্যমান quotation_items table পরিবর্তন করে।
            $table->string('quantity_size', 50)->nullable()->after('product_name'); // নির্বাচিত unit-কে quotation item-এ snapshot হিসেবে রাখে।
        }); // quotation_items table-এ নতুন unit column যোগ করে।
    } // migration চালিয়ে quantity_size সংরক্ষণের ব্যবস্থা করে।

    public function down(): void // migration rollback method ঘোষণা করে।
    { // down method শুরু করে।
        Schema::table('quotation_items', function (Blueprint $table) { // rollback-এর জন্য quotation_items table খোলে।
            $table->dropColumn('quantity_size'); // rollback-এ শুধু এই migration-এর column সরায়।
        }); // quotation_items table পরিবর্তন শেষ করে।
    } // migration rollback-এর নিয়ম নির্ধারণ করে।
}; // migration class শেষ করে।
