# কোটেশন ফর্মে গ্রাহকের তথ্য স্বয়ংক্রিয় পূরণ

## Controller (code and explanation)----

**ফাইল:** `app/Http/Controllers/QuotationController.php`

```php
public function customerLookup(Request $request)
{
    $validated = $request->validate([
        'name' => 'required_without:phone|nullable|string|max:255',
        'phone' => 'required_without:name|nullable|string|max:50',
    ]);

    $query = Customer::query();

    if (! empty($validated['phone'])) {
        $query->where('phone', trim($validated['phone']));
    } else {
        $query->whereRaw('LOWER(name) = LOWER(?)', [trim($validated['name'])]);
    }

    $matches = $query->limit(2)->get(['name', 'phone', 'address']);
    if ($matches->count() !== 1) {
        return response()->json([
            'found' => false,
            'ambiguous' => $matches->count() > 1,
        ]);
    }

    return response()->json([
        'found' => true,
        'customer' => $matches->first(),
    ]);
}
```

নাম বা ফোন দিয়ে সংরক্ষিত গ্রাহক খোঁজে। একাধিক গ্রাহকের নাম/ফোন মিলে গেলে ভুল তথ্য পূরণ না করে ফলাফলকে ambiguous হিসেবে ফেরত দেয়।

**রাউট:** `routes/web.php`-এ `quotation.customer.lookup` GET রাউটটি কোটেশন তৈরির অনুমতিতে সুরক্ষিত করা হয়েছে।

```php
Route::get('/quotations/customer-lookup', [QuotationController::class, 'customerLookup'])
    ->middleware('permission:sales.create')
    ->name('quotation.customer.lookup');
```

## Migration (code and explanation)----

এই পরিবর্তনের জন্য কোনো migration প্রয়োজন হয়নি।

## Model (code and explanation)----

এই পরিবর্তনের জন্য কোনো model পরিবর্তন প্রয়োজন হয়নি; বিদ্যমান `Customer` model ব্যবহার করা হয়েছে।

## Blade (code and explanation)----

**ফাইল:** `resources/views/backend/quotation/create.blade.php`

```blade
<small id="customerLookupStatus" class="text-muted" aria-live="polite"></small>
```

```javascript
function scheduleCustomerLookup(input, field) {
    clearPreviousCustomerAutofill(input);
    customerLookupRequest++;
    const requestId = customerLookupRequest;
    const value = input.value.trim();

    window.clearTimeout(customerLookupTimer);
    customerLookupStatus.textContent = '';
    if (value.length < 2) {
        return;
    }

    customerLookupStatus.textContent = 'Searching saved customers...';
    customerLookupTimer = window.setTimeout(async function () {
        const url = new URL(@json(route('quotation.customer.lookup')), window.location.origin);
        url.searchParams.set(field, value);

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) {
                throw new Error('Customer lookup failed with status ' + response.status);
            }

            const result = await response.json();
            if (requestId !== customerLookupRequest || input.value.trim() !== value) {
                return;
            }

            if (!result.found) {
                customerLookupStatus.textContent = result.ambiguous
                    ? 'More than one customer matches. Search by phone number instead.'
                    : 'No saved customer found.';
                return;
            }

            customerNameInput.value = result.customer.name || '';
            customerPhoneInput.value = result.customer.phone || '';
            customerAddressInput.value = result.customer.address || '';
            autoFilledCustomer = {
                name: customerNameInput.value,
                phone: customerPhoneInput.value,
                address: customerAddressInput.value
            };
            customerLookupStatus.textContent = 'Saved customer details filled in.';
        } catch (error) {
            if (requestId === customerLookupRequest) {
                customerLookupStatus.textContent = 'Could not load customer details. Please try again.';
            }
            console.error(error);
        }
    }, 350);
}

customerNameInput.addEventListener('input', function () {
    scheduleCustomerLookup(customerNameInput, 'name');
});
customerPhoneInput.addEventListener('input', function () {
    scheduleCustomerLookup(customerPhoneInput, 'phone');
});
```

নাম অথবা ফোন ইনপুটে টাইপের পর ৩৫০ মিলিসেকেন্ড অপেক্ষা করে lookup করা হয়। একটি সঠিক ও একক মিল পাওয়া গেলে Customer Information অংশের নাম, ফোন ও ঠিকানা পূরণ হয়। নতুন ইনপুট শুরু করলে আগের lookup থেকে স্বয়ংক্রিয়ভাবে বসানো বাকি তথ্য সরানো হয়, কিন্তু হাতে লেখা তথ্য অক্ষত থাকে। খোঁজ চলা, ফল না পাওয়া, একাধিক ফল এবং নেটওয়ার্ক ত্রুটির জন্য status বার্তা দেখানো হয়।

## কোটেশনে AIT গণনা ও প্রদর্শন

### Controller (code and explanation)----

**ফাইল:** `app/Http/Controllers/QuotationController.php`

```php
'vat_percentage' => 'nullable|numeric|min:0|max:100',
'ait_percentage' => 'nullable|numeric|min:0|max:100',
```

```php
$aitPercentage = (float)($validated['ait_percentage'] ?? 0);
$aitAmount = round(($taxableSubtotal * $aitPercentage) / 100, 2);
$grandTotal = round($taxableSubtotal + $vatAmount + $aitAmount, 2);
```

AIT rate validate করে discount বাদ দেওয়া taxable subtotal-এর উপর AIT হিসাব করা হয়। AIT amount-সহ চূড়ান্ত total সার্ভারেই তৈরি ও সংরক্ষণ করা হয়।

```php
'ait_percentage' => $aitPercentage,
'ait_amount' => $aitAmount,
```

### Migration (code and explanation)----

**ফাইল:** `database/migrations/2026_10_31_000001_add_ait_fields_to_quotations_table.php`

```php
$table->decimal('ait_percentage', 5, 2)->default(0)->after('vat_amount');
$table->decimal('ait_amount', 12, 2)->default(0)->after('ait_percentage');
```

আগের কোটেশনগুলোতে AIT শূন্য রাখতে default `0` সহ rate এবং calculated amount কলাম যোগ করা হয়েছে।

### Model (code and explanation)----

**ফাইল:** `app/Models/Quotation.php`

```php
'ait_percentage',
'ait_amount',
```

```php
'ait_percentage' => 'float',
'ait_amount' => 'float',
```

Quotation model-এ AIT fields mass assignment এবং numeric cast-এর জন্য অন্তর্ভুক্ত।

### Blade (code and explanation)----

**ফাইল:** `resources/views/backend/quotation/create.blade.php`

AIT rate ইনপুট, live amount এবং grand total VAT-এর মতো একইভাবে update হয়; discount বাদ দেওয়ার পর VAT ও AIT আলাদাভাবে যোগ করা হয়।

```blade
<span class="input-group-text bg-light">AIT Rate (%)</span>
<input type="number" step="0.01" min="0" max="100" name="ait_percentage" id="ait_percentage"
    class="form-control text-end" value="{{ old('ait_percentage', 0) }}" placeholder="0">
```

```javascript
const aitPercent = parseFloat(aitPercentageInput.value) || 0;
const aitAmount = Math.round((taxableAmount * aitPercent / 100) * 100) / 100;
const grandTotal = Math.round((taxableAmount + vatAmount + aitAmount) * 100) / 100;
```

**ফাইল:** `resources/views/backend/quotation/show.blade.php`

সংরক্ষিত AIT amount শূন্যের বেশি হলে quotation document-এ AIT rate ও amount দেখানো হয়।
