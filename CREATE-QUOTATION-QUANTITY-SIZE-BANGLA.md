# Create Quotation: QuantitySize dropdown

Create Quotation form-এর প্রতিটি item-এর **Unit** ঘরে `quantity_sizes` table থেকে dropdown option আসে। নির্বাচিত unit quotation item-এ সংরক্ষিত হয়, তাই quotation document-এও একই unit দেখা যায়। নিচের code excerpt-এর প্রতিটি code line-এ বাংলা ব্যাখ্যা দেওয়া হয়েছে।

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/QuotationController.php`

```php
use App\Models\QuantitySize; // QuantitySize table-এর Eloquent model ব্যবহার করে।

$quantitySizes = QuantitySize::query()->orderBy('name')->get(['name']); // table থেকে নাম অনুযায়ী সাজানো unit-গুলো আনে।

return view('backend.quotation.create', compact('products', 'quantitySizes', 'organization', 'generatedQuotationNo', 'defaultTerms')); // products ও unit options-সহ form view render করে।
```

`store()`-এ unit field যাচাই এবং item-এর সঙ্গে সংরক্ষণ:

```php
'items.*.quantity_size' => 'nullable|string|max:50|exists:quantity_sizes,name', // প্রতিটি item-এর unit table-এ আছে কি না যাচাই করে।

'quantity_size' => $item['quantity_size'] ?? $product?->quantity_size, // নির্বাচিত unit সংরক্ষণ করে; পুরোনো request হলে product unit নেয়।
```

`exists:quantity_sizes,name` থাকার কারণে QuantitySize table-এ নেই এমন নাম form submission-এ গ্রহণ করা হয় না। Unit ফাঁকা থাকলে আগের product unit fallback হিসেবে ব্যবহৃত হয়।

## Migration (Code and Explanation)

নতুন ফাইল: `database/migrations/2026_11_14_000001_add_quantity_size_to_quotation_items_table.php`

```php
<?php // PHP migration file শুরু করে।

use Illuminate\Database\Migrations\Migration; // Laravel migration base class আনে।
use Illuminate\Database\Schema\Blueprint; // schema পরিবর্তনের table blueprint আনে।
use Illuminate\Support\Facades\Schema; // database schema facade আনে।

return new class extends Migration // anonymous migration class ফেরায়।
{ // migration class শুরু করে।
    public function up(): void // migration চালানোর method ঘোষণা করে।
    { // up method শুরু করে।
        Schema::table('quotation_items', function (Blueprint $table) { // বিদ্যমান quotation_items table পরিবর্তন করে।
            $table->string('quantity_size', 50)->nullable()->after('product_name'); // নির্বাচিত unit-এর snapshot রাখার nullable column যোগ করে।
        }); // table পরিবর্তন শেষ করে।
    } // up method শেষ করে।

    public function down(): void // migration rollback method ঘোষণা করে।
    { // down method শুরু করে।
        Schema::table('quotation_items', function (Blueprint $table) { // rollback-এর জন্য quotation_items table খোলে।
            $table->dropColumn('quantity_size'); // এই migration যোগ করা column-টি সরায়।
        }); // table পরিবর্তন শেষ করে।
    } // down method শেষ করে।
}; // migration class শেষ করে।
```

Column-টি nullable, তাই পুরোনো quotation item migration-এর পরেও ব্যবহার করা যায়। Rollback-এ শুধু এই migration-এর column সরানো হয়।

## Route (Code and Explanation)

`routes/web.php`-এ কোনো পরিবর্তন দরকার হয়নি; বিদ্যমান route-ই controller-এর `create()` method চালায়:

```php
Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('permission:sales.create')->name('quotation.create'); // sales.create permission যাচাই করে Create Quotation form খোলে।
```

## Model (Code and Explanation)

ফাইল: `app/Models/QuotationItem.php`

```php
'quantity_size', // নির্বাচিত unit-কে model-এর mass-assignment field-এ অনুমোদন করে।

