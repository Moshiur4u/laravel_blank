# ইনভয়েস থেকে Delivery Challan প্রিন্ট

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`  
এই কাজের জন্য Controller পরিবর্তন করা হয়নি। আগে থেকেই থাকা method ইনভয়েস ও তার পণ্যগুলো নিয়ে Challan view দেখায়:

```php
public function deliveryChallan($id)
{
    $invoice = SaleInvoice::with([
        'items' => fn($query) => $query->with('product')->withSum('returns as returned_quantity', 'quantity'),
        'customer',
        'employee',
    ])->findOrFail($id);

    return view('backend.Sales.deliveryChallan', compact('invoice'));
}
```

- `deliveryChallan($id)` URL থেকে ইনভয়েসের ID নেয়।
- `SaleInvoice::with(...)` পণ্য, গ্রাহক ও কর্মীর সম্পর্কসহ ইনভয়েস আনে; `findOrFail` ID না পেলে 404 দেয়।
- `return view(...)` বিদ্যমান Delivery Challan Blade view-তে ইনভয়েস পাঠায়।
- `?print=1` query parameter শুধু Blade view-তে ব্যবহৃত হয়; Controller-এ নতুন লজিক লাগেনি।

## Migration (Code and Explanation)

**কোনো Migration যোগ বা পরিবর্তন করা হয়নি।** প্রিন্ট আচরণটি বিদ্যমান ইনভয়েস ও প্রতিষ্ঠানের লোগো ব্যবহার করে; নতুন database column বা table প্রয়োজন নেই।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php`  
Route-টি আগে থেকেই ছিল এবং আগের মতোই ব্যবহার করা হয়েছে:

```php
Route::get('/sales/delivery-challan/{id}', [SaleController::class, 'deliveryChallan'])
    ->middleware('permission:sales.view|sales.report')
    ->name('sales.delivery-challan');
```

- `Route::get(...)` ইনভয়েস ID-সহ Challan দেখানোর URL নির্ধারণ করে।
- `SaleController::class, 'deliveryChallan'` আগের Controller method চালায়।
- `middleware(...)` আগের অনুমতি-নিয়ম অপরিবর্তিত রাখে।
- `name(...)` Blade থেকে route-টি নাম দিয়ে তৈরি করতে দেয়।

## Model (Code and Explanation)

**ফাইল:** `app/Models/SaleInvoice.php`  
Model-এ কোনো পরিবর্তন হয়নি। Challan-এর জন্য আগে থেকেই থাকা সম্পর্কগুলো ব্যবহৃত হয়:

```php
public function customer()
{
    return $this->belongsTo(Customer::class);
}

public function employee()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function items()
{
    return $this->hasMany(SaleItem::class);
}
```

- `customer()` ইনভয়েসের গ্রাহকের তথ্য আনে।
- `employee()` ইনভয়েসটি তৈরি করা কর্মীর তথ্য আনে।
- `items()` ইনভয়েসের পণ্যগুলোর তালিকা আনে।
- বিদ্যমান Controller এগুলো eager-load করায় নতুন Model কোড দরকার হয়নি।

## Blade Template (Code and Explanation)

### ইনভয়েস থেকে Challan খোলা ও সরাসরি প্রিন্ট

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

```blade
<a href="{{ route('sales.delivery-challan', ['id' => $invoice->id, 'print' => 1]) }}"
    target="_blank" rel="noopener" class="btn btn-primary">
    <i class="bx bx-printer me-1"></i>Print Delivery Challan
</a>
```

- প্রথম লাইনের `<a>` একটি লিংক তৈরি করে; `route(...)` বিদ্যমান Challan route-এ ইনভয়েস ID এবং স্বয়ংক্রিয় প্রিন্টের `print=1` পাঠায়।
- দ্বিতীয় লাইনের `target="_blank"` Challan নতুন ট্যাবে খোলে, `rel="noopener"` নতুন ট্যাব থেকে মূল পেজ নিয়ন্ত্রণ ঠেকায় এবং `btn btn-primary` লিংকটিকে বোতামের মতো দেখায়।
- তৃতীয় লাইনের `<i>` প্রিন্টার আইকন দেখায় এবং `me-1` আইকন ও লেখার মাঝে ফাঁক রাখে।
- শেষ লাইনের `</a>` লিংকটি বন্ধ করে; ফলে ক্লিকটি ভুল করে বর্তমান ইনভয়েস প্রিন্ট না করে Challan পেজে যায়।

