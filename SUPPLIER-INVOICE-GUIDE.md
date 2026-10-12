# Supplier Invoice - শেখার নোট

এই পরিবর্তনে প্রতিটি Supplier Ledger row থেকে আলাদা প্রিন্টযোগ্য invoice দেখানো হয়। নতুন কোনো database field, migration বা ledger calculation যোগ করা হয়নি; invoice-এ বিদ্যমান row-র মানই দেখানো হয়।

## কাজের প্রবাহ

1. **Ledger তালিকা:** `resources/views/backend/Supplier/supplierledger.blade.php`-এর প্রতিটি row-তে printer icon যোগ করা হয়েছে। এটি সেই row-র ID দিয়ে invoice route-এ যায়।
2. **Route:** `routes/web.php`-এ `supplier.ledger.invoice` নামের authenticated GET route যোগ হয়েছে। GET request শুধু invoice দেখায়, কোনো ledger তথ্য save বা update করে না।
3. **Controller:** `SupplierledgerController::invoice($id)` ID দিয়ে একটি `Supplierledger` খুঁজে আনে। `with(['supplier', 'product'])` ব্যবহার করে supplier ও product সম্পর্ক একসঙ্গে load করা হয়; ID না থাকলে Laravel-এর `findOrFail` স্বাভাবিক 404 response দেয়।
4. **Invoice view:** `resources/views/backend/Supplier/supplierInvoice.blade.php`-এ supplier-এর নাম, logo, ঠিকানা, email, phone এবং সংশ্লিষ্ট entry-র product, quantity, unit price, total, amount paid ও due দেখানো হয়।
5. **Logo fallback:** logo আপলোড করা না থাকলে supplier নামের প্রথম দুই অক্ষর দেখানো হয়। Logo-র পথ আগের supplier profile-এর মতো `public/uploads/suppliers/`।
6. **Print:** `Print Invoice` বাটন browser-এর `window.print()` চালায়। Print stylesheet dashboard navigation ও button লুকিয়ে invoice-টুকু কাগজে রাখে।
7. **Invoice number:** ledger-এ `invoice_id` থাকলে সেটিই দেখায়; না থাকলে `SUP-` prefix-সহ ledger ID-র ভিত্তিতে স্থায়ী number দেখায়। এটি শুধু view-তে তৈরি হয়, database-এ কিছু লেখা বা বদলানো হয় না।

## Payment তথ্য

Supplier ledger model-এ payment amount ও due আছে, কিন্তু payment method field নেই। তাই customer invoice-এর মতো `Amount Paid` ও `Due` দেখানো হয়; নতুন কোনো payment method অনুমান করে যোগ করা হয়নি।

## কোথায় পরিবর্তন হয়েছে

- `routes/web.php`
- `app/Http/Controllers/SupplierledgerController.php`
- `resources/views/backend/Supplier/supplierledger.blade.php`
- `resources/views/backend/Supplier/supplierInvoice.blade.php`
- `SUPPLIER-INVOICE-GUIDE.md`
