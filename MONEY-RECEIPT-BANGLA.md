# ফাঁকা Money Receipt প্রিন্ট সুবিধা

## Controller (Code and explanation)

নতুন `app/Http/Controllers/MoneyReceiptController.php` controller শুধু blank receipt view দেখায়:

```php
<?php

namespace App\Http\Controllers;

class MoneyReceiptController extends Controller
{
    public function create()
    {
        return view('backend.money-receipts.create');
    }
}
```

এই controller কোনো payment গ্রহণ, receipt নম্বর তৈরি বা database-এ তথ্য সংরক্ষণ করে না। receipt-টি হাতে পূরণের template।

Money Receipt view-এ প্রতিষ্ঠানের তথ্য সরবরাহের জন্য `app/Providers/AppServiceProvider.php`-এর view composer-এ view-টি যুক্ত করা হয়েছে:

```php
// Money Receipt view-এ প্রতিষ্ঠানের তথ্য পাঠিয়ে Undefined variable error ঠেকায়।
'backend.money-receipts.create',
```

এর ফলে Blade template-এর `$organization` variable পাওয়া যায় এবং প্রতিষ্ঠানের নাম, ঠিকানা, ফোন, ইমেইল ও logo প্রদর্শিত হয়।

## Migration (Code and explanation)

কোনো migration যোগ করা হয়নি। receipt সম্পূর্ণ blank এবং template-ভিত্তিক; তথ্য database-এ রাখার প্রয়োজন নেই।

## Route (Code and explanation)

`routes/web.php`-এ controller import এবং authentication/verification group-এর Sales route-গুলোর পাশে route যোগ করা হয়েছে:

```php
use App\Http\Controllers\MoneyReceiptController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/money-receipts/create', [MoneyReceiptController::class, 'create'])
        ->middleware('permission:sales.create|sales.view')
        ->name('money-receipts.create');
});
```

শুধু Sales create বা view permission থাকা লগইন করা verified ব্যবহারকারী receipt template খুলতে পারবেন।

## Model (Code and explanation)

কোনো model যোগ বা পরিবর্তন করা হয়নি। এটি সংরক্ষিত receipt নয়; তাই model বা database table প্রয়োজন নেই।

## Blade (Code and explanation)

নতুন `resources/views/backend/money-receipts/create.blade.php`-এ blank form, organization branding, print action এবং responsive/print CSS রাখা হয়েছে। Sales page-এ `resources/views/backend/Sales/salesIndex.blade.php` থেকে প্রবেশের button যোগ করা হয়েছে।

### Sales page-এর button

```blade
@canany(['sales.create', 'sales.view'])
    <div class="{{ auth()->user()->can('blank-invoice') ? '' : 'ms-auto' }}">
        <a href="{{ route('money-receipts.create') }}" class="btn btn-outline-success">
            <i class="bx bx-receipt me-1"></i>Print Money Receipt
        </a>
    </div>
@endcanany
```

এই button-টি বিদ্যমান Sales breadcrumb action-এ রাখা হয়েছে। permission-যুক্ত ব্যবহারকারীরা এখান থেকে template-এ যেতে পারেন।

### Receipt-এর blank printable fields

```blade
<main class="money-receipt">
    <div class="receipt-accent"></div>
    <header class="receipt-heading">
        <div class="receipt-organization">
            @if ($organization?->logo)
                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                    alt="{{ $organization->name ?: 'Organization logo' }}">
            @endif
            <div>
                <h2>{{ $organization?->name ?: config('app.name') }}</h2>
                @if ($organization?->address)<div>{{ $organization->address }}</div>@endif
                @if ($organization?->phone)<div>Phone: {{ $organization->phone }}</div>@endif
                @if ($organization?->email)<div>{{ $organization->email }}</div>@endif
            </div>
        </div>
        <div class="receipt-title">
            <span>PAYMENT RECORD</span>
            <h1>MONEY RECEIPT</h1>
        </div>
    </header>

    <div class="receipt-meta">
        <div>Receipt No. <span class="write-line"></span></div>
        <div>Date <span class="write-line"></span></div>
    </div>
    <section class="receipt-fields" aria-label="Receipt details">
        <div class="receipt-field">
            <span class="field-label">Received with thanks from</span>
            <span class="field-line"></span>
        </div>
        <div class="receipt-field">
            <span class="field-label">Amount in words</span>
            <span class="field-line"></span>
        </div>
        <div class="receipt-field">
            <span class="field-label">Payer contact number</span>
            <span class="field-line"></span>
        </div>
    </section>
    <div class="receipt-payment-row">
        <div class="payment-options">
            <span class="field-label">Payment method</span>
            <span class="payment-option"><i></i>Cash</span>
            <span class="payment-option"><i></i>Cheque</span>
            <span class="payment-option"><i></i>Card</span>
            <span class="payment-option"><i></i>Bank transfer</span>
            <span class="payment-option"><i></i>Mobile wallet</span>
        </div>
        <div class="receipt-amount">
            <span>Amount</span><strong>৳</strong><span class="amount-line"></span>
        </div>
    </div>
    <div class="receipt-field receipt-reference">
        <span class="field-label">Payment reference / bank details</span>
        <span class="field-line"></span>
    </div>
    <div class="receipt-field receipt-purpose">
        <span class="field-label">Payment received for</span>
        <span class="field-line"></span>
    </div>
    <footer class="receipt-signatures">
        <div><span></span>Received by</div>
        <div><span></span>Authorized signature</div>
    </footer>
</main>
```