### লোগো watermark সবসময় চালু রাখা

**ফাইল:** `resources/views/backend/Sales/deliveryChallan.blade.php`

```blade
<div id="print-document"
    class="border-0 shadow-sm card print-document @if ($organization?->logo) watermark-enabled @endif"
    @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
```

- প্রথম লাইনে print document container চিহ্নিত করার জন্য `id="print-document"` রাখা হয়েছে।
- দ্বিতীয় লাইনে লোগো থাকলেই `watermark-enabled` class যোগ হয়; অন্য class-গুলো Challan-এর বিদ্যমান layout ও styling বজায় রাখে।
- তৃতীয় লাইনে প্রতিষ্ঠানের লোগোর asset URL CSS variable-এ সেট হয়, যা watermark-এর background image হিসেবে ব্যবহৃত হয়।
- লোগো না থাকলে watermark class বা image URL তৈরি হয় না।
- আগে watermark নিয়ন্ত্রণের checkbox ও permission-নির্ভর toggle ছিল; Challan-এ watermark সবসময় থাকার জন্য সেটি সরানো হয়েছে।

### ক্লিক থেকে স্বয়ংক্রিয় Print dialog

```blade
@if (request()->boolean('print'))
    <script>
        window.addEventListener('load', function() {
            window.setTimeout(function() {
                window.print();
            }, 250);
        });
    </script>
@endif
```

- `@if`-এর লাইনটি query parameter সত্য হলে তবেই script HTML-এ রাখে।
- `<script>` ও `</script>` ব্রাউজারে চালানোর JavaScript অংশ শুরু ও শেষ করে।
- `window.addEventListener('load', ...)` Challan ও লোগোর মতো পেজের রিসোর্স লোড হওয়া পর্যন্ত অপেক্ষা করে।
- `window.setTimeout(...)` প্রিন্ট ডায়ালগ খোলার আগে ২৫০ মিলিসেকেন্ড অপেক্ষা করে।
- `window.print()` ব্রাউজারের print dialog খোলে।
- `@endif` শর্তাধীন Blade block বন্ধ করে।
- Challan পেজে থাকা নিজস্ব Print বোতামও আগের মতো কাজ করে।

### সব প্রিন্ট পেজে প্রতিষ্ঠানের তথ্য বড় করা

**ফাইল:** `resources/views/dashboard/dashboard.blade.php`

```css
.invoice-organization,
.invoice-organization *,
.quotation-organization,
.quotation-organization *,
.organization-report-header,
.organization-report-header * {
    font-size: 12px !important;
}

.invoice-organization h4,
.quotation-organization h4,
.organization-report-header h2 {
    font-size: 20px !important;
    line-height: 1.25 !important;
}

.invoice-organization img,
.quotation-organization img,
.organization-report-header img {
    max-width: 110px !important;
    max-height: 80px !important;
}

.money-receipt .receipt-organization,
.money-receipt .receipt-organization * {
    font-size: 8pt !important;
}

.money-receipt .receipt-organization h2 {
    font-size: 11pt !important;
}

.money-receipt .receipt-organization img {
    max-width: 18mm !important;
    max-height: 14mm !important;
}
```

- প্রথম selector-গুলো Invoice, Challan, quotation এবং shared report header-এর সব প্রতিষ্ঠানের তথ্যকে প্রিন্টে 12px করে।
- পরের selector-গুলো প্রতিষ্ঠানের নামকে 20px করে এবং line-height নির্ধারণ করে।
- লোগোর সর্বোচ্চ মাপ 110×80px করা হয়েছে, যাতে আগের চেয়ে বড় হলেও অনুপাত ঠিক থাকে।
- Money Receipt-এ জায়গা কম হওয়ায় তথ্য 8pt, প্রতিষ্ঠানের নাম 11pt এবং লোগো সর্বোচ্চ 18×14mm রাখা হয়েছে।
- এই নিয়মগুলো dashboard-এর `@media print`-এর মধ্যে থাকায় স্ক্রিনের স্বাভাবিক layout বদলায় না; `!important` আগের ছোট print font-কে অতিক্রম করে।