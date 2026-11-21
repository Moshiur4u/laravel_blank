# পণ্যভিত্তিক Warranty Cover — কোড ও বাংলা ব্যাখ্যা

এই নথিতে Warranty Cover যোগ করার পরিবর্তিত কোডের মূল অংশগুলো দেওয়া হয়েছে। প্রতিটি কোড ব্লকের পর লাইন ধরে বাংলা ব্যাখ্যা আছে। ডাটাবেস পরিবর্তন কার্যকর করতে `php artisan migrate` চালাতে হবে।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`

### আইটেম ভ্যালিডেশন

```php
private function saleItemValidationRules(Request $request): array
{
    $rules = [
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|integer|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0|max:9999999999999.99',
        'items.*.warranty_cover' => 'nullable|boolean',
        'items.*.warranty_year' => 'nullable|integer|between:1,100',
        'items.*.serial_mac' => 'nullable|string|max:255',
    ];
    $items = $request->input('items', []);

    foreach (is_array($items) ? $items : [] as $index => $item) {
        if (! is_array($item) || ! filter_var($item['warranty_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            continue;
        }

        $rules["items.{$index}.warranty_year"] = 'required|integer|between:1,100';
        $rules["items.{$index}.serial_mac"] = 'required|string|max:255';
    }

    return $rules;
}
```

**লাইন ধরে ব্যাখ্যা**

- `private function ...` — একই নিয়ম নতুন বিক্রি এবং ইনভয়েস আপডেট—দুই জায়গায় ব্যবহার করার জন্য private helper।
- `$rules = [...]` — পণ্য, পরিমাণ, দাম ও তিনটি warranty field-এর প্রাথমিক নিয়ম।
- `warranty_year` — ওয়ারেন্টির বছর পূর্ণসংখ্যা হতে হবে এবং ১ থেকে ১০০-এর মধ্যে থাকতে হবে।
- `serial_mac` — ডিভাইসের সিরিয়াল/MAC সর্বোচ্চ ২৫৫ অক্ষরের লেখা।
- `$items = ...` — অনুরোধে পাঠানো আইটেমগুলো নেয়; আইটেম না থাকলে ফাঁকা তালিকা ব্যবহার করে।
- `foreach (...)` — প্রতিটি আইটেমের warranty checkbox আলাদাভাবে পরীক্ষা করে।
- `continue` — আইটেমটি সঠিক array না হলে অথবা warranty বন্ধ থাকলে পরের আইটেমে যায়।
- পরের দুই `$rules[...]` লাইন — warranty চালু থাকলে বছর ও সিরিয়াল/MAC-কে বাধ্যতামূলক করে।
- `return $rules` — Laravel validation-এর জন্য সব নিয়ম ফেরত দেয়।

`store()` এবং `updateInvoice()`-এর বিদ্যমান validation-এ এই নিয়ম যুক্ত হয়েছে:

```php
$validated = $request->validate([
    // ঐ endpoint-এর আগের customer/payment/discount validation অপরিবর্তিত।
] + $this->saleItemValidationRules($request));
```

- `$request->validate(...)` — invalid input হলে Laravel validation error দেখায়; ডাটাবেসে বিক্রি লেখা হয় না।
- `+ $this->saleItemValidationRules(...)` — দুই endpoint-এই একই পণ্য ও warranty validation যুক্ত করে।

### নতুন বিক্রির সময় warranty সংরক্ষণ

```php
$warrantyCover = (bool) ($item['warranty_cover'] ?? false);
$warrantyYear = $warrantyCover ? (int) $item['warranty_year'] : null;
$serialMac = $warrantyCover ? trim($item['serial_mac']) : null;
$lineItems[] = compact(
    'product',
    'quantity',
    'unitPrice',
    'lineTotal',
    'warrantyCover',
    'warrantyYear',
    'serialMac'
);
```

- প্রথম লাইন — checkbox-এর মানকে boolean করে।
- দ্বিতীয় লাইন — checkbox চালু হলে বছর রাখে, বন্ধ হলে `null` রাখে।
- তৃতীয় লাইন — checkbox চালু হলে trim করা সিরিয়াল/MAC রাখে, বন্ধ হলে `null` রাখে।
- `compact(...)` — বিদ্যমান বিক্রির হিসাবের সঙ্গে warranty তথ্যকে item data-তে যুক্ত করে।

`SaleItem::create()`-এর item field-গুলোতে নিচের মানগুলোও লেখা হয়:

```php
'warranty_cover' => $lineItem['warrantyCover'],
'warranty_year' => $lineItem['warrantyYear'],
'serial_mac' => $lineItem['serialMac'],
```

- প্রতিটি লাইন একই `sale_items` রেকর্ডে checkbox, warranty বছর এবং সিরিয়াল/MAC সংরক্ষণ করে।

**ফাইল:** `app/Support/SaleInvoiceUpdateService.php`

```php
'warranty_cover' => (bool) ($item['warranty_cover'] ?? false),
'warranty_year' => ! empty($item['warranty_cover']) ? (int) $item['warranty_year'] : null,
'serial_mac' => ! empty($item['warranty_cover']) ? trim($item['serial_mac']) : null,
```

- আপডেট করা পণ্যের checkbox অবস্থা ধরে রাখে।
- checkbox চালু থাকলে বছর ও সিরিয়াল/MAC রাখে; বন্ধ থাকলে আগের warranty তথ্য মুছে `null` করে।

`SaleItem::create()`-এ update service-ও একই তিনটি field লেখে। ফলে ইনভয়েস আপডেটে আইটেম পরিবর্তন হলেও warranty তথ্য সঠিক পণ্যের সঙ্গেই থাকে।

## Migration (Code and Explanation)

**ফাইল:** `database/migrations/2026_11_21_000001_add_warranty_details_to_sale_items_table.php`

```php
Schema::table('sale_items', function (Blueprint $table) {
    $table->boolean('warranty_cover')->default(false);
    $table->unsignedSmallInteger('warranty_year')->nullable();
    $table->string('serial_mac')->nullable();
});
```

- `Schema::table(...)` — বিদ্যমান `sale_items` টেবিল পরিবর্তন করে; পুরোনো migration মুছে বা বদলায় না।
- `warranty_cover` — checkbox-এর অবস্থা রাখে; পুরোনো বিক্রির জন্য ডিফল্ট `false`।
- `warranty_year` — ওয়ারেন্টির বছর রাখে; checkbox বন্ধ থাকলে `NULL`।
- `serial_mac` — পণ্যের সিরিয়াল/MAC রাখে; checkbox বন্ধ থাকলে `NULL`।

```php
Schema::table('sale_items', function (Blueprint $table) {
    $table->dropColumn(['warranty_cover', 'warranty_year', 'serial_mac']);
});
```

- `down()`-এর এই অংশ migration rollback করলে কেবল এই migration-এর যোগ করা তিনটি কলাম সরায়।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php` — নতুন route প্রয়োজন হয়নি; বিদ্যমান route-গুলোই ব্যবহার করা হয়েছে।

```php
Route::post('/sales', [SaleController::class, 'store'])
    ->middleware('permission:sales.create')
    ->name('sales.store');

Route::get('/sales/invoice/{id}/edit', [SaleController::class, 'editInvoice'])
    ->middleware('role_or_permission:admin|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.invoice.edit');

Route::put('/sales/invoice/{id}', [SaleController::class, 'updateInvoice'])
    ->middleware('role_or_permission:admin|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.invoice.update');
```

- `POST /sales` — Create Sale-এ warranty data-সহ item-গুলো `store()`-এ পাঠায়।
- `GET .../edit` — অনুমতিপ্রাপ্ত ব্যবহারকারীকে Update Invoice ফর্ম দেখায়।
- `PUT ...` — একই invoice update করে; warranty তথ্যও item-এর সঙ্গে সংরক্ষণ করে।
- Middleware আগের permission-ই বজায় রাখে; নতুন কোনো প্রবেশাধিকার যোগ করা হয়নি।

## Model (Code and Explanation)

**ফাইল:** `app/Models/SaleItem.php`

```php
protected $fillable = [
    'warranty_cover',
    'warranty_year',
    'serial_mac',
];

protected $casts = [
    'warranty_cover' => 'boolean',
    'warranty_year' => 'integer',
];
```

- `$fillable` — Eloquent `create()` ও `update()`-কে তিনটি warranty field নিরাপদে mass-assign করতে দেয়।
- `warranty_cover` cast — ডাটাবেসের checkbox মানকে PHP boolean হিসেবে ব্যবহার করে।
- `warranty_year` cast — বছরকে integer হিসেবে ব্যবহার করে।
- `serial_mac`-এ বিশেষ cast লাগে না; এটি সাধারণ text হিসেবেই থাকে।

## Blade Template (Code and Explanation)

**ফর্ম ফাইল:** `resources/views/backend/Sales/salesIndex.blade.php`

### Update Invoice-এ আগের warranty তথ্য দেখানো

```php
'warranty_cover' => $item->warranty_cover,
'warranty_year' => $item->warranty_year,
'serial_mac' => $item->serial_mac,
```

- invoice item model থেকে checkbox, বছর ও serial/MAC ফর্মের item data-তে নেয়।
- একই ফর্মে Create Sale এবং Update Invoice চলে; তাই warranty checkbox প্রতিটি পণ্যের নিচে দেখা যায়।
- validation ব্যর্থ হয়ে ফর্মে ফিরলে Blade-এর `old(...)` মান আগের input-ই দেখায়।

### প্রতিটি পণ্যের নিচের checkbox ও ইনপুট

```blade
<tr class="warranty-row">
    <td colspan="7" class="pt-0">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="form-check">
                <input type="checkbox" id="warranty_cover_{{ $index }}"
                    name="items[{{ $index }}][warranty_cover]"
                    class="form-check-input warranty-cover-toggle" value="1"
                    {{ $warrantyCoverEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold"
                    for="warranty_cover_{{ $index }}">warrantyCover</label>
            </div>
            <div class="warranty-fields {{ $warrantyCoverEnabled ? '' : 'd-none' }}">
                <input type="number" id="warranty_year_{{ $index }}"
                    name="items[{{ $index }}][warranty_year]"
                    class="form-control warranty-year-input" min="1" max="100"
                    placeholder="Warranty Year"
                    value="{{ old("items.$index.warranty_year", $oldItem['warranty_year'] ?? '') }}"
                    {{ $warrantyCoverEnabled ? 'required' : 'disabled' }}>
                <input type="text" id="serial_mac_{{ $index }}"
                    name="items[{{ $index }}][serial_mac]"
                    class="form-control serial-mac-input" maxlength="255"
                    placeholder="Device Serial / MAC"
                    value="{{ old("items.$index.serial_mac", $oldItem['serial_mac'] ?? '') }}"
                    {{ $warrantyCoverEnabled ? 'required' : 'disabled' }}>
            </div>
        </div>
    </td>
</tr>
```

- `warranty-row` — পণ্যের মূল row-এর ঠিক পরের row; তাই প্রতিটি পণ্যের জন্য checkbox থাকে।
- `items[{{ $index }}]...` — warranty মানকে নির্দিষ্ট পণ্য/আইটেম index-এর সঙ্গে যুক্ত করে।
- `warrantyCover` label — ব্যবহারকারীর চাওয়া checkbox-এর নাম।
- `$warrantyCoverEnabled` — পুরোনো input বা invoice item-এর checkbox অবস্থা নির্ধারণ করে।
- `warranty-fields` — checkbox বন্ধ থাকলে ইনপুট দুটিকে লুকিয়ে রাখে।
- `warranty_year` — ১–১০০ পূর্ণসংখ্যার Warranty Year ইনপুট।
- `serial_mac` — সর্বোচ্চ ২৫৫ অক্ষরের Device Serial / MAC ইনপুট।
- `required` ও `disabled` — checkbox চালু থাকলে তথ্য বাধ্যতামূলক; বন্ধ থাকলে ফর্মে পাঠানো হয় না।

### নতুন product row-এর জন্য field index তৈরি

```javascript
const first = itemsBody.querySelector('.sale-item-row');
const row = first.cloneNode(true);
const warrantyRow = first.nextElementSibling.cloneNode(true);
const warrantyToggle = warrantyRow.querySelector('.warranty-cover-toggle');
warrantyToggle.name = `items[${rowIndex}][warranty_cover]`;
warrantyToggle.id = `warranty_cover_${rowIndex}`;
warrantyToggle.checked = false;
warrantyRow.querySelector('label[for^="warranty_cover_"]').htmlFor = warrantyToggle.id;
warrantyRow.querySelector('.warranty-year-input').name = `items[${rowIndex}][warranty_year]`;
warrantyRow.querySelector('.warranty-year-input').id = `warranty_year_${rowIndex}`;
warrantyRow.querySelector('.warranty-year-input').value = '';
warrantyRow.querySelector('.warranty-year-input').disabled = true;
warrantyRow.querySelector('.warranty-year-input').required = false;
warrantyRow.querySelector('.serial-mac-input').name = `items[${rowIndex}][serial_mac]`;
warrantyRow.querySelector('.serial-mac-input').id = `serial_mac_${rowIndex}`;
warrantyRow.querySelector('.serial-mac-input').value = '';
warrantyRow.querySelector('.serial-mac-input').disabled = true;
warrantyRow.querySelector('.serial-mac-input').required = false;
itemsBody.appendChild(row);
itemsBody.appendChild(warrantyRow);
rowIndex++;
```

- প্রথম তিনটি লাইন — মূল পণ্য row ও তার পরের warranty row কপি করে।
- `warrantyToggle` — নতুন checkbox নির্বাচন করে।
- `name` ও `id` লাইনগুলো — `rowIndex` দিয়ে নতুন আইটেমের জন্য অনন্য form field ও HTML ID বানায়।
- `.checked = false` — নতুন পণ্যে warranty প্রাথমিকভাবে বন্ধ রাখে।
- label-এর `htmlFor` — নতুন checkbox label-কে সঠিক checkbox-এর সঙ্গে যুক্ত করে।
- warranty year ও serial/MAC-এর `name` — নতুন পণ্যের index-এর মধ্যে তাদের মান পাঠায়।
- `id` লাইনগুলো — নতুন ইনপুটের label association-এর জন্য আলাদা ID দেয়।
- `.value = ''` — কপি হওয়া আগের পণ্যের warranty মান নতুন পণ্যে বহন হতে দেয় না।
- `.disabled = true` — warranty বন্ধ থাকা অবস্থায় নতুন warranty field submit হতে দেয় না।
- `.required = false` — বন্ধ অবস্থায় ইনপুটগুলোকে বাধ্যতামূলক করে না।
- দুই `appendChild` লাইন — পণ্য row-এর পরেই warranty row দেখায়।
- `rowIndex++` — পরের পণ্যের জন্য নতুন index প্রস্তুত করে।

### Checkbox পরিবর্তনে ইনপুট দেখানো/লুকানো

```javascript
if (event.target.matches('.warranty-cover-toggle')) {
    const warrantyFields = event.target.closest('.warranty-row').querySelector('.warranty-fields');
    warrantyFields.classList.toggle('d-none', !event.target.checked);
    warrantyFields.querySelectorAll('input').forEach((input) => {
        input.disabled = !event.target.checked;
        input.required = event.target.checked;
        if (!event.target.checked) input.value = '';
    });
}
```

- checkbox-এর পরিবর্তন শনাক্ত করে সংশ্লিষ্ট পণ্যের warranty input নেয়।
- checkbox চালু হলে ইনপুট দেখায়, সক্রিয় ও required করে।
- checkbox বন্ধ হলে ইনপুট লুকায়, disabled করে এবং আগের মান মুছে দেয়।

**ইনভয়েস ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

```blade
@if ($item->warranty_cover)
    <div class="mt-1 small text-muted">
        Warranty: {{ $item->warranty_year }} Year(s)<br>
        Serial/MAC: {{ $item->serial_mac }}
    </div>
@endif
```

- warranty চালু থাকা পণ্যের নিচে Warranty Year এবং Serial/MAC প্রিন্ট/প্রদর্শন করে।
- Blade-এর `{{ ... }}` output escaping সক্রিয় থাকে।

## পরীক্ষা

`tests/Feature/SaleItemWarrantyTest.php`-এ warranty তথ্য সংরক্ষণ, invoice-এ প্রদর্শন, update/clear এবং checkbox টিক করা অবস্থায় বাধ্যতামূলক field validation যাচাইয়ের test যোগ করা হয়েছে।
