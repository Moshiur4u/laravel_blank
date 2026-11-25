# Sales Invoice Blade — বাংলায় সম্পূর্ণ শেখার নির্দেশিকা

## 1. এই ফাইলটির উদ্দেশ্য

`salesInvoice.blade.php` ফাইলটি বিক্রয়ের একটি ইনভয়েস HTML এবং print layout দেখায়।
এটি শুধু পেজের ডিজাইন নয়; একইসঙ্গে permission, invoice data, customer data, discount, VAT, AIT, due, signature এবং print behavior নিয়ন্ত্রণ করে।

ফাইলটি Laravel Blade, Bootstrap, PHP এবং ছোট কিছু JavaScript/CSS-এর সমন্বয়ে তৈরি।

---

## 2. ফাইলের মূল কাঠামো

| অংশ | কাজ |
|---|---|
| `@extends` | Dashboard layout উত্তরাধিকার হিসেবে নেয় |
| `@section('title', ...)` | Browser title সেট করে |
| `@section('content')` | মূল ইনভয়েস HTML বসায় |
| Blade directives | Permission ও শর্ত অনুযায়ী HTML দেখায় বা লুকায় |
| `@foreach` | Invoice-এর প্রতিটি item row বানায় |
| CSS | Screen ও print layout নিয়ন্ত্রণ করে |
| JavaScript | Watermark toggle, low stock ও auto print চালায় |

---

## 3. Layout ও পৃষ্ঠার শিরোনাম

```blade
@extends('dashboard.dashboard')
@section('title', 'Invoice ' . $invoice->invoice_no)
@section('content')
```

- `@extends('dashboard.dashboard')`: Dashboard layout-এর common header, sidebar ও footer ব্যবহার করা হবে।
- `@section('title', ...)`: ব্রাউজারের title হবে, যেমন `Invoice INV-0001`।
- `@section('content')`: এই section-এর HTML Dashboard layout-এর content অংশে ঢুকবে।

`$invoice` হলো Controller থেকে পাঠানো Invoice model object।

---

## 4. কাজের বোতাম

### নতুন বিক্রয়

```blade
@canany(['sales.create', 'sales.view'])
    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
        New Sale
    </a>
@endcanany
```

- `@canany(...)`: ভিতরের HTML দেখাবে যদি ব্যবহারকারীর যেকোনো একটি permission থাকে।
- `route('sales.index')`: named Laravel route তৈরি করে।
- `{{ }}`: PHP বা model value safeভাবে HTML output করে।

### ইনভয়েস হালনাগাদ

```blade
@if (auth()->user()->hasRole('admin') ||
        auth()->user()->can('Update_anyday_Invoice') ||
        (auth()->user()->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()))
```

- Admin যেকোনো invoice edit করতে পারে।
- `Update_anyday_Invoice` permission থাকলে যেকোনো দিনের invoice edit করা যায়।
- `Update_today_Invoice` ও invoice-এর date আজ হলে edit করা যায়।

`?->` হলো null-safe operator। Left side null হলেও program crash করবে না।

### ইনভয়েস বাতিল

```blade
<form action="{{ route('sales.destroy', $invoice->id) }}" method="POST">
    @csrf
    @method('DELETE')
</form>
```

- HTML form স্বাভাবিকভাবে DELETE method পাঠাতে পারে না।
- `@csrf`: CSRF protection token যুক্ত করে।
- `@method('DELETE')`: Laravel route-কে DELETE request হিসেবে পাঠায়।
- `confirm(...)`: Submit হওয়ার আগে ব্যবহারকারীকে confirmation window দেখায়।

Void action সাধারণত শুধু invoice না, সংশ্লিষ্ট stock, ledger এবং voucher হিসাবও reverse করে।

---

## 5. `print-document` ও মুদ্রণ এলাকা

```blade
<div id="print-document" class="print-document">
```

এই wrapper-এর ভিতরের organization header, customer, table, terms ও signature সব প্রিন্ট হবে।

### প্রতিষ্ঠানের লোগো

```blade
@if ($organization?->logo)
    style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');"
@endif
```

- `$organization?->logo` logo থাকলে true।
- `asset(...)` public URL তৈরি করে।
- `--invoice-watermark` CSS custom property হিসেবে logo-এর URL পাঠায়।

---

## 6. প্রতিষ্ঠানের পরিচিতি

```blade
@if (
    $organization?->logo ||
    $organization?->name ||
    $organization?->address ||
    $organization?->phone ||
    $organization?->email)
```

এই শর্তে organization-এর যেকোনো একটি তথ্য থাকলেই header দেখানো হয়।

Organization-এর name, address, phone এবং email আলাদাভাবে render করা হয়েছে।
Phone ও email English label হিসেবে রাখা হয়েছে; তথ্য না থাকলে row তৈরি হবে না।

---

## 7. গ্রাহক ও ইনভয়েস পরিচিতি

```blade
{{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}
```

এখানে fallback hierarchy আছে:

1. Invoice snapshot-এর `customer_name`
2. Relationship-এর customer name
3. না থাকলে `Walk-in Customer`

Phone-এর fallback হলো `N/A`; address না থাকলে address block তৈরি হবে না।

Invoice date format:

```blade
{{ $invoice->invoice_date->format('d M Y') }}
```

উদাহরণ: `25 Nov 2026`

---

## 8. ইনভয়েসের পণ্য তালিকা

### পুনরাবৃত্তি

```blade
@foreach ($invoice->items as $key => $item)
```

- `$invoice->items`: Invoice-এর সম্পর্কিত সব sale item।
- `$key`: শুরুতে 0।
- `$item`: একটি করে invoice item।

Serial number দেখাতে `$key + 1` করা হয়েছে।

### ওয়ারেন্টি

```blade
@if ($item->warranty_cover)
```

Warranty true হলে year, month ও serial/MAC দেখানো হয়।

```blade
@if ((int) $item->warranty_year > 0)
```

Value-কে `(int)` দিয়ে integer-এ convert করা হয়েছে। 0-এর বেশি হলে 0-year text দেখাবে না।

### দাম, পরিমাণ ও মোট মূল্য

```blade
৳{{ number_format($item->unit_price, 2) }}
```

- `number_format(..., 2)` দুই দশমিক সংখ্যা দেখায়।
- বিক্রয়ের সময় unit price sale item-এ snapshot হিসেবে রাখা হয়েছে।
- `remaining_quantity` অবশিষ্ট বিক্রয় quantity বা void calculation অনুযায়ী হিসাবিত সংখ্যা।
- `remaining_line_total` সে line-এর remaining amount।

`৳` হলো বাংলাদেশি টাকার প্রতীক।

---

## 9. উপমোট ও ছাড়

```blade
@if ((float) $invoice->discount_amount > 0)
```

Discount amount string হলেও `(float)` করে numeric comparison করা হয়েছে।

Discount percentage হলে:

```blade
$invoice->discount_type === 'percentage'
```

Type percentage হলে discount value শতকরা হিসেবেও দেখানো হয়।

Percentage-এর trailing zeros সরানোর জন্য `rtrim(rtrim(...))` ব্যবহার করা হয়েছে।

---

## 10. VAT, AIT ও আগের বকেয়া

| Row | শর্ত |
|---|---|
| VAT | `vat_amount > 0` হলে |
| AIT | `ait_amount > 0` হলে |
| Previous Due | `include_previous_due == true` হলে |
| Outstanding | `due_amount > 0` হলে |

শূন্য row প্রিন্টে দেখানো হয় না, ফলে invoice পরিস্কার থাকে।

### পরিশোধের পদ্ধতি

```blade
{{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}
```

- Payment method থাকলে প্রথম letter বড় করে দেখায়।
- না থাকলে `Unpaid` দেখায়।

### টাকার পরিমাণ কথায়

```blade
<td colspan="4">{{ $amountInWords }}</td>
```

`$amountInWords` Controller বা View Composer থেকে পাঠানো text।
এটি সাধারণত invoice-এর মোট পরিমাণ কথায় দেখায়।

---

## 11. নোট ও শর্তাবলি

```blade
@if ($invoice->notes)
    <div class="mt-3"><strong>Notes:</strong> {{ $invoice->notes }}</div>
@endif
```

Invoice নোট থাকলে সেটি আলাদা row হিসেবে দেখানো হয়।

Terms & Conditions:

```blade
<div class="invoice-terms-content">{{ $termsConditions }}</div>
```

`$termsConditions` সম্ভাব্য organization বা system setting থেকে আসে।
এর নিচের CSS `white-space: pre-line` multiline text-এর line break ধরে রাখে।

---

## 12. স্বাক্ষরের জায়গা

```blade
<div class="pt-5 mt-4 row">
```

- Left column: Customer Signature
- Right column: Authorized Signature
- Authorized area-তে `Prepared by: ...` দেখায়

```blade
$quotation->user->name ?? 'Sales Officer'
```

`$quotation` relation-এর user না থাকলে default হিসেবে `Sales Officer` দেখায়।

---

## 13. CSS ও মুদ্রণের আচরণ

### শর্তাবলির CSS

```css
.invoice-terms-content {
    white-space: pre-line;
    line-height: 1.8;
}
```

- `pre-line`: নতুন লাইন ও একটি সাধারণ whitespace বজায় রাখে।
- `line-height: 1.8`: readable spacing দেয়।

### মুদ্রণের CSS

`@media print` কাগজে প্রিন্ট করার সময় শুধু চালু হয়।

নিচের অংশগুলো hide হয়:

- `header`
- `.topbar`
- `.sidebar-wrapper`
- `.no-print`
- `.page-footer`
- `.footer`

Table layout এবং background print adjustment সেট করে যাতে print-এ product details ঠিক অবস্থানে থাকে।

### জলছাপ

```css
.print-document.watermark-enabled::before
```

