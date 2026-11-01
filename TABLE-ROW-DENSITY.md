# টেবিলের সারির উচ্চতা, প্রিন্ট এবং ফাঁকা ইনভয়েস

এই পরিবর্তনে dashboard layout-এ সব টেবিলের জন্য কম padding ও একক data-font-size নির্ধারণ করা হয়েছে। টেবিলের header-গুলো Sales/Quotation-এর নীল `#14ADE0` রঙ ব্যবহার করে এবং বহু-পৃষ্ঠার প্রিন্টে `<thead>` পুনরাবৃত্ত হয়। Sales পৃষ্ঠা থেকে প্রতিষ্ঠানের তথ্যসহ একটি ফাঁকা ইনভয়েস প্রিন্ট করা যায়।

## Controller (Code and Explanation)

`app/Http/Controllers/SaleController.php`-এ নতুন method:

```php
public function blankInvoice()
{
    return view('backend.Sales.blankInvoice');
}
```

এই method কোনো বিক্রয় বা database record তৈরি করে না; শুধু ফাঁকা ইনভয়েসের Blade view দেখায়। প্রতিষ্ঠানের তথ্য বিদ্যমান shared view data থেকে আসে।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন করা হয়নি। ফাঁকা ইনভয়েস কেবল প্রিন্টের জন্য; কোনো তথ্য সংরক্ষণ করে না, তাই database schema বদলানোর প্রয়োজন নেই।

## Route (Code and Explanation)

`routes/web.php`-এর authenticated ও verified route group-এ:

```php
Route::get('/sales/blank-invoice', [SaleController::class, 'blankInvoice'])
    ->middleware('permission:sales.create|sales.view')
    ->name('sales.blank-invoice');
```

শুধু Sales তৈরি বা দেখার অনুমতিপ্রাপ্ত ব্যবহারকারী route-টি খুলতে পারেন।

## Model (Code and Explanation)

কোনো model যোগ বা পরিবর্তন করা হয়নি। ফাঁকা ইনভয়েসে কোনো model record-এর প্রয়োজন হয় না।

## Blade Template (Code and Explanation)

### সব টেবিল ও প্রিন্টের সাধারণ নিয়ম

`resources/views/dashboard/dashboard.blade.php`-এর shared style-এ:

```css
table > :not(caption) > * > * {
    padding: .25rem .4rem !important;
    line-height: 1.2;
}

table thead th {
    background-color: #14ADE0 !important;
    border-color: #14ADE0 !important;
    color: #fff !important;
}

table :where(tbody td, tbody th),
table :where(tbody td, tbody th) *:not(i):not(svg):not(path) {
    font-size: .875rem !important;
}

@media print {
    @page {
        margin: 34mm 12mm 15mm;
    }

    *,
    *::before,
    *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table thead {
        display: table-header-group;
    }

    table tfoot {
        display: table-footer-group;
    }

    table tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .invoice-organization,
    .quotation-organization,
    .organization-report-header {
        position: fixed !important;
        top: 3mm;
        left: 12mm;
        right: 12mm;
        z-index: 1000;
        margin: 0 !important;
        background: #fff !important;
    }
}
```

Shared layout-এ থাকার কারণে নিয়মগুলো dashboard-ভিত্তিক সব টেবিলে প্রযোজ্য। `thead`-এর header প্রতিটি printed page-এ দেখা যায়; প্রতিষ্ঠানের header-যুক্ত invoice, quotation এবং report-এ logo/পরিচিতিও প্রতিটি page-এ থাকে।

### Sales পৃষ্ঠা থেকে প্রিন্ট বাটন

`resources/views/backend/Sales/salesIndex.blade.php`-এর breadcrumb action:

```blade
<div class="ms-auto">
    <a href="{{ route('sales.blank-invoice') }}" class="btn btn-outline-primary">
        <i class="bx bx-printer me-1"></i>Print Blank Invoice
    </a>
</div>
```

এটি নতুন permission-protected route-এ নিয়ে যায়; সেখানে Print Blank Invoice বাটনে চাপলে browser-এর print dialog খোলে।

### ফাঁকা ইনভয়েস view

নতুন `resources/views/backend/Sales/blankInvoice.blade.php`-এর মূল কাঠামো:

```blade
@extends('dashboard.dashboard')
@section('title', 'Blank Sales Invoice')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Sales
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print Blank Invoice
                </button>
            </div>

            <div class="border-0 shadow-sm card print-document">
                <div class="p-4 card-body p-md-5">
                    @if ($organization?->logo || $organization?->name || $organization?->address || $organization?->phone || $organization?->email)
                        <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom invoice-organization">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                    style="max-width: 90px; max-height: 65px; object-fit: contain;">
                            @endif
                            <div>
                                @if ($organization?->name)
                                    <h4 class="mb-0 fw-bold text-dark">{{ $organization->name }}</h4>
                                @endif
                                @if ($organization?->address)
                                    <div class="small text-muted">{{ $organization->address }}</div>
                                @endif
                                @if ($organization?->phone)
                                    <div class="small text-muted">Phone: {{ $organization->phone }}</div>
                                @endif
                                @if ($organization?->email)
                                    <div class="small text-muted">Email: {{ $organization->email }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mb-4 row">
                        <div class="col-7">
                            <h2 class="mb-2 text-primary fw-bold">Sales Invoice</h2>
                            <div>Invoice No: ____________________</div>
                            <div>Date: __________________________</div>
                        </div>
                        <div class="col-5 text-end">
                            <h5 class="mb-2 fw-bold">Customer</h5>
                            <div>Name: __________________________</div>
                            <div>Phone: __________________________</div>
                            <div>Address: ________________________</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle blank-invoice-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 7%;">#</th>
                                    <th style="width: 43%;">Product / Description</th>
                                    <th class="text-center" style="width: 15%;">Quantity</th>
                                    <th class="text-end" style="width: 17%;">Unit Price</th>
                                    <th class="text-end" style="width: 18%;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($row = 1; $row <= 12; $row++)
                                    <tr>
                                        <td class="text-center">{{ $row }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endfor
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Discount</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">VAT / Tax</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Total</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Amount Paid</th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Due</th>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5 row">
                        <div class="col-6">
                            <div class="pt-2 text-center border-top w-75">Customer Signature</div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 text-center border-top w-75">Authorized Signature</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .blank-invoice-table tbody tr:not(:nth-last-child(-n + 6)) {
            height: 28px;
        }

        @media print {
            .blank-invoice-table tbody tr:not(:nth-last-child(-n + 6)) {
                height: 28px;
            }

            .print-document {
                border: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
@endsection
```

ফর্মের input বা database record ছাড়াই হাতে লেখার জন্য ১২টি পণ্য-সারি, মোটের ঘর এবং স্বাক্ষরের জায়গা দেওয়া হয়েছে।

### বিদ্যমান invoice/quotation-এর প্রিন্ট header

`resources/views/backend/Sales/salesInvoice.blade.php`-এ পণ্যের শিরোনাম `tbody` থেকে `thead`-এ স্থানান্তর করা হয়েছে, যাতে browser multi-page print-এ সেটি পুনরাবৃত্ত করতে পারে:

```blade
<thead>
    <tr>
        <th class="text-center">#</th>
        <th>Product</th>
        <th class="text-end">Unit Selling Price</th>
        <th class="text-center">Quantity</th>
        <th class="text-end">Amount</th>
    </tr>
</thead>
<tbody>
    @foreach ($invoice->items as $key => $item)
        {{-- বিদ্যমান item row --}}
    @endforeach
```

Quotation-এর header আগে থেকেই `thead`-এ ছিল। `deliveryChallan.blade.php` এবং `customerLedgerInvoice.blade.php`-এর প্রতিষ্ঠানের তথ্যের wrapper-এ `invoice-organization` class যোগ করা হয়েছে; `supplierInvoice.blade.php`-এ `@include('backend.partials.organizationHeader')` যোগ করা হয়েছে। ফলে shared print CSS এগুলোর logo/header প্রতিটি page-এ ধরে রাখে।

প্রতিষ্ঠানের header wrapper-এর পরিবর্তিত markup:

```blade
<div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom invoice-organization">
    {{-- বিদ্যমান প্রতিষ্ঠানের logo ও যোগাযোগের তথ্য --}}
</div>
```

`user-activity/index.blade.php`-এর print style-এ:

```blade
@page {
    size: landscape;
    margin: 34mm 12mm 15mm;
}
```

এবং প্রিন্টযোগ্য activity heading-এর আগে:

```blade
@include('backend.partials.organizationHeader')
```

যোগ করা হয়েছে, যাতে এই বহু-পৃষ্ঠার table-ও একই organization header ও logo পায়।

### Organization তথ্য না থাকলে partial-এর নিরাপদ আচরণ

`resources/views/backend/partials/organizationHeader.blade.php`-এর শুরুতে organization variable অনুপস্থিত থাকলে `null` ধরা হয়:

```blade
@php
    $organization = $organization ?? null;
    $hasOrganizationDetails = $organization
        && ($organization->name || $organization->logo || $organization->phone || $organization->email || $organization->address);
@endphp
```

সব view/controller-তে organization পাঠানো হয় না। এই guard-এর ফলে ঐচ্ছিক organization header include করলেও `Undefined variable $organization` exception হয় না; তথ্য না থাকলে প্রতিষ্ঠান header বাদ থাকে।

### Blank Invoice-এ organization তথ্য পাঠানো

`app/Providers/AppServiceProvider.php`-এর organization view composer-এ blank invoice view যোগ করা হয়েছে:

```php
View::composer([
    'backend.Sales.salesInvoice',
    'backend.Sales.blankInvoice',
    'backend.Sales.deliveryChallan',
    // অন্যান্য print view
], function ($view) {
    $view->with('organization', Organization::query()->find(1));
});
```

এই composer blank invoice Blade-এ `$organization` সরবরাহ করে; ফলে প্রতিষ্ঠানের নাম, যোগাযোগ এবং logo দেখানোর সময় `Undefined variable $organization` error হয় না।

### Blank Invoice-কে Sales Invoice-এর print layout-এর সঙ্গে মিলানো

`resources/views/backend/Sales/blankInvoice.blade.php`-এ Sales invoice-এর মতো একই print document wrapper, watermark control, customer header class, table class/column widths, signatures এবং print CSS ব্যবহার করা হয়েছে:

```blade
<div id="print-document" class="border-0 shadow-sm card print-document"
    @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
    <div class="p-4 card-body p-md-5">
        {{-- প্রতিষ্ঠানের তথ্য; logo থাকলে invoice-এর watermark ব্যবহার করা যায় --}}

        <div class="mb-4 row align-items-start invoice-header">
            <div class="col-7">
                <h2 class="mb-1 text-primary fw-bold">Sales Invoice</h2>
                <div class="text-muted">Invoice: <strong>____________________</strong></div>
                <div class="text-muted">Date: ________________________</div>
            </div>
            <div class="col-5 text-end">
                <h5 class="mb-1 fw-bold">Customer</h5>
                <div>Name: ____________________________</div>
                <div>Phone: ____________________________</div>
                <div>Address: __________________________</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-bordered invoice-table blank-invoice-table">
                <colgroup>
                    <col style="width: 6%;">
                    <col style="width: 39%;">
                    <col style="width: 20%;">
                    <col style="width: 15%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Product</th>
                        <th class="text-end">Unit Selling Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                {{-- হাতে লেখার জন্য ফাঁকা পণ্য ও মোটের সারি --}}
            </table>
        </div>
    </div>
</div>
```

Print CSS-এ navigation ও action button লুকানো, page/card spacing reset, responsive table-এর overflow সরানো, invoice table-কে fixed layout-এ রাখা এবং row-কে page-এর মাঝখানে ভাঙা থেকে বিরত রাখার নিয়ম Sales invoice-এর সঙ্গে মিলিয়ে দেওয়া হয়েছে:

```css
@media print {
    header,
    .topbar,
    .sidebar-wrapper,
    .no-print,
    .page-footer,
    .footer {
        display: none !important;
    }

    .page-wrapper,
    .page-content {
        margin: 0 !important;
        padding: 0 !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    .invoice-table {
        width: 100% !important;
        table-layout: fixed;
    }

    .invoice-table tr {
        break-inside: avoid;
    }
}
```

এর ফলে ফাঁকা invoice-ও Sales invoice-এর মতো পরিচ্ছন্ন কাগজের layout-এ প্রিন্ট হয়; navigation ছাপা হয় না, কলামগুলো একই অনুপাতে থাকে এবং ঐচ্ছিক watermark কাজ করে।

### Quotation print watermark-কে Sales Invoice-এর সঙ্গে মিলানো

`resources/views/backend/quotation/show.blade.php`-এর print CSS-এ watermark-এর জন্য Sales invoice-এর একই `print-document` selector ও stacking context ব্যবহার করা হয়েছে:

```css
.print-document {
    position: relative;
    isolation: isolate;
}

.print-document.watermark-enabled::before {
    content: '';
    position: fixed;
    inset: 0;
    z-index: 0;
    background-image: var(--invoice-watermark);
    background-position: center;
    background-repeat: no-repeat;
    background-size: min(65vw, 600px);
    opacity: .09;
    pointer-events: none;
}

.print-document > .card-body {
    position: relative;
    z-index: 1;
}
```

Quotation document-এ `print-document` class আগে থেকেই ছিল এবং logo URL-ও CSS custom property-তে দেওয়া ছিল। এখন Sales invoice-এর মতো watermark-enabled class-সহ একই shared document selector ব্যবহৃত হয়; checkbox চালু করলে logo page-এর পেছনে দেখা যায়, আর invoice content তার ওপরে থাকে।

### Sales Invoice-এর পরিচিতি ও Customer তথ্য প্রিন্টে দেখানো

`resources/views/backend/Sales/salesInvoice.blade.php`-এর `@media print`-এ invoice/customer header-এর জন্য top clearance যোগ করা হয়েছে:

```css
.invoice-header {
    position: relative;
    z-index: 1;
    margin-top: 26mm !important;
}
```

প্রতিষ্ঠানের logo ও তথ্য print-এ fixed header হিসেবে দেখানো হয়। অতিরিক্ত top clearance না থাকলে invoice number/date এবং customer name/phone ঐ header-এর পেছনে চাপা পড়তে পারে। এই নিয়ম শুধু Sales invoice print-এ প্রয়োগ হয় এবং screen layout বদলায় না।