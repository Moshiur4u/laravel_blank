# Delivery Challan পরিবর্তনের নথি

## Controller (code and explanation)----

`app/Http/Controllers/SaleController.php`-এর `deliveryChallan()` action invoice, তার items, product unit, customer ও employee load করে printable Delivery Challan view দেখায়। এটি invoice বা sale data পরিবর্তন করে না।

```php
public function deliveryChallan($id)
{
    $invoice = SaleInvoice::with(['items.product', 'customer', 'employee'])->findOrFail($id);

    return view('backend.Sales.deliveryChallan', compact('invoice'));
}
```

`routes/web.php`-এ `sales.delivery-challan` route শুধু authentication এবং `sales.view` অথবা `sales.report` permission-সহ চালু।

`app/Providers/AppServiceProvider.php`-এর organization view composer-এ `backend.Sales.deliveryChallan` অন্তর্ভুক্ত করা হয়েছে। এতে Challan view render হওয়ার সময় organization তথ্য `$organization` variable-এ পাওয়া যায়; layout render হওয়ার আগে child view-এ undefined variable error হয় না।

## Migration (code and explanation)----

এই feature-এর জন্য নতুন migration লাগেনি। বিদ্যমান sale invoice, sale item ও product data-ই ব্যবহার করা হয়েছে।

## Model (code and explanation)----

কোনো model পরিবর্তন করা হয়নি। বিদ্যমান `SaleInvoice`, `SaleItem` ও `Product` সম্পর্ক দিয়ে challan-এর invoice items এবং `stock_unit` দেখানো হয়।

## Blade (code and explanation)----

`resources/views/backend/Sales/salesIndex.blade.php`-এর Recent Invoices row-তে Delivery Challan button যোগ হয়েছে। `resources/views/backend/Sales/deliveryChallan.blade.php` invoice print করার page; এতে product, quantity, unit, invoice/customer details, note, signature এবং table-এর শেষ row-তে total quantity থাকে। Product-এর `stock_unit` না থাকলে “Unit” দেখানো হয়। Sale price, amount, discount, tax, paid/due values challan-এ দেখানো হয় না।

```blade
<th>Product</th>
<th class="text-center">QTY</th>
<th class="text-center">UNIT</th>
```

## পরীক্ষা

`tests/Feature/DeliveryChallanTest.php` sales viewer-এর route access, product/quantity/unit, total quantity row এবং financial columns না থাকার বিষয়টি যাচাই করে।
