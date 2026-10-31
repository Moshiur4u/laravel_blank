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
