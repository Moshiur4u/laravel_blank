# Supplier Payment History - শেখার নোট

এই পরিবর্তনে Supplier Ledger History থেকে employee প্রতিটি ledger entry-র জন্য payment date ও amount জমা দিতে পারে। প্রতিটি কিস্তি আলাদা database record হয়; একই সঙ্গে ledger-এর মোট payment ও due update হয়।

## কাজের প্রবাহ

1. **Payment History table:** `supplier_ledger_payments` migration-এ ledger entry-র ID, payment date, amount ও timestamps রাখা হয়। Ledger মুছে গেলে তার payment history foreign-key cascade দিয়ে পরিষ্কার হয়।
2. **Payment Model:** `SupplierLedgerPayment` model তারিখকে date type এবং amount-কে দুই decimal-এর money string হিসেবে cast করে। `Supplierledger::payments()` একটি ledger-এর payment-গুলো নতুন তারিখ আগে রেখে আনে।
3. **Ledger History form:** প্রতিটি due থাকা row-তে Payment details control আছে। Employee date ও amount দিয়ে POST করে। Due না থাকলে payment action দেখানো হয় না।
4. **Validation:** controller valid date ও শূন্যের বেশি amount চায়; ভবিষ্যতের date এবং বর্তমান due-এর চেয়ে বেশি amount প্রত্যাখ্যান করে। Browser-এর `max` সুবিধার পাশাপাশি server-এও due যাচাই হয়।
5. **Concurrency ও transaction:** controller ledger row lock করে payment history record যোগ এবং aggregate `payment`/`due` এক transaction-এ update করে। একই সময়ে একাধিক payment এলে stale due দিয়ে overpay হওয়া ঠেকে; database save ব্যর্থ হলে সব পরিবর্তন rollback হয়।
6. **Money arithmetic:** amount ও due-কে cents-এ রূপান্তর করে যোগ/বিয়োগ করা হয়, তারপর দুই decimal string হিসেবে legacy ledger columns-এ লেখা হয়।
7. **History display:** Ledger History-তে payment amount-এর নিচে তারিখ ও অঙ্কের তালিকা খোলা যায়। Supplier invoice-তেও প্রতিটি payment date ও amount দেখায়।
8. **Ledger edit নিরাপত্তা:** edit form-এ payment read-only; ledger তৈরির পরের payment কেবল payment entry দিয়ে আসে। Ledger total কমিয়ে আগে দেওয়া payment-এর চেয়ে কম করা যাবে না, যাতে due ঋণাত্মক না হয়।

## পুরোনো payment সম্পর্কে

Feature চালুর আগের ledger payment aggregate আগের মতোই থাকে। ঐতিহাসিক payment-এর আলাদা date/amount record আগে থেকে না থাকলে সেগুলোর date অনুমান করা হয় না; নতুন payment-এর পর থেকে history লেখা শুরু হয়। Ledger তৈরির সময় আগে থেকে দেওয়া opening payment-ও পুরোনো নিয়মে aggregate-এ থাকে, কারণ তার জন্য আলাদা payment date চাওয়া হয় না। নতুন কোনো payment method বা unrelated ledger/supplier/product তথ্য যোগ বা পরিবর্তন করা হয়নি।

## পরিবর্তিত ফাইল

- `database/migrations/2026_10_12_163000_create_supplier_ledger_payments_table.php`
- `app/Models/SupplierLedgerPayment.php`
- `app/Models/Supplierledger.php`
- `app/Http/Controllers/SupplierledgerController.php`
- `routes/web.php`
- `resources/views/backend/Supplier/supplierledger.blade.php`
- `resources/views/backend/Supplier/supplierInvoice.blade.php`
- `SUPPLIER-PAYMENT-GUIDE.md`

Schema যোগ করতে deployment-এ `php artisan migrate` চালাতে হবে।