`::before` pseudoelement দিয়ে logo মাঝখানে হালকা পটভূমি হিসেবে বসে।
`position: fixed` পেজের নির্দিষ্ট স্থানে fixed overlay তৈরি করে।
`opacity: .09` watermark-কে খুব হালকা করে।
`pointer-events: none` যেন watermark মাউস বা print interaction বাধা না করে।

---

## 14. JavaScript-এর কাজ

### জলছাপ চালু বা বন্ধ

```javascript
document.getElementById('watermark-toggle')?.addEventListener('change', function() {
    document.getElementById('print-document')?.classList.toggle(
        'watermark-enabled',
        this.checked
    );
});
```

- Checkbox এ পরিবর্তন হলে event চালু হয়।
- Checkbox checked থাকলে class যোগ হয়।
- Unchecked হলে class মুছে যায়।
- `?.` element না থাকলেও JavaScript crash হওয়া আটকায়।

### কম মজুদের সতর্কবার্তা

```javascript
window.addEventListener('DOMContentLoaded', function() {
    window.Swal.fire({...});
});
```

- Page load সম্পূর্ণ হয়ে গেলে popup দেখায়।
- `@json(...)` PHP array/collection-কে JavaScript-এর নিরাপদ JSON string করে।
- SweetAlert `warning` icon ও low-stock product names দেখায়।

### স্বয়ংক্রিয় মুদ্রণ

Low stock popup না থাকলেও `auto_print` session true হলে print dialog খুলতে পারে।

```javascript
window.setTimeout(() => window.print(), 400);
```

400ms অপেক্ষা করার কারণ popup বা DOM layout-এ print-এর আগে সম্পূর্ণ render হতে দেওয়া।

---

## 15. গুরুত্বপূর্ণ Blade ধারণা

| Syntax | অর্থ |
|---|---|
| `{{ $value }}` | Escaped output |
| `{!! $value !!}` | Raw HTML output; এই ফাইলের plain text field-এ ব্যবহার করবে না |
| `@if ... @endif` | শর্ত অনুযায়ী HTML দেখানো |
| `@foreach ... @endforeach` | Array বা collection loop |
| `@can ... @endcan` | Permission থাকা লাগবে |
| `@canany([...]) ... @endcanany` | একাধিক permission-এর যেকোনো একটি |
| `@extends` | Parent layout উত্তরাধিকার |
| `@section` | Layout-এর named area পূরণ |
| `??` | Null হলে fallback value |
| `?->` | Null-safe property access |

---

## 16. এই ফাইলটি পড়ার সঠিক ক্রম

1. Blade layout directives
2. Permission-based action buttons
3. Organization header
4. Invoice এবং customer header
5. Items loop
6. Discount, VAT, AIT ও due rows
7. Notes, terms এবং signatures
8. Print CSS
9. JavaScript behavior

---

## 17. সম্পূর্ণ ব্যাখ্যাসহ কোড

দুইটি Blade view-এর সম্পূর্ণ বর্তমান কোড এই গাইডের ১৯ নম্বর অংশের পরিশিষ্টে দেওয়া আছে।
প্রতিটি গুরুত্বপূর্ণ অংশে শেখার সুবিধার জন্য বাংলা মন্তব্য যোগ করা হয়েছে।
কোডের মূল ফাইল হলো `salesInvoice.blade.php` এবং `deliveryChallan.blade.php`।

---

## 18. প্রিভিউ ছাড়া সরাসরি মুদ্রণ

Print Invoice এবং Print Delivery Challan—দুই button-ই একই Sales Invoice page-এর native `window.print()` dialog ব্যবহার করে।
Invoice button স্বাভাবিক Invoice document print করে।
Delivery Challan button একই page-এ থাকা Challan print area নির্বাচন করে; কোনো নতুন tab, browser window, modal বা preview খোলে না।

```blade
<button type="button" class="btn btn-primary"
    onclick="printDeliveryChallan()">
    <i class="bx bx-printer me-1"></i>Print Delivery Challan
</button>
```

Button click করলে JavaScript body-তে `print-delivery-challan` class যোগ করে:

```javascript
function printDeliveryChallan() {
    document.body.classList.add('print-delivery-challan');
    window.print();
}
```

এরপর সাধারণ browser print dialog খোলে—যেমন Print Invoice button-এ হয়।

### অতিরিক্ত ফাইল ছাড়াই Challan markup

আপনার অনুরোধ অনুযায়ী Challan markup দুইটি আগে থেকেই থাকা Blade view-তেই রাখা হয়েছে।
কোনো নতুন partial বা অতিরিক্ত source file যোগ করা হয়নি।
Sales Invoice page-এর screen mode-এ embedded Challan wrapper hidden থাকে:

```css
#delivery-challan-print-wrapper {
    display: none;
}
```

### Delivery Challan-এর address বিন্যাস