প্রতিষ্ঠানের নাম, ঠিকানা, ফোন, ইমেইল ও logo বিদ্যমান `$organization` data থেকে আসে; টাকা, payer, contact number, payment method, reference, purpose ও signature-এর ঘরগুলো ইচ্ছাকৃতভাবে ফাঁকা।

### Responsive ও print styling

একই Blade file-এর CSS-এ screen-এর জন্য flexible receipt card এবং print-এর জন্য A4 portrait layout দেওয়া হয়েছে। মূল print rules:

```css
@media print {
    @page {
        size: A4 portrait;
        margin: 5mm;
    }

    header:not(.receipt-heading),
    .topbar,
    .sidebar-wrapper,
    .page-footer,
    .footer,
    .no-print {
        display: none !important;
    }

    .wrapper,
    .page-wrapper,
    .page-content {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    body {
        margin: 0 !important;
        background: #fff !important;
    }

    .money-receipt {
        display: flex !important;
        box-sizing: border-box;
        width: 100%;
        max-width: none;
        height: 94mm;
        margin: 0;
        padding: 3mm 6mm 2mm 8mm;
        border: 1px solid #d6e0e7;
        border-radius: 0;
        box-shadow: none;
        font-size: 8pt;
        break-inside: avoid;
        page-break-inside: avoid;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
```

`Print Money Receipt` button-এর `window.print()` browser-এর print dialog খুলে। এটি sample image-এর stock artwork অনুলিপি না করে প্রতিষ্ঠানের branding ও সাধারণ receipt field-সহ একটি স্বতন্ত্র design ব্যবহার করে।

### Money Receipt-এর print layout

আগের A5 একক receipt layout-কে A4 portrait কাগজে তিনটি copy প্রিন্ট করার বিন্যাসে বদলানো হয়েছে। প্রতিটি copy-এর উচ্চতা 94mm এবং print-only spacing ছোট রাখা হয়েছে; ফলে screen-এর responsive preview আগের মতোই একটি receipt দেখায়।

### A4 কাগজে তিনটি Money Receipt

একই blank receipt template তিনবার render করতে Blade loop ব্যবহার করা হয়েছে। স্ক্রিনে প্রথম copy ছাড়া অন্যগুলো লুকানো থাকে; print-এ তিনটিই দেখানো হয়:

```blade
@for ($copy = 1; $copy <= 3; $copy++)
    <main class="money-receipt">
        {{-- প্রতিটি copy-তে একই ফাঁকা Money Receipt field ও প্রতিষ্ঠানের তথ্য --}}
    </main>
@endfor
```

Print CSS-এ কাগজ A4 portrait এবং প্রতিটি receipt-এর উচ্চতা 94mm করা হয়েছে। প্রথম ও পরের copy screen-এ আলাদা করার জন্য `:not(:first-of-type)` ব্যবহার হয়; print-এ `display: flex !important` দিয়ে তিনটিই দেখানো হয়:

```css
.money-receipt:not(:first-of-type) {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 5mm;
    }

    .money-receipt {
        display: flex !important;
        box-sizing: border-box;
        width: 100%;
        height: 94mm;
        padding: 3mm 6mm 2mm 8mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .money-receipt:not(:last-of-type) {
        border-bottom: 1.5px dashed #536b78;
        position: relative;
    }

    .money-receipt:not(:last-of-type)::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: -2mm;
        left: 0;
        border-bottom: 1px dashed #8a9aa3;
    }
}
```

A4 portrait-এর printable height-এর মধ্যে তিনটি 94mm copy বসে। প্রথম দুইটির আরও স্পষ্ট double dashed separator কেটে আলাদা receipt করার guide দেয়; `break-inside: avoid` প্রতিটি copy-কে আলাদা page-এ ভেঙে যেতে বাধা দেয়।
