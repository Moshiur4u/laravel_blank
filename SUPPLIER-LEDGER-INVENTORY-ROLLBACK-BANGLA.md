# Supplier Ledger Delete এবং Inventory Rollback

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SupplierledgerController.php`

```php
$ledger = Supplierledger::query()->with('payments')->lockForUpdate()->findOrFail($id);
if ($ledger->purchaseReturns()->exists()) {
    throw ValidationException::withMessages([
        'supplierledger_id' => 'This purchase ledger entry cannot be deleted because purchase return records are linked to it.',
    ]);
}

$purchaseQuantity = (float) $ledger->quantity;
if ($ledger->product_id && $purchaseQuantity > 0) {
    $product = Product::query()->whereKey($ledger->product_id)->lockForUpdate()->first();
    if ($product) {
        $currentStock = (float) $product->unit;
        if ($currentStock + 0.000001 < $purchaseQuantity) {
            throw ValidationException::withMessages([
                'supplierledger_id' => 'This purchase cannot be deleted because some of its stock has already been sold or consumed.',
            ]);
        }

        $remainingStock = $currentStock - $purchaseQuantity;
        $product->unit = abs($remainingStock) < 0.000001 ? '0' : (string) $remainingStock;
        $product->save();
    }
}
```

Ledger delete transaction-এর ভেতর product row lock করে ledger-এর ক্রয় quantity বর্তমান stock থেকে বাদ দেওয়া হয়। মজুত যথেষ্ট না থাকলে transaction বাতিল হয়, যাতে stock ঋণাত্মক না হয় বা ledger/voucher আংশিকভাবে মুছে না যায়। Purchase return-যুক্ত ledger-এর আগের delete restriction অপরিবর্তিত। এরপর আগের মতোই সংশ্লিষ্ট payment voucher ও ledger মুছে যায়।

## Migration (Code and Explanation)

কোনো migration প্রয়োজন হয়নি। `supplierledgers.product_id`, `supplierledgers.quantity` এবং `products.unit` বিদ্যমান কলাম ব্যবহার করা হয়েছে।

## Route (Code and Explanation)

কোনো route পরিবর্তন হয়নি। বিদ্যমান `supplier.ledger.destroy` route একই `SupplierledgerController::destroy()` method-এ যায় এবং আগের permission বজায় রাখে।

## Model (Code and Explanation)

কোনো model পরিবর্তন হয়নি। বিদ্যমান `Supplierledger`-এর product association-এর `product_id` দিয়ে `Product` খুঁজে stock update করা হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Supplier/supplierledger.blade.php`

```blade
data-delete-message="Supplier ledger entry: {{ $entry->invoice_id }}. Deleting it will remove {{ $entry->quantity }} from product inventory."
```

Delete confirmation-এ ledger মুছলে সংশ্লিষ্ট inventory থেকে কত quantity বাদ যাবে তা দেখানো হয়। Stock শূন্য হলে Product List-এর বিদ্যমান positive-stock filter-এর কারণে পণ্যটি তালিকা থেকে সরে যায়।

এই আচরণের regression test `tests/Feature/SupplierPaymentVoucherTest.php`-এ যোগ হয়েছে: একটি test stock শূন্য হওয়া ও Product List থেকে product বাদ পড়া যাচাই করে, আরেকটি test stock অপর্যাপ্ত হলে deletion বাতিল হওয়া যাচাই করে।