Invoice ও Challan—দুই header-এই Customer column `col-7` এবং invoice details `col-5`, ছবির মতো।
এতে Customer ও Address-এর জন্য বেশি horizontal space থাকে।
দুই Customer column-এই `invoice-customer-info` class আছে; Dashboard-এর print CSS তাই একই font size প্রয়োগ করে।
Name ও Phone row-এ `text-nowrap` থাকায় এগুলো এক লাইনেই থাকে।
Address row-এর markup Invoice-এর মতো রাখা হয়েছে; কোনো forced wrap বা manual line break নেই। পর্যাপ্ত জায়গা থাকলে এক লাইনে থাকবে, column-এর width ছাড়ালেই browser স্বাভাবিকভাবে wrap করবে।
দুই document-এই Customer ও right-side title `h6`, আর Challan print area-তেও `invoice-print-area` class আছে—তাই header ও print text sizing একইভাবে দেখায়।

### কোন অংশ মুদ্রিত হবে

```css
body.print-delivery-challan #print-document {
    display: none !important;
}

body.print-delivery-challan #delivery-challan-print-wrapper {
    display: block !important;
}
```

Print CSS চালু হলে:

- `print-delivery-challan` class না থাকলে Invoice print area স্বাভাবিকভাবে print হয়।
- Class থাকলে Invoice print area hide হয় এবং Challan area print হয়।
- এই class শুধু print dialog বন্ধ হওয়া পর্যন্ত থাকে।

```javascript
window.addEventListener('afterprint', function() {
    document.body.classList.remove('print-delivery-challan');
});
```

`afterprint` event print dialog বন্ধ হলে চলে। Class সরানোর ফলে পরেরবার Invoice button চাপলে Invoice-ই print হবে।

এই implementation-এর flow:

```text
Print Delivery Challan button
→ body-তে print-delivery-challan class যোগ
→ একই পেজে window.print()
→ Invoice আড়াল, Challan area দৃশ্যমান
→ Browser print dialog
```

Print Invoice flow আলাদা:

```text
Print Invoice button
→ একই পেজে window.print()
→ Invoice area print dialog-এ দেখানো
```

---

## 19. বর্তমান Blade source code

### ১৯.১ Sales Invoice view

