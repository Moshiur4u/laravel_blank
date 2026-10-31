# Sales Invoice ও Delivery Challan Watermark

## Controller (Code and explanation) —

`SaleController`-এ পরিবর্তন প্রয়োজন হয়নি। Invoice এবং challan view-তে organization তথ্য আগে থেকেই `AppServiceProvider`-এর view composer সরবরাহ করে; তাই controller-এ duplicate organization lookup যোগ করা হয়নি।

## Migration (Code and explanation) —

কোনো migration প্রয়োজন হয়নি। Watermark চালু/বন্ধের পছন্দটি বর্তমান document print session-এর checkbox-এ থাকে; database-এ আলাদা setting সংরক্ষণ করা হয় না।

## Model (Code and explanation) —

কোনো model পরিবর্তন প্রয়োজন হয়নি। Organization model-এর বিদ্যমান `logo` field-ই watermark image হিসেবে ব্যবহার করা হয়।

## Blade (Code and explanation) —

`resources/views/backend/Sales/salesInvoice.blade.php` এবং `resources/views/backend/Sales/deliveryChallan.blade.php`-এ `sales.document.watermark` permission ও organization logo থাকলেই “Print logo watermark” checkbox দেখায়। checkbox চালু করলে print-এর সময় organization logo হালকা opacity-তে document-এর পেছনে watermark হয়। Checkbox default-এ বন্ধ থাকে; permission বা logo না থাকলে সাধারণ print format-ই থাকে।

Sales Invoice-এর প্রতিষ্ঠানের পরিচিতি, invoice/customer details, product rows, subtotal/discount/VAT/AIT/previous due/total/payment, amount in words, notes, terms, signature ও footer—সব একই bordered table-এ রাখা হয়েছে। Delivery Challan-এর বিদ্যমান বিন্যাস অপরিবর্তিত; শুধু print watermark control যোগ হয়েছে।

```blade
@can('sales.document.watermark')
    @if ($organization?->logo)
        <input type="checkbox" id="watermark-toggle">
    @endif
@endcan
```

```css
.print-document.watermark-enabled::before {
    background-image: var(--invoice-watermark);
    opacity: .09;
}
```

## Seeder (Code and explanation) —

`database/seeders/PermissionSeederTable.php`-এ `sales.document.watermark` permission নিবন্ধিত হয়েছে। `database/seeders/RolePermissionSeeder.php`-এর manager preset-এ permission যোগ হয়েছে; admin preset permission list থেকে এটি পায়। `SalesDocumentWatermarkPermissionSeeder` permission তৈরি করে বিদ্যমান admin/manager role-কে এটি দেয়, অন্য permission সরায় না। `DatabaseSeeder`-এ এটি যুক্ত থাকায় standard seed-এর সময়ও permission বরাদ্দ হয়। অন্য role-এ permission নিজে থেকে দেওয়া হয় না, ফলে role/permission ব্যবস্থাপনায় তা প্রয়োজনমতো বরাদ্দ করা যায়।

## পরীক্ষা —

`tests/Feature/DeliveryChallanTest.php`-এ permission থাকা user-এর invoice watermark option এবং একটিমাত্র invoice table যাচাই করা হয়েছে। Logo থাকা সত্ত্বেও permission ছাড়া challan-এ watermark option না দেখানোর assertion-ও যোগ হয়েছে।
