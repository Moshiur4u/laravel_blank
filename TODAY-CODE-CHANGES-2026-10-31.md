# Code Changes — 2026-10-31

এই নথিতে আজকের পরিবর্তিত Controller, Migration, Model এবং Blade অংশগুলোর কোড ও পরিবর্তনের কারণ লেখা হয়েছে। নতুন Blade/CSS পরিবর্তনের পাশে বাংলায় সংক্ষিপ্ত মন্তব্যও যোগ করা হয়েছে।

নিচের code block-গুলো পরিবর্তিত অংশের প্রাসঙ্গিক excerpt; অপরিবর্তিত markup বাদ দেওয়া হয়েছে।

## Controller (Code and explanation) —

**কোড:** আজ কোনো Controller ফাইলে পরিবর্তন করা হয়নি।

**ব্যাখ্যা:** ইনভয়েস ও কোটেশনের প্রিন্ট বিন্যাস এবং প্রদর্শনের শর্তগুলো Blade view-তেই সমাধান করা হয়েছে; এজন্য Controller-এর ডেটা বা ব্যবসায়িক লজিক বদলানোর প্রয়োজন হয়নি।

## Migration (Code and explanation) —

**কোড:** আজ কোনো Migration ফাইলে পরিবর্তন করা হয়নি।

**ব্যাখ্যা:** এই কাজগুলোতে নতুন ডেটাবেস কলাম বা টেবিলের প্রয়োজন হয়নি।

## Model (Code and explanation) —

**কোড:** আজ কোনো Model ফাইলে পরিবর্তন করা হয়নি।

**ব্যাখ্যা:** বিদ্যমান ইনভয়েস, কোটেশন ও প্রতিষ্ঠানের ডেটাই ব্যবহার করা হয়েছে; কোনো cast, relationship বা model logic বদলানো হয়নি।

## Blade (Code and explanation) —

### Sales invoice — [salesInvoice.blade.php](./resources/views/backend/Sales/salesInvoice.blade.php)

**প্রতিষ্ঠানের পরিচিতি ও গ্রাহক তথ্য:** আগের টেবিল-সারি-ভিত্তিক পরিচিতির বদলে Delivery Challan-এর মতো আলাদা organization header এবং invoice/customer row রাখা হয়েছে। লোগো ও প্রতিষ্ঠানের তথ্য থাকলেই header দেখায়।

```blade
@if ($organization?->logo || $organization?->name || $organization?->address || $organization?->phone || $organization?->email)
    {{-- প্রতিষ্ঠানের তথ্য টেবিলের বাইরে রাখলে ইনভয়েসের প্রিন্ট হেডার পরিষ্কার থাকে। --}}
    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom invoice-organization">
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

{{-- ইনভয়েসের পরিচিতি ও গ্রাহকের তথ্য আলাদা কলামে রাখলে প্রিন্টে দ্রুত পড়া যায়। --}}
<div class="mb-4 row align-items-start invoice-header">
    <div class="col-7">
        <h2 class="mb-1 text-primary fw-bold">Sales Invoice</h2>
        <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
        <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
    </div>
    <div class="col-5 text-end">
        <h5 class="mb-1 fw-bold">Customer</h5>
        <div>Name: {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
        <div>Phone: {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
        @if ($invoice->customer_address ?? $invoice->customer?->address)
            <div>Address: {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
        @endif
    </div>
</div>
```

**পণ্য টেবিল ও শর্তসাপেক্ষ চার্জ:** নির্দিষ্ট কলাম প্রস্থ ও fixed table layout পণ্যের নামকে পাশের কলামে ঢুকে পড়া থেকে আটকায়। Discount, VAT বা AIT-এর পরিমাণ শূন্য হলে সংশ্লিষ্ট সারি প্রিন্টে আসে না।

```blade
{{-- নির্দিষ্ট কলাম প্রস্থ পণ্যের নামকে পাশের মূল্য কলামে ঢুকে পড়া থেকে আটকায়। --}}
<colgroup>
    <col style="width: 6%;">
    <col style="width: 39%;">
    <col style="width: 20%;">
    <col style="width: 15%;">
    <col style="width: 20%;">
</colgroup>

{{-- ছাড় ও করের পরিমাণ শূন্য হলে অপ্রয়োজনীয় সারি প্রিন্টে দেখানো হয় না। --}}
@if ((float) $invoice->discount_amount > 0)
    <tr>
        <th colspan="4" class="text-end">
            Discount{{ $invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0 ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}
        </th>
        <td class="text-end">- ৳{{ number_format($invoice->discount_amount, 2) }}</td>
    </tr>
@endif
@if ((float) $invoice->vat_amount > 0)
    <tr>
        <th colspan="4" class="text-end">VAT</th>
        <td class="text-end">৳{{ number_format($invoice->vat_amount, 2) }}</td>
    </tr>
@endif
@if ((float) $invoice->ait_amount > 0)
    <tr>
        <th colspan="4" class="text-end">AIT</th>
        <td class="text-end">৳{{ number_format($invoice->ait_amount, 2) }}</td>
    </tr>
@endif
```