```blade
@extends('dashboard.dashboard')
{{-- dashboard/dashboard.blade.php থেকে মূল HTML কাঠামো, Navbar ও Sidebar উত্তরাধিকার হিসেবে নেওয়া হয়েছে। --}}
@section('title', 'Invoice ' . $invoice->invoice_no)
{{-- ব্রাউজারের টাইটেলে বর্তমান ইনভয়েস নম্বর দেখানো হয়েছে। --}}

@section('content')
    {{-- এই অংশের HTML ও Blade logic শুরু হয়েছে। --}}
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                {{-- no-print ক্লাসযুক্ত বাটনগুলো প্রিন্ট করার সময় কাগজে দেখানো হবে না। --}}
                @canany(['sales.create', 'sales.view'])
                    {{-- ব্যবহারকারীর sales.create অথবা sales.view permission থাকলে New Sale লিংক দেখানো হয়। --}}
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>
                        Open Sale</a>
                @endcanany
                @if (auth()->user()->hasRole('admin') ||
                        auth()->user()->can('Update_anyday_Invoice') ||
                        (auth()->user()->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()))
                    {{-- Admin বা যথাযথ update permission থাকলে Update Invoice বাটন দেখানো হয়। আজকের ইনভয়েসের জন্য পৃথক permission আছে। --}}
                    <a href="{{ route('sales.invoice.edit', $invoice->id) }}" class="btn btn-outline-primary">
                        <i class="bx bx-edit me-1"></i>Update
                    </a>
                @endif
                @if (auth()->user()->can('Voide_anyday_Invoice') ||
                        (auth()->user()->can('Voide_today_Invoice') && $invoice->invoice_date?->isToday()))
                    {{-- Void permission ও ডেট শর্ত পূরণ হলে POST DELETE ফর্ম দেখানো হয়। confirm() ব্যবহারকারীকে আগে সতর্ক করে। --}}
                    <form action="{{ route('sales.destroy', $invoice->id) }}" method="POST"
                        onsubmit="return confirm('Delete this invoice? If it is linked to a sale voucher, the invoice will be voided and its stock, ledger, payment voucher amounts, and due balance will be reversed.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bx bx-trash me-1"></i>Void
                        </button>
                    </form>
                @endif
                @can('sales.document.watermark')
                    {{-- Watermark permission ও প্রতিষ্ঠানের logo থাকলে প্রিন্টের জন্য logo toggle তৈরি হয়। --}}
                    @if ($organization?->logo)
                        <label class="mb-0 btn btn-outline-secondary d-none">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1" checked>
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()"><i
                        class="bx bx-printer me-1"></i>Invoice</button>
                {{-- Button click করলে প্রিভিউ নয়, সরাসরি Delivery Challan-এর print dialog খুলবে। --}}
                <button type="button" class="btn btn-primary" onclick="printDeliveryChallan()">
                    <i class="bx bx-printer me-1"></i>Challan
                </button>
            </div>

            <div id="print-document"
                class="border-0 shadow-sm card print-document invoice-print-area @if (auth()->user()->can('sales.document.watermark') && $organization?->logo) watermark-enabled @endif"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                {{-- print-document হলো সম্পূর্ণ ইনভয়েসের মূল কন্টেন্ট। watermark CSS variable-এ organization logo বসেছে। --}}
                <div class="p-4 card-body p-md-5">
                    @if (
                        $organization?->logo ||
                            $organization?->name ||
                            $organization?->address ||
                            $organization?->phone ||
                            $organization?->email)
                        {{-- প্রতিষ্ঠানের তথ্য টেবিলের বাইরে রাখলে ইনভয়েসের প্রিন্ট হেডার পরিষ্কার থাকে। --}}
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

                    {{-- ইনভয়েসের পরিচিতি ও গ্রাহকের তথ্য আলাদা কলামে রাখলে প্রিন্টে দ্রুত পড়া যায়। --}}
                    {{-- <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7 invoice-header-details">
                            <h5 class="mb-1 text-primary fw-bold">Sales Invoice</h5>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-5 text-end invoice-customer-info">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div><i class="bx bx-user me-1 text-nowrap" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                    </div> --}}
                    {{-- ইনভয়েসের পরিচিতি ও গ্রাহকের তথ্য আলাদা কলামে রাখলে প্রিন্টে দ্রুত পড়া যায়। --}}
                    {{-- গ্রাহকের নাম, ফোন ও ঠিকানার fallback হিসেবে walk-in customer এবং N/A ব্যবহার করা হয়েছে। --}}
                    <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7 invoice-customer-info">
                            <h6 class="mb-1 fw-bold">Customer</h6>
                            <div class="text-nowrap"><i class="bx bx-user me-1 text-nowrap" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div><i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                        <div class="col-5 invoice-header-details text-end">
                            <h6 class="mb-1 text-primary fw-bold">Sales Invoice</h6>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                    </div>


                    <div class="table-responsive">
                        {{-- table-responsive ছোট স্ক্রিনে টেবিলকে অনুভূমিকভাবে স্ক্রল করতে দেয়। --}}
                        <table class="table align-middle table-bordered invoice-table">
                            {{-- নির্দিষ্ট প্রস্থ পণ্যের নামকে পাশের মূল্য কলামে ঢুকে পড়া থেকে আটকায়। --}}
                            <colgroup>
                                <col style="width: 5%;">
                                <col style="width: 40%;">
                                <col style="width: 3%">
                                <col style="width: 3%">
                                <col style="width: 5%">
                                <col style="width: 7%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Product</th>
                                    <th class="text-end fit-content-column">Price</th>
                                    <th class="text-center fit-content-column">QTY</th>
                                    <th class="text-center fit-content-column">Unit</th>
                                    <th class="text-end fit-content-column">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $key => $item)
                                    {{-- প্রতিটি ইনভয়েস আইটেম একটি করে table row তৈরি করছে। $key-কে +1 করে ধারাবাহিক ক্রম দেখানো হয়েছে। --}}
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td class="invoice-product-name text-wrap fit-content-column">
                                            {{ $item->product_name }}
                                            @if ($item->warranty_cover)
                                                {{-- warranty cover চালু থাকলে warranty year, month ও serial/MAC তথ্য আলাদা লাইনে দেখানো হয়। --}}
                                                <div class="mt-1 small text-muted text-nowrap">
                                                    Warranty:
                                                    @if ((int) $item->warranty_year > 0)
                                                        {{ $item->warranty_year }} Year(s)
                                                    @endif
                                                    @if ((int) ($item->warranty_months ?? 0) > 0)
                                                        {{ $item->warranty_months }} Month(s)
                                                    @endif
                                                    Serial/MAC: {{ $item->serial_mac }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- sale তৈরির সময় Product-এর selling price sale item-এ snapshot হয়ে invoice-এ দেখায়। --}}
                                        <td class="text-end">৳{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->remaining_quantity }}</td>
                                        <td class="text-center">
                                            {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
                                        </td>
                                        <td class="text-end">৳{{ number_format($item->remaining_line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <th colspan="5" class="text-end">Subtotal</th>
                                    <td class="text-end">৳{{ number_format($invoice->subtotal, 2) }}</td>
                                </tr>
                                {{-- ছাড় ও করের পরিমাণ শূন্য হলে অপ্রয়োজনীয় সারি প্রিন্টে দেখানো হয় না। --}}
                                @if ((float) $invoice->discount_amount > 0)
                                    {{-- ডিসকাউন্ট percentage হলে হিসাবের শতকরা মানও ডিসকাউন্ট লাইনের পাশে দেখানো হয়। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">
                                            Discount{{ $invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0 ? ' (' . rtrim(rtrim(number_format((float) $invoice->discount_value, 2), '0'), '.') . '%)' : '' }}
                                        </th>
                                        <td class="text-end">- ৳{{ number_format($invoice->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->vat_amount > 0)
                                    {{-- VAT Amount শূন্যের বেশি হলে এই row যোগ করা হয়েছে। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">VAT</th>
                                        <td class="text-end">৳{{ number_format($invoice->vat_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->ait_amount > 0)
                                    {{-- AIT Amount শূন্যের বেশি হলে এই row যোগ করা হয়েছে। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">AIT</th>
                                        <td class="text-end">৳{{ number_format($invoice->ait_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->include_previous_due)
                                    {{-- পূর্বের বকেয়া ইনভয়েসে যুক্ত করার option থাকলে previous_due row দেখানো হয়। --}}
                                    <tr>
                                        <th colspan="5" class="text-end">Previous Due</th>
                                        <td class="text-end">৳{{ number_format($invoice->previous_due, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class=" fs-5">
                                    <th colspan="5" class="text-end">Total</th>
                                    <td class="text-end">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Amount Paid</th>
                                    <td class="text-end">৳{{ number_format($invoice->amount_paid, 2) }}</td>
                                </tr>
                                @if ((float) $invoice->due_amount > 0)
                                    {{-- বকেয়া না থাকলে invoice-এ Due row দেখানোর প্রয়োজন নেই। --}}
                                    <tr class="fw-bold">
                                        <th colspan="5" class="text-end">Outstanding</th>
                                        <td class="text-end">৳{{ number_format($invoice->due_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="5" class="text-end">Payment Method</th>
                                    <td class="text-end">
                                        {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Unpaid' }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2">Amount in Words</th>
                                    <td colspan="4">{{ $amountInWords }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- নোট ও শর্তাবলি টেবিলের বাইরে রাখলে এগুলো পড়া সহজ হয়। --}}
                    @if ($invoice->notes)
                        {{-- বিক্রয়কালে সংরক্ষিত ব্যবহারকারী নোট থাকলে তা Invoices-এর শেষে দেখানো হয়। --}}
                        <div class="mt-3"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif

                    <div class="p-3 mt-4 border rounded invoice-terms">
                        <h6 class="mb-2 fw-bold text-dark">
                            <i class="bx bx-check-shield text-primary me-1"></i>Terms &amp; Conditions
                        </h6>
                        <!--শর্তাবলির লেখা বড় ও বামদিকে রেখে লাইনগুলোর মাঝে পড়ার উপযোগী ফাঁকা রাখা হয়।-->
                        <div class="invoice-terms-content">{{ $termsConditions }}</div>
                    </div>

                    <!-- স্বাক্ষরের অংশ -->
                    <!-- গ্রাহক ও প্রতিষ্ঠান উভয়ের স্বাক্ষরের জন্য fixed signature area তৈরি হয়েছে। -->
                    <div class="pt-5 mt-4 row">
                        <div class="col-6">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Customer Signature</div>
                                <div class="small text-muted">Signature & Date</div>
                            </div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Authorized Signature</div>
                                <div class="small text-muted">Prepared by: {{ $quotation->user->name ?? 'Sales Officer' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 mb-0 text-center text-muted small">Thank you for your business.</p>
                </div>
            </div>

            {{-- একই পেজে Challan print area রাখা হয়েছে, যেন আলাদা window ছাড়াই print করা যায়। --}}
            <div id="delivery-challan-print-wrapper">
                <div id="delivery-challan-print-document"
                    class="border-0 shadow-sm card print-document invoice-print-area @if ($organization?->logo) watermark-enabled @endif"
                    @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                    <div class="p-4 card-body p-md-5">
                        @if ($organization?->logo || $organization?->name)
                            {{-- প্রতিষ্ঠানের পরিচিতি থাকলে Challan-এর উপরে logo ও contact info দেখানো হয়। --}}
                            <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom invoice-organization">
                                @if ($organization?->logo)
                                    <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                        alt="{{ $organization->name ?: 'Organization logo' }}"
                                        style="max-width: 90px; max-height: 65px; object-fit: contain;">
                                @endif
                                <div>
                                    <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
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

                        {{-- Customer-এর তথ্য এবং Challan-এর invoice number/date একই header-এ দেখানো হয়। --}}
                        <div class="mb-4 row align-items-start invoice-header">
                            <div class="col-7 invoice-customer-info">
                                <h6 class="mb-1 fw-bold">Customer</h6>
                                <div class="text-nowrap"><i class="bx bx-user me-1" aria-hidden="true"></i>Name:
                                    {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}
                                </div>
                                <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                    {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                                @if ($invoice->customer_address ?? $invoice->customer?->address)
                                    <div>
                                        <i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                        {{ $invoice->customer_address ?? $invoice->customer?->address }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-5 invoice-header-details text-end">
                                <h6 class="mb-1 text-primary fw-bold">Delivery Challan</h6>
                                <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                                <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                            </div>
                        </div>

                        {{-- Challan-এ মূল্য বাদ দিয়ে শুধু পণ্য, পরিমাণ ও unit দেখানো হয়। --}}
                        <div class="table-responsive">
                            <table class="table align-middle table-bordered">
                                <thead>
                                    <tr>
                                        <th style="background-color: #14ADE0; color: #ffff;">#</th>
                                        <th style="background-color: #14ADE0; color: #ffff;">Product</th>
                                        <th class="text-center" style="background-color: #14ADE0; color: #ffff;">QTY</th>
                                        <th class="text-center" style="background-color: #14ADE0; color: #ffff;">UNIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $key => $item)
                                        {{-- প্রতিটি sale item-এর product, অবশিষ্ট quantity এবং unit দেখানো হয়। --}}
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->display_product_name }}</td>
                                            <td class="text-center">{{ $item->remaining_quantity }}</td>
                                            <td class="text-center">
                                                {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end">Total Quantity</td>
                                        <td class="text-center">{{ $invoice->items->sum('remaining_quantity') }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if ($invoice->notes)
                            {{-- Invoice-এ note থাকলে Challan-এও দেখানো হয়। --}}
                            <div class="mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                        @endif

                        {{-- পণ্য বুঝে নেওয়া ও অনুমোদনের জন্য দুইটি স্বাক্ষর স্থান রাখা হয়েছে। --}}
                        <div class="pt-5 mt-4 row">
                            <div class="col-6">
                                <div class="pt-2 text-center border-top w-75">
                                    <div class="fw-semibold text-dark">Customer Signature</div>
                                    <div class="small text-muted">Signature &amp; Date</div>
                                </div>
                            </div>
                            <div class="col-6 text-end d-flex justify-content-end">
                                <div class="pt-2 text-center border-top w-75">
                                    <div class="fw-semibold text-dark">Authorized Signature</div>
                                    <div class="small text-muted">Signature &amp; Date</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Blade-এর ভিতরের CSS মূলত print layout ও invoice terms formatting নিয়ন্ত্রণ করে। */
        .invoice-terms-content {
            font-size: 1rem;
            line-height: 1.8;
            text-align: left;
            white-space: pre-line;
        }

        /* Screen-এ embedded Challan গোপন থাকে; Print button চাপলে শুধু print layout-এ দেখানো হয়। */
        #delivery-challan-print-wrapper {
            display: none;
        }

        @media print {
            /* @media print ব্লক সক্রিয় হলে screen-এর sidebar, header ও action button কাগজে দেখাবে না। */

            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer {
                display: none !important;
            }

            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            /* নির্দিষ্ট টেবিল প্রস্থ প্রিন্টে পণ্যের কলামগুলোকে সঠিক জায়গায় রাখে। */
            .invoice-table {
                /* width: 100% !important; */
                table-layout: auto;
            }

            .invoice-table .fit-content-column {
                width: 1%;
                white-space: nowrap;
            }

            .invoice-table tr {
                break-inside: avoid;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-document {
                position: relative;
                isolation: isolate;
            }

            .print-document.watermark-enabled::before {
                /* pseudo-element-টি মাঝখানে হালকা logo watermark হিসেবে পেইজে বসাবে। */
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

            .print-document>.card-body {
                position: relative;
                z-index: 1;
            }

            /* Challan button থেকে print হলে Invoice আড়াল করে Challan area-টি print করা হয়। */
            body.print-delivery-challan #print-document {
                display: none !important;
            }

            body.print-delivery-challan #delivery-challan-print-wrapper {
                display: block !important;
            }
        }
    </style>
    @can('sales.document.watermark')
        @if ($organization?->logo)
            <script>
                // checkbox পরিবর্তন হলে print-document-এর উপর .watermark-enabled class যোগ বা বাদ হয়।
                document.getElementById('watermark-toggle')?.addEventListener('change', function() {
                    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
                });
            </script>
        @endif
    @endcan
    <script>
        // বর্তমান পেজে Challan mode চালু করে একই browser print dialog ব্যবহার করা হয়।
        function printDeliveryChallan() {
            document.body.classList.add('print-delivery-challan');
            window.print();
        }

        // Print dialog বন্ধ হলে পরেরবার Invoice স্বাভাবিকভাবে print করার জন্য Challan mode সরানো হয়।
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('print-delivery-challan');
        });
    </script>
    @if (session('low_stock_products'))
        <script>
            // low_stock_products session থাকলে DOM ready হওয়ার পর warning popup দেখাবে।
            window.addEventListener('DOMContentLoaded', function() {
                window.Swal.fire({
                    title: 'Low stock',
                    text: 'The following products now have 10 or fewer units: ' +
                        @json(implode(', ', session('low_stock_products'))),
                    icon: 'warning',
                }).then(function() {
                    @if (session('auto_print'))
                        // warning popup বন্ধ হওয়ার 400ms পরে স্বয়ংক্রিয় print dialog খুলবে।
                        window.setTimeout(() => window.print(), 400);
                    @endif
                });
            });
        </script>
    @elseif (session('auto_print'))
        <script>
            // low_stock warning না থাকলেও auto_print session থাকলে 400ms পর print dialog খুলবে।
            document.addEventListener('DOMContentLoaded', function() {
                window.setTimeout(() => window.print(), 400);
            });
        </script>
    @endif
@endsection
```