$quantitySize = trim((string) ($this->quantity_size ?: ($this->product?->quantity_size ?? ''))); // সংরক্ষিত unit-কে product-এর বর্তমান unit-এর আগে প্রাধান্য দেয়।
```

`QuantitySize` model বা table-এর structure পরিবর্তন করা হয়নি। Quotation item-এ unit snapshot হিসেবে থাকে, তাই master তালিকা বা product unit পরে বদলালেও quotation-এর unit অপরিবর্তিত থাকে।

## Blade Template (Code and Explanation)

ফাইল: `resources/views/backend/quotation/create.blade.php`

### আগে থেকে থাকা item-এর Unit dropdown

```blade
@php {{-- পুরোনো input ও product থেকে প্রাথমিক unit নির্ধারণের PHP block শুরু করে। --}}
    $selectedQuantitySize = $oldItem['quantity_size'] ?? $oldProduct?->quantity_size ?? ''; // পুরোনো unit, না থাকলে product unit বেছে নেয়।
@endphp {{-- PHP block শেষ করে। --}}
<td class="item-unit text-center"> {{-- item-এর Unit cell তৈরি করে। --}}
    <select name="items[{{ $index }}][quantity_size]" class="form-select form-select-sm item-unit-select"> {{-- নির্বাচিত unit form-এ পাঠায়। --}}
        <option value="">-- Select Unit --</option> {{-- ফাঁকা unit option দেয়। --}}
        @foreach ($quantitySizes as $quantitySize) {{-- controller থেকে পাওয়া প্রতিটি QuantitySize record ঘোরে। --}}
            <option value="{{ $quantitySize->name }}" {{ $selectedQuantitySize === $quantitySize->name ? 'selected' : '' }}>{{ $quantitySize->name }}</option> {{-- unit name-কে value ও label করে এবং আগের unit select করে। --}}
        @endforeach {{-- unit option-গুলোর loop শেষ করে। --}}
    </select> {{-- Unit dropdown শেষ করে। --}}
</td> {{-- Unit cell শেষ করে। --}}
```

### Product-এর unit এবং নতুন item row-এর option template

```blade
<option value="{{ $product->productName }} — PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}" data-unit="{{ $product->quantity_size }}"> {{-- product নির্বাচন করলে তার quantity_size JavaScript-কে দেয়। --}}

<template id="quotationUnitOptions"> {{-- JavaScript দিয়ে যোগ করা row-গুলোর unit option রাখে। --}}
    <option value="">-- Select Unit --</option> {{-- নতুন row-এ ফাঁকা unit option যোগ করে। --}}
    @foreach ($quantitySizes as $quantitySize) {{-- QuantitySize table থেকে প্রতিটি unit নেয়। --}}
        <option value="{{ $quantitySize->name }}">{{ $quantitySize->name }}</option> {{-- unit name-কে option value ও label করে। --}}
    @endforeach {{-- option তৈরির loop শেষ করে। --}}
</template> {{-- নতুন row-এর template শেষ করে। --}}
```

### JavaScript-এ product নির্বাচন ও নতুন row

```javascript
const unitOptionsTemplate = document.getElementById('quotationUnitOptions'); // server-rendered unit option template খুঁজে নেয়।
const unitSelect = row.querySelector('.item-unit-select'); // বর্তমান item row-এর dropdown ধরে।
unitSelect.value = productMap[val].unit; // product-এর unit dropdown-এ preselect করে, যদি option মেলে।
unitSelect.value = ''; // product text না মিললে unit selection ফাঁকা করে।
newRow.innerHTML = `<select name="items[${rowIndex}][quantity_size]" class="form-select form-select-sm item-unit-select">${unitOptionsTemplate.innerHTML}</select>`; // নতুন row-এ QuantitySize option-সহ unit dropdown তৈরি করে।
```

ফাইল: `resources/views/backend/quotation/show.blade.php`

```blade
<td class="text-center">{{ $item->quantity_size ?: ($item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit')) }}</td> {{-- saved unit আগে দেখায়; পুরোনো item-এ product unit বা stock unit fallback দেয়। --}}
```