```css
/* নির্দিষ্ট টেবিল প্রস্থ প্রিন্টে পণ্যের কলামগুলোকে সঠিক জায়গায় রাখে। */
.invoice-table {
    width: 100% !important;
    table-layout: fixed;
}
```

**Notes ও Terms & Conditions:** এগুলোকে invoice table-এর বাইরে আনা হয়েছে, যাতে টেবিলের পাঁচ-কলামের বিন্যাসে না ঢুকে স্বাভাবিকভাবে পড়া যায়। Due row-এর লাল text class-ও সরানো হয়েছে; এখন এটি অন্য মোটের মতো স্বাভাবিক রঙে bold থাকে।

```blade
{{-- নোট ও শর্তাবলি টেবিলের বাইরে রাখলে এগুলো পড়া সহজ হয়। --}}
@if ($invoice->notes)
    <div class="mt-3"><strong>Notes:</strong> {{ $invoice->notes }}</div>
@endif

<div class="mt-4 p-3 border rounded invoice-terms">
    <h6 class="mb-2 fw-bold text-dark">
        <i class="bx bx-check-shield text-primary me-1"></i>Terms &amp; Conditions
    </h6>
    {{-- শর্তাবলির লেখা বড় ও বামদিকে রেখে লাইনগুলোর মাঝে পড়ার উপযোগী ফাঁকা রাখা হয়। --}}
    <div class="invoice-terms-content">{{ $termsConditions }}</div>
</div>
```

```css
.invoice-terms-content {
    font-size: 1rem;
    line-height: 1.8;
    text-align: left;
    white-space: pre-line;
}
```

```blade
{{-- Due-কে অন্য মোটের মতো স্বাভাবিক রঙে রেখে শুধু bold করা হয়েছে। --}}
<tr class="fw-bold">
    <th colspan="4" class="text-end">Due</th>
    <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
</tr>
```

### Quotation — [show.blade.php](./resources/views/backend/quotation/show.blade.php)

**প্রিন্ট নিয়ন্ত্রণ ও হেডার:** toolbar-এ flex wrapping যোগ হয়েছে এবং `sales.document.watermark` permission ও organization logo থাকলেই watermark toggle দেখা যায়। Organization details এবং quotation/customer information-কে Delivery Challan-এর মতো আলাদা header-এ সাজানো হয়েছে।

```blade
{{-- ছোট পর্দায় বাটনগুলো গুটিয়ে রাখে এবং অনুমতিপ্রাপ্ত ব্যবহারকারীকে ওয়াটারমার্ক চালু করতে দেয়। --}}
<div class="gap-2 mb-3 d-flex flex-wrap justify-content-end no-print">
    @can('sales.document.watermark')
        @if ($organization?->logo)
            <label>
                <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                Print logo watermark
            </label>
        @endif
    @endcan
</div>

{{-- প্রতিষ্ঠানের পরিচিতি আলাদা হেডারে রাখলে প্রিন্টে লোগো ও তথ্য স্পষ্ট থাকে। --}}
<div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom quotation-organization">
    @if ($organization?->logo)
        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
            alt="{{ $organization->name ?: 'Organization Logo' }}"
            style="max-width: 90px; max-height: 65px; object-fit: contain;">
    @else
        <img src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo"
            style="max-width: 60px; max-height: 60px; object-fit: contain;">
    @endif
    <div>
        <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name ?: 'My-POS Enterprise' }}</h4>
        <div class="small text-muted">Official Quotation</div>
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

{{-- উদ্ধৃতির পরিচিতি ও গ্রাহকের তথ্য পাশাপাশি রেখে ছোট পর্দায় একটির নিচে আরেকটি দেখানো হয়। --}}
<div class="mb-4 row align-items-start quotation-header">
    <div class="col-12 col-md-7">
        <h2 class="mb-1 text-primary fw-bold">Quotation</h2>
        <div class="text-muted">Ref No: <strong>{{ $quotation->quotation_no }}</strong></div>
        <div class="text-muted">Date: {{ $quotation->quotation_date->format('d M Y') }}</div>
    </div>
    <div class="col-12 col-md-5 text-md-end">
        <h5 class="mb-1 fw-bold">Customer</h5>
        <div>Name: {{ $quotation->customer_name }}</div>
        <div>Phone: {{ $quotation->customer_phone }}</div>
        @if ($quotation->customer_address)
            <div>Address: {{ $quotation->customer_address }}</div>
        @endif
    </div>
</div>
```