### ১৯.২ Delivery Challan view

```blade
@extends('dashboard.dashboard')
{{-- Print popup-এ শুধু Challan দেখা যাবে; সাধারণ Challan page-এ Dashboard layout থাকবে। --}}

@section('title', 'Delivery Challan ' . $invoice->invoice_no)
{{-- Popup window-এর browser title-এ Challan এবং Invoice number দেখানো হবে। --}}

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Sales Open
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Challan
                </button>
            </div>
            <div id="delivery-challan-print-document"
                class="border-0 shadow-sm card print-document invoice-print-area @if ($organization?->logo) watermark-enabled @endif"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                <div class="p-4 card-body p-md-5">
                    @if ($organization?->logo || $organization?->name)
                        {{-- প্রতিষ্ঠানের পরিচিতি থাকলে Challan-এর উপরে logo ও contact info দেখানো হয়। --}}
                        <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom invoice-organization">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}"
                                    style="max-width: 90px; max-height: 65px; object-fit: contain;">
                            @endif
                            <div>
                                <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
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

                    {{-- Customer-এর তথ্য এবং Challan-এর invoice number/date একই header-এ দেখানো হয়। --}}
                    <div class="mb-4 row align-items-start invoice-header">
                        <div class="col-7 invoice-customer-info">
                            <h6 class="mb-1 fw-bold">Customer</h6>
                            <div class="text-nowrap"><i class="bx bx-user me-1" aria-hidden="true"></i>Name:
                                {{ $invoice->customer_name ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</div>
                            <div class="text-nowrap"><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone:
                                {{ $invoice->customer_phone ?? ($invoice->customer?->phone ?? 'N/A') }}</div>
                            @if ($invoice->customer_address ?? $invoice->customer?->address)
                                <div>
                                    <i class="bx bx-map me-1 text-wrap" aria-hidden="true"></i>Address:
                                    {{ $invoice->customer_address ?? $invoice->customer?->address }}</div>
                            @endif
                        </div>
                        <div class="col-5 invoice-header-details text-end">
                            <h6 class="mb-1 text-primary fw-bold">Delivery Challan</h6>
                            <div class="text-muted">Invoice: <strong>{{ $invoice->invoice_no }}</strong></div>
                            <div class="text-muted">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                        </div>
                    </div>

                    {{-- Challan-এ মূল্য বাদ দিয়ে শুধু পণ্য, পরিমাণ ও unit দেখানো হয়। --}}
                    <div class="table-responsive">
                        <table class="table align-middle table-bordered">
                            <thead>
                                <tr>
                                    <th style="background-color: #14ADE0; color: #ffff;">#</th>
                                    <th style="background-color: #14ADE0; color: #ffff;">Product</th>
                                    <th class="text-center" style="background-color: #14ADE0; color: #ffff;">QTY</th>
                                    <th class="text-center" style="background-color: #14ADE0; color: #ffff;">UNIT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $key => $item)
                                    {{-- প্রতিটি sale item-এর product, অবশিষ্ট quantity এবং unit দেখানো হয়। --}}
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $item->display_product_name }}</td>
                                        <td class="text-center">{{ $item->remaining_quantity }}</td>
                                        <td class="text-center">
                                            {{ $item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td colspan="2" class="text-end">Total Quantity</td>
                                    <td class="text-center">{{ $invoice->items->sum('remaining_quantity') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($invoice->notes)
                        {{-- Invoice-এ note থাকলে Challan-এও দেখানো হয়। --}}
                        <div class="mt-4"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                    @endif

                    {{-- পণ্য বুঝে নেওয়া ও অনুমোদনের জন্য দুইটি স্বাক্ষর স্থান রাখা হয়েছে। --}}
                    <div class="pt-5 mt-4 row">
                        <div class="col-6">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Customer Signature</div>
                                <div class="small text-muted">Signature &amp; Date</div>
                            </div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Authorized Signature</div>
                                <div class="small text-muted">Signature &amp; Date</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @if (request()->boolean('print'))
            /* Print popup-এ preview না দেখিয়ে কেবল print dialog-ই সামনে আনা হয়। */
            @media screen {
                body {
                    visibility: hidden;
                }
            }

            /* Print media-তে Dashboard header, sidebar ও action area বাদ দিয়ে Challan-টি print হয়। */
            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer {
                display: none !important;
            }

            .page-wrapper,
            .page-content,
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .print-document {
                border: 0 !important;
                box-shadow: none !important;
            }
        @endif

        @media print {

            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer {
                display: none !important;
            }

            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

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

            .print-document>.card-body {
                position: relative;
                z-index: 1;
            }
        }
    </style>
    @if (request()->boolean('print'))
        <script>
            // ?print=1 থাকলে popup window load হওয়ার পর স্বয়ংক্রিয় print dialog খোলে।
            window.addEventListener('load', function() {
                window.setTimeout(function() {
                    // এটি popup window-এর নিজস্ব print() কল করে; মূল Sales Invoice পেজ প্রিন্ট করবে না।
                    window.print();
                }, 250);
            });
        </script>
    @endif
@endsection
```
