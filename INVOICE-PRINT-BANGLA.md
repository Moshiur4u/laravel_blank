# Invoice Print Font — বাংলা ব্যাখ্যা

Invoice-এর print area-র লেখা print করার সময় সাধারণভাবে 10px করা হয়েছে। Sales Invoice-এর title, invoice number, date এবং customer information print-এ default `1rem` font size-এ থাকে, যাতে header ও customer details সহজে পড়া যায়। সাধারণ screen view-এর font size অপরিবর্তিত আছে।

Paid sales invoice-এ outstanding due `0` হলে এখন `Due` row দেখানো হয় না। আগে row-টি সব invoice-এ দেখানো হতো, ফলে পুরো টাকা পরিশোধ করা হলেও `৳0.00` দেখা যেত। বকেয়া থাকলে আগের মতোই invoice-এর বর্তমান `due_amount` দেখায়; amount calculation বা payment behavior পরিবর্তিত হয়নি।

## Controller (Code and Explanation)

কোনো controller পরিবর্তন করা হয়নি। Print font-size সম্পূর্ণভাবে Blade-এর print CSS দিয়ে নিয়ন্ত্রিত।

## Migration (Code and Explanation)

কোনো database schema পরিবর্তন বা migration যোগ করা হয়নি।

## Route (Code and Explanation)

কোনো route পরিবর্তন করা হয়নি।

## Model (Code and Explanation)

কোনো model পরিবর্তন করা হয়নি।

## Blade Template (Code and Explanation)

### Print CSS

**ফাইল:** [dashboard.blade.php](./resources/views/dashboard/dashboard.blade.php)

একটি shared print rule শুধু `.invoice-print-area` এবং তার ভেতরের element-এ প্রযোজ্য। `!important` ব্যবহার করায় Bootstrap-এর heading ও utility class-ও print-এ নির্দিষ্ট 10px মেনে চলে। Sales Invoice-এর header ও customer information-এ `invoice-header-details` এবং `invoice-customer-info` class আছে; print stylesheet-এ এই দুই অংশের font size `1rem` করে override করা হয়।

```css
@media print {
    .invoice-print-area,
    .invoice-print-area * {
        font-size: 10px !important;
    }

    .invoice-header-details,
    .invoice-header-details *,
    .invoice-customer-info,
    .invoice-customer-info * {
        font-size: 1rem !important;
    }
}
```

### Sales Invoice-এর due row

**ফাইল:** [salesInvoice.blade.php](./resources/views/backend/Sales/salesInvoice.blade.php)

Invoice-এর `due_amount` শূন্যের বেশি হলেই শুধু print-এ `Due` row দেখানো হয়। পুরো টাকা পরিশোধ করা invoice-এ `৳0.00`-এর Due row দেখানো হয় না। বকেয়া থাকলে invoice-এর সংরক্ষিত due amount আগের মতোই দেখায়; হিসাব বা payment behavior পরিবর্তিত হয়নি।

### Invoice print area চিহ্নিত করা

নিচের Blade view-গুলোতে printable invoice container-এ `invoice-print-area` class যোগ হয়েছে:

- [salesInvoice.blade.php](./resources/views/backend/Sales/salesInvoice.blade.php) — Sales Invoice
- [blankInvoice.blade.php](./resources/views/backend/Sales/blankInvoice.blade.php) — Blank Sales Invoice
- [supplierInvoice.blade.php](./resources/views/backend/Supplier/supplierInvoice.blade.php) — Supplier Invoice
- [customerLedgerInvoice.blade.php](./resources/views/backend/customer/customerLedgerInvoice.blade.php) — Customer Invoice/Payment Receipt

```blade
<div class="border-0 shadow-sm card invoice-print-area">
    {{-- Printable invoice content --}}
</div>
```

Class-টি print document-এর wrapper-এ যোগ করায় screen styling বদলায় না; print preview-তে সাধারণ লেখা 10px হয় এবং Sales Invoice-এর header ও customer information default `1rem` থাকে।
