# Sales Invoice ও Delivery Challan Watermark

## Controller (Code and explanation) —

`SaleController`-এ পরিবর্তন প্রয়োজন হয়নি। Invoice এবং challan view-তে organization তথ্য আগে থেকেই `AppServiceProvider`-এর view composer সরবরাহ করে; তাই controller-এ duplicate organization lookup যোগ করা হয়নি।

## Migration (Code and Explanation) —

কোনো migration প্রয়োজন হয়নি। Watermark চালু/বন্ধের পছন্দটি বর্তমান document print session-এর checkbox-এ থাকে; database-এ আলাদা setting সংরক্ষণ করা হয় না।

## Route (Code and Explanation) —

এই পরিবর্তনের জন্য কোনো route যোগ বা পরিবর্তন হয়নি। এটি বিদ্যমান sales invoice print page-এর Blade checkbox ও CSS class-এর আচরণ।

## Model (Code and Explanation) —

কোনো model পরিবর্তন প্রয়োজন হয়নি। Organization model-এর বিদ্যমান `logo` field-ই watermark image হিসেবে ব্যবহার করা হয়।

## Blade Template (Code and Explanation) —

`resources/views/backend/Sales/salesInvoice.blade.php`-এ `sales.document.watermark` permission ও organization logo থাকলেই “Print logo watermark” checkbox দেখায়। Checkbox এখন default-এ checked থাকে এবং document-এ `watermark-enabled` class শুরু থেকেই যুক্ত হয়। ফলে invoice খুলেই print করলে logo watermark থাকে; ব্যবহারকারী checkbox uncheck করলে JavaScript class সরিয়ে watermark বন্ধ করে। Permission বা logo না থাকলে checkbox এবং default watermark—দুটিই থাকে না।

```blade
<input type="checkbox" id="watermark-toggle" class="form-check-input me-1" checked>

<div id="print-document"
    class="border-0 shadow-sm card print-document invoice-print-area @if (auth()->user()->can('sales.document.watermark') && $organization?->logo) watermark-enabled @endif">
```

Checkbox-এর বিদ্যমান change handler ব্যবহারকারীর পছন্দ অনুযায়ী watermark class যোগ/সরায়:

```javascript
document.getElementById('watermark-toggle')?.addEventListener('change', function() {
    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
});
```

Watermark-এর opacity ও print styling আগের মতোই থাকে:

```css
.print-document.watermark-enabled::before {
    background-image: var(--invoice-watermark);
    opacity: .09;
}
```

`resources/views/backend/Sales/deliveryChallan.blade.php`-এর watermark control এই পরিবর্তনের অংশ নয় এবং অপরিবর্তিত রয়েছে।

## Seeder (Code and explanation) —

`database/seeders/PermissionSeederTable.php`-এ `sales.document.watermark` permission নিবন্ধিত হয়েছে। `database/seeders/RolePermissionSeeder.php`-এর manager preset-এ permission যোগ হয়েছে; admin preset permission list থেকে এটি পায়। `SalesDocumentWatermarkPermissionSeeder` permission তৈরি করে বিদ্যমান admin/manager role-কে এটি দেয়, অন্য permission সরায় না। `DatabaseSeeder`-এ এটি যুক্ত থাকায় standard seed-এর সময়ও permission বরাদ্দ হয়। অন্য role-এ permission নিজে থেকে দেওয়া হয় না, ফলে role/permission ব্যবস্থাপনায় তা প্রয়োজনমতো বরাদ্দ করা যায়।

## পরীক্ষা —

`tests/Feature/DeliveryChallanTest.php`-এ permission থাকা user-এর invoice watermark option default checked এবং invoice-এ `watermark-enabled` class সক্রিয়—দুটিই যাচাই করা হয়েছে। Logo থাকা সত্ত্বেও permission ছাড়া challan-এ watermark option না দেখানোর assertion-ও আছে।
