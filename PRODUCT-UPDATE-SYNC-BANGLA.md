# প্রডাক্ট আপডেট সিঙ্ক — বাংলা নির্দেশিকা
## (Product Update → Supplier Ledger + Inventory Sync)

## ভূমিকা ও সমস্যা

পূর্বে `ProductController::update()` শুধুমাত্র `products` টেবিল আপডেট করত।
ফলে পণ্যের **দাম (purchase_price)**, **পরিমাণ (unit)**, বা **সাপ্লায়ার (supplier_id)** পরিবর্তন করলে:

| মডিউল | পূর্বের অবস্থা | সমস্যা |
|---|---|---|
| `products` টেবিল | আপডেট হতো | — |
| Inventory Report (`products.unit`) | আপডেট হতো | — |
| Supplier Ledger (`supplierledgers`) | আপডেট হতো না | পুরনো দাম/পরিমাণ দেখাত |
| Inventory Report (ক্রয় পরিমাণ/মোট) | আপডেট হতো না | Ledger থেকে পুরনো তথ্য আসত |

---

## ১. Controller (কোড ও ব্যাখ্যা)

**ফাইল পাথ:** `app/Http/Controllers/ProductController.php`

### সংশোধিত `update(Request $request, $id)` মেথড

```php
public function update(Request $request, $id)
{
    // ১. Validation (অপরিবর্তিত)
    $request->validate([...]);

    // ২. Product খুঁজে বের করা
    $product = Product::findOrFail($id);

    // ৩. Image upload (transaction-এর বাইরে)
    $productImageName = $product->img_url;
    if ($request->hasFile('imageName')) { ... }

    // ৪. DB::transaction — product + supplier ledger একসঙ্গে আপডেট
    DB::transaction(function () use ($request, $product, $productImageName) {

        // ৪.১ products টেবিল আপডেট (stock/unit এখান থেকেই আসে)
        $product->update([
            'productName', 'product_categorie_id', 'brand_id',
            'supplier_id', 'purchase_price', 'price', 'unit',
            'purchase_date', 'mfg_date', 'expiry_date',
            'description', 'img_url'
        ]);

        // ৪.২ Supplier Ledger সমন্বয়
        if ($newSupplierId && $newPurchasePrice) {
            $newTotal = round($newPurchasePrice * $newUnit, 2);

            // সবচেয়ে পুরনো ledger entry (product তৈরির সময়ের)
            $existingLedger = Supplierledger::where('product_id', $product->id)
                ->oldest()->first();

            if ($existingLedger) {
                $paidAmount = (float) $existingLedger->payment;
                $newDue     = max(0.0, $newTotal - $paidAmount);
                $existingLedger->update([
                    'supplier_id', 'product_id', 'quantity', 'price',
                    'total', 'due'  // payment অপরিবর্তিত
                ]);
            } else {
                Supplierledger::create([
                    ..., 'payment' => '0.00', ...
                ]);
            }
        }
    });

    return redirect()->route('product.index')
        ->with('success', 'Product Updated Successfully');
}
```

### বিস্তারিত ব্যাখ্যা

#### কেন `DB::transaction` ব্যবহার করা হয়েছে?
`DB::transaction` নিশ্চিত করে যে `products` ও `supplierledgers` টেবিল
**একসঙ্গে সফল অথবা একসঙ্গে ব্যর্থ** হবে। যদি ledger আপডেটে কোনো সমস্যা হয়,
তাহলে product-এর পরিবর্তনও rollback হয়ে যাবে।

#### কেন ইমেজ transaction-এর বাইরে?
ফাইল সিস্টেম অপারেশন (`unlink`, `move`) database rollback-এ পূর্বাবস্থায় ফেরানো যায় না।
তাই ইমেজ transaction শুরুর আগেই সম্পন্ন করা হয়।

#### `payment` কেন অপরিবর্তিত?
সাপ্লায়ার আগে যে টাকা পরিশোধ করেছেন তা মুছে দেওয়া ঠিক হবে না।
তাই শুধু `due = new_total - payment` পুনরায় হিসাব করা হয়।