**পণ্য টেবিলের overflow:** পাঁচটি কলামের জন্য explicit width ব্যবহার করা হয়েছে; product name cell ও তার ভেতরের লেখা প্রয়োজনমতো wrap হয়, ফলে Unit Price কলামের ওপর পড়ে না।

```blade
{{-- স্থির কলাম প্রস্থ পণ্যের বর্ণনাকে মূল্য ও পরিমাণের কলাম থেকে আলাদা রাখে। --}}
<colgroup>
    <col style="width: 6%;">
    <col style="width: 39%;">
    <col style="width: 20%;">
    <col style="width: 15%;">
    <col style="width: 20%;">
</colgroup>
```

Item Code-টি quotation product description-এ আর দেখানো হয় না; পণ্যের নামের জায়গা বাড়িয়ে overflow কমানো হয়েছে।

```blade
{{-- পণ্যের নামের জন্য বেশি জায়গা রাখতে Item Code কোটেশনের প্রিন্টে দেখানো হয় না। --}}
{{-- @if ($item->product_id)
    <small class="text-muted">Item Code: PRD-{{ str_pad($item->product_id, 5, '0', STR_PAD_LEFT) }}</small>
@endif --}}
```

টেবিলের শিরোনামের রঙ Delivery Challan-এর নীলের সঙ্গে একই রাখা হয়েছে:

```blade
<th class="text-white" style="background-color: #14ADE0;">Product Description</th>
<th class="text-end text-white" style="background-color: #14ADE0;">Unit Price</th>
```

```css
/* স্থির টেবিল লেআউট ও লেখা ভাঙা দীর্ঘ পণ্যের নামকে পাশের কলামে যাওয়া থেকে আটকায়। */
.quotation-items {
    width: 100%;
    table-layout: fixed;
}

.quotation-product-name,
.quotation-product-name > * {
    min-width: 0;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}
```

```blade
{{-- মোট মূল্যকে হালকা ব্যাকগ্রাউন্ডে আলাদা করে সহজে চোখে পড়ার ব্যবস্থা করা হয়েছে। --}}
<div class="py-2 d-flex justify-content-between align-items-center bg-light rounded px-3">
    <span class="fw-bold fs-5 text-primary">Total Price</span>
    <span class="fw-bold fs-4 text-primary">৳{{ number_format((float) $quotation->total_amount, 2) }}</span>
</div>
```

**Responsive ও print layout:** ছোট স্ক্রিনে item table-এর font/padding কমে; print-এ item table-এর width, font ও padding নির্ধারিত হয়। Print stylesheet dashboard navigation লুকায় এবং অনুমতিপ্রাপ্ত watermark toggle-এ logo watermark দেখায়।

```css
/* মোবাইলে টেবিলের লেখা ও padding কমিয়ে দৃশ্যমান জায়গার সঙ্গে মানানো হয়। */
@media (max-width: 575.98px) {
    .quotation-items {
        font-size: .8rem;
    }

    .quotation-items th,
    .quotation-items td {
        padding: .4rem;
    }
}

/* প্রিন্টে নেভিগেশন বাদ দিয়ে উদ্ধৃতির বিষয়বস্তু ও ঐচ্ছিক লোগো ওয়াটারমার্ক রাখা হয়। */
@media print {
    .quotation-items {
        width: 100% !important;
        table-layout: fixed;
        font-size: 9pt;
    }

    .quotation-header > div {
        flex: 0 0 58.333333%;
        width: 58.333333%;
    }

    .quotation-header > div:last-child {
        flex: 0 0 41.666667%;
        width: 41.666667%;
    }

    .quotation-document.watermark-enabled::before {
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
}
```

Toggle listener-এর কারণে user checkbox চালু করলে print document-এ `watermark-enabled` class যোগ হয়; বন্ধ করলে class সরানো হয়।

```blade
{{-- checkbox বদলালে শুধু প্রিন্ট ডকুমেন্টে ওয়াটারমার্কের class যোগ বা সরানো হয়। --}}
<script>
    document.getElementById('watermark-toggle')?.addEventListener('change', function () {
        document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
    });
</script>
```

### Shared dashboard print rule — [dashboard.blade.php](./resources/views/dashboard/dashboard.blade.php)

`@page` margin zero করে browser-generated print header/footer (যেমন date, time, page title, URL) সরানোর চেষ্টা করা হয়; `body`-তে 10 mm margin রেখে document content-কে কাগজের কিনারা থেকে ফাঁকা রাখা হয়.

```blade
{{-- ব্রাউজারের প্রিন্ট হেডার-ফুটার সরিয়ে মূল ডকুমেন্টের চারপাশে প্রিন্ট মার্জিন রাখা হয়। --}}
<style>
    @media print {
        @page {
            margin: 0;
        }

        body {
            margin: 10mm !important;
        }
    }
</style>
```
