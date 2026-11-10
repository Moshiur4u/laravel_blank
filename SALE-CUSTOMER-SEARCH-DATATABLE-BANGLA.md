# Sale Invoice Customer Search ও DataTable Info পরিবর্তন

এই পরিবর্তনে বিক্রয় তৈরির ফর্মে গ্রাহকের নাম বা ফোন দিয়ে খোঁজা যায়। টাইপের সঙ্গে মিল থাকা গ্রাহকের নাম ও ফোনের suggestion আসে; একটি নির্বাচন করলে গ্রাহকের ledger-সহ আগের তথ্য লোড হয়। DataTable-এ “Showing … entries” তথ্যটি এখন table-এর footer-এ pagination-এর পাশে দেখানো হয়।

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/SaleController.php`, `customerByPhone()` method।

```php
if ($request->filled('search')) {
    $validated = $request->validate([
        'search' => 'required|string|max:100',
    ]);
    $search = trim($validated['search']);

    $customers = Customer::query()
        ->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%');
        })
        ->orderBy('name')
        ->limit(10)
        ->get(['id', 'name', 'phone']);

    return response()->json([
        'suggestions' => $customers,
    ]);
}
```

`search` পাঠালে controller নাম ও ফোন—দুই কলামেই মিল খোঁজে এবং সর্বোচ্চ ১০টি suggestion JSON-এ ফেরত দেয়। পুরোনো `phone` দিয়ে নির্দিষ্ট গ্রাহক, বকেয়া ও ledger খোঁজার অংশ অপরিবর্তিত আছে।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান customer টেবিলের `name` ও `phone` কলাম ব্যবহার করা হয়েছে।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি। আগের `sales.customer.lookup` route-এ suggestion-এর জন্য `search` এবং নির্দিষ্ট lookup-এর জন্য `phone` parameter ব্যবহৃত হয়েছে।

```php
Route::get('/sales/customer-lookup', [SaleController::class, 'customerByPhone'])
    ->middleware('permission:sales.create|blank-invoice')
    ->name('sales.customer.lookup');
```

## Model (Code and Explanation)

কোনো model পরিবর্তন করা হয়নি। বিদ্যমান `Customer` model-এর query ব্যবহার করে গ্রাহক খোঁজা হয়েছে।

## Blade Template (Code and Explanation)

### `resources/views/backend/Sales/salesIndex.blade.php`

Customer input-এ datalist যুক্ত করা হয়েছে:

```blade
<input type="text" name="customer_phone" id="customer_phone"
    class="form-control" value="{{ old('customer_phone') }}"
    list="customerSuggestions" placeholder="Type customer name or phone"
    autocomplete="off" required>
<datalist id="customerSuggestions"></datalist>
```

নিচের JavaScript typed query দিয়ে আগের lookup route-এ request করে, পাওয়া customer-এর ফোন option-এর value এবং নাম-ফোন label হিসেবে দেখায়। ফোন সরাসরি মিলে গেলে, অথবা একটিমাত্র পূর্ণ নাম মিলে গেলে পুরোনো detail lookup চালায়।

```javascript
async function suggestCustomers(resolveExactMatch = false) {
    const search = customerPhone.value.trim();
    if (!search) return false;

    const requestSequence = ++customerSearchSequence;
    try {
        const url = new URL(@json(route('sales.customer.lookup')), window.location.origin);
        url.searchParams.set('search', search);
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) {
            throw new Error('Customer suggestions could not be loaded. Please try again.');
        }

        const result = await response.json();
        if (requestSequence !== customerSearchSequence || customerPhone.value.trim() !== search) {
            return false;
        }

        const suggestions = document.getElementById('customerSuggestions');
        suggestions.replaceChildren();
        result.suggestions.forEach((customer) => {
            const option = document.createElement('option');
            option.value = customer.phone;
            option.label = `${customer.name} — ${customer.phone}`;
            suggestions.appendChild(option);
        });

        const exactPhone = result.suggestions.find((customer) => customer.phone === search);
        const exactNameMatches = result.suggestions.filter((customer) =>
            customer.name.trim().toLocaleLowerCase() === search.toLocaleLowerCase()
        );
        const exactMatch = exactPhone || (exactNameMatches.length === 1 ? exactNameMatches[0] : null);

        if (exactMatch && (resolveExactMatch || exactPhone)) {
            customerPhone.value = exactMatch.phone;
            return lookupCustomer();
        }

        lookupMessage.textContent = result.suggestions.length ?
            'Select a customer suggestion to load their name and ledger.' :
            'No matching saved customer. Enter a phone number to continue.';
        return false;
    } catch (error) {
        if (requestSequence === customerSearchSequence) {
            lookupMessage.textContent = error.message;
        }
        return false;
    }
}

customerPhone.addEventListener('input', function() {
    customerFound = false;
    ledgerPanel.classList.add('d-none');
    newCustomerDetails.classList.add('d-none');
    customerName.value = '';
    window.clearTimeout(suggestionTimer);
    suggestionTimer = window.setTimeout(suggestCustomers, 250);
});
```

নাম দিয়ে একাধিক গ্রাহক মিলে গেলে তালিকা থেকে সঠিক গ্রাহক নির্বাচন করতে হয়। Find/Enter/blur এবং sale submit-এ exact name resolve করার চেষ্টা হয়; অসম্পূর্ণ নাম নির্বাচন ছাড়া submit করা যায় না। পুরোনো customer detail request-এ বর্তমান ইনপুট বদলে গেলে তার stale response উপেক্ষা করা হয়।

```javascript
if (customerPhone.value.trim() !== phone) {
    return false;
}

if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
    await suggestCustomers(true);
    if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
        await window.Swal.fire({
            title: 'Select a customer',
            text: 'Choose a customer from the suggestions, or enter a phone number.',
            icon: 'warning',
        });
        customerPhone.focus();
        return;
    }
}
```

### `resources/views/dashboard/dashboard.blade.php`

Shared DataTable configuration-এ তথ্য প্রদর্শন চালু রেখে footer-এ রাখা হয়েছে:

```javascript
new DataTable('#myTable', {
    info: true,
    pageLength: 10,
    lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'All']
    ],
    layout: {
        topStart: 'pageLength',
        topEnd: 'search',
        bottomStart: 'info',
        bottomEnd: 'paging'
    }
});
```

এর ফলে `#myTable` ব্যবহার করা পৃষ্ঠাগুলোতে “Showing 1 to … of … entries” লাইনটি উপরের controls-এ না থেকে footer-এ pagination-এর পাশে থাকবে। Page length, search ও pagination আগের মতো থাকবে।