#### `oldest()` কেন ব্যবহার করা হয়েছে?
`store()` মেথড প্রথমবার product তৈরির সময় একটি ledger entry তৈরি করে।
পরবর্তীতে ম্যানুয়ালি আরো entry যোগ হতে পারে।
`oldest()` নিশ্চিত করে শুধু **প্রথম (product-creation)** entry-ই আপডেট হবে।

#### `max(0.0, ...)` কেন?
যদি আগে বেশি পেমেন্ট হয়ে থাকে এবং নতুন total কমে যায়,
তাহলে due কখনো ঋণাত্মক হবে না — শূন্যতেই থামবে।

---

## ২. Migration (মাইগ্রেশন)

**কোনো নতুন migration তৈরির প্রয়োজন নেই।**

এই পরিবর্তনটি সম্পূর্ণরূপে কন্ট্রোলার লজিকের মাধ্যমে বিদ্যমান টেবিলে কাজ করে:

| টেবিল | ব্যবহৃত কলামসমূহ |
|---|---|
| `products` | `productName`, `unit`, `price`, `purchase_price`, `supplier_id` ইত্যাদি |
| `supplierledgers` | `supplier_id`, `product_id`, `quantity`, `price`, `total`, `payment`, `due` |

---

## ৩. Blade Template

**কোনো Blade পরিবর্তন নেই।**

`editProduct.blade.php`-এ ইতোমধ্যে `supplier_id`, `purchase_price`, ও `unit` ফিল্ড রয়েছে।
ফর্ম submit-এর পর controller স্বয়ংক্রিয়ভাবে `supplierledgers` আপডেট করে।

---

## ৪. Sync Flow — সমন্বয় প্রবাহ

```
ব্যবহারকারী Edit Product ফর্ম Submit
        |
        v
ProductController::update()
        |
        |-- Image upload (transaction-এর বাইরে)
        |
        `-- DB::transaction শুরু
                |
                |-- products টেবিল আপডেট
                |     (productName, unit, price, supplier_id ইত্যাদি)
                |
                `-- supplierledgers sync
                      |
                      |-- supplier_id এবং purchase_price আছে?
                      |     |-- হ্যাঁ => oldest() ledger খোঁজা
                      |     |         |-- পাওয়া গেলে => আপডেট
                      |     |         |   (qty, price, total, due -- payment অপরিবর্তিত)
                      |     |         `-- না পাওয়া গেলে => নতুন entry
                      |     `-- না => ledger অপরিবর্তিত
                      |
                      `-- DB::transaction সম্পন্ন
```

---

## ৫. কোন মডিউল কোথা থেকে ডেটা পড়ে?

| মডিউল | ডেটা উৎস | আপডেট হয়? |
|---|---|---|
| **Product List** | `products` টেবিল | হ্যাঁ |
| **Inventory — Current Stock** | `products.unit` | হ্যাঁ |
| **Inventory — Purchase Qty/Amount** | `supplierledgers.quantity` / `.total` | হ্যাঁ (সংশোধনের পর) |
| **Supplier Ledger** | `supplierledgers` টেবিল | হ্যাঁ (সংশোধনের পর) |
| **Supplier Ledger — Payment History** | `supplier_ledger_payments` | অপরিবর্তিত, পূর্ণ ইতিহাস থাকে |
| **Dashboard Purchase Total** | `supplierledgers.total` | হ্যাঁ |

---

## ৬. সারসংক্ষেপ

**পরিবর্তিত ফাইল:** শুধু `app/Http/Controllers/ProductController.php`

**ফলাফল:** প্রডাক্ট এডিট করার পর নিচের সবকিছু একযোগে আপডেট হয়:
- Products টেবিল (productName, price, unit ইত্যাদি)
- Inventory Report এ স্টক (unit থেকে)
- Inventory Report এ ক্রয় পরিমাণ ও মোট (supplierledger থেকে)
- Supplier Ledger এ দাম, পরিমাণ, মোট ও বাকি (due)
- Supplier পরিবর্তন হলে নতুন supplier-এর অধীনে চলে যায়
- পরিশোধিত অর্থ (payment) সম্পূর্ণ সংরক্ষিত থাকে
