# কাস্টমার লেজার: ফিচার ও কোড নির্দেশিকা

এই নথিতে customer ledger-এর route, controller logic, model relationship, database migration এবং Blade view-এর কাজ ব্যাখ্যা করা হয়েছে। Supplier ledger-এর মতো sale edit, due collection, payment history, invoice/receipt print এবং permission-ভিত্তিক delete যোগ হয়েছে; customer-এর বিদ্যমান sale ও standalone payment entry-র ধরন অক্ষুণ্ণ আছে।

## Sale Return-এর পর Product column-এ item breakdown

### Controller (Code and Explanation)

ফাইল: [`app/Http/Controllers/CustomerController.php`](./app/Http/Controllers/CustomerController.php), `ledger($id)` method।

```php
$query->with([
    'receiver',
    'employee',
    'payments.receiver',
    'saleItems.product',
    'saleItems.returns',
])->latest();
```

Ledger page খোলার সময় sale invoice-এর পৃথক item, product display name এবং প্রতিটি item-এর return records একসঙ্গে eager-load হয়। এতে প্রতিটি ledger row-এর জন্য আলাদা database query না করে পণ্যের নাম ও ফেরত quantity দেখানো যায়।

### Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান `sale_items` ও `product_returns` টেবিলের invoice/item সম্পর্ক ব্যবহার করা হয়েছে।

### Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি। আগের `customer.ledger` route-ই ledger page দেখায়।

### Model (Code and Explanation)

ফাইল: [`app/Models/CustomerLedger.php`](./app/Models/CustomerLedger.php)।

```php
public function saleItems(): HasMany
{
    return $this->hasMany(SaleItem::class, 'sale_invoice_id', 'sale_invoice_id');
}
```

এই relation customer ledger-এর invoice ID দিয়ে সেই invoice-এর আলাদা sale item-গুলো আনে। প্রতিটি `SaleItem`-এর `product()` ও `returns()` relation আগে থেকেই আছে; তাই পণ্যের বর্তমান display name এবং ওই item-এর মোট ফেরত quantity পাওয়া যায়।

### Blade Template (Code and Explanation)

ফাইল: [`resources/views/backend/customer/customerLedger.blade.php`](./resources/views/backend/customer/customerLedger.blade.php), ledger table-এর Entry / Product cell।

```blade
<th>Entry / Product</th>
<td class="customer-ledger-product">
    @if ($ledger->saleItems->isNotEmpty())
        <div class="d-flex flex-column gap-1 text-break">
            @foreach ($ledger->saleItems as $item)
                @php($returnedQuantity = (int) $item->returns->sum('quantity'))
                <div>
                    {{ $item->product?->display_name ?? $item->product_name }}
                    × {{ $item->quantity }}
                    @if ($returnedQuantity > 0)
                        <span class="text-danger">(Remark-{{ $returnedQuantity }} Return)</span>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-break">{{ $ledger->product_name }}</div>
    @endif
</td>
```

```css
.customer-ledger-product {
    width: 240px !important;
    max-width: 240px !important;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}
```

আগে invoice-এর সব পণ্যের নাম একসঙ্গে একটি cell-এ দেখানো হতো। এখন প্রতিটি sale item আলাদা লাইনে মূল বিক্রিত quantity-সহ দেখা যায়। কোনো item ফেরত হলে তার পাশে `(Remark-N Return)` যোগ হয়; যেমন `A4 Paper 70 GSM (Ream) - Rim × 5 (Remark-2 Return)`। Product কলামের সর্বোচ্চ প্রস্থ ২৪০ পিক্সেল এবং `overflow-wrap`/`word-break` থাকায় লম্বা product name-ও cell-এর মধ্যে ভেঙে যায়; ফলে একটি দীর্ঘ নাম পুরো টেবিল চওড়া করে না। পুরোনো/manual ledger entry-তে linked sale item না থাকলে আগের সংরক্ষিত product description-ই দেখায়।

## Sale Invoice-এ Customer Ledger lookup-এর return breakdown

### Controller (Code and Explanation)

ফাইল: [`app/Http/Controllers/SaleController.php`](./app/Http/Controllers/SaleController.php), `customerByPhone()` method।

```php
$customer = Customer::with(['ledgers' => function ($query) {
    $query->where('entry_type', 'sale')
        ->where('due_amount', '>', 0)
        ->with(['saleItems.product', 'saleItems.returns'])
        ->latest('sale_date')
        ->orderByDesc('id');
}])->where('phone', trim($validated['phone']))->first();
```

Sale Invoice-এর Customer Ledger preview-তে এখন শুধু বকেয়া থাকা sale invoice দেখানো হয়—invoice-এর তারিখ পুরোনো বা সাম্প্রতিক যাই হোক। সম্পূর্ণ পরিশোধিত sale এবং payment entry দেখানো হয় না। কোনো বকেয়া invoice না থাকলে preview-তে “No outstanding due invoices.” দেখায়। প্রতিটি নির্বাচিত invoice-এর sale item, product এবং return eager-load হয়। JSON response-এ প্রতিটি item-এর display name, বিক্রির মূল quantity ও ফেরত quantity পাঠানো হয়:

```php
'items' => $ledger->saleItems->map(fn (SaleItem $item) => [
    'name' => $item->display_product_name,
    'quantity' => (int) $item->quantity,
    'returned_quantity' => (int) $item->returns->sum('quantity'),
])->values(),
```

এতে Sale Invoice form-এ Customer বাছার পর Customer Ledger preview-তে কেবল outstanding due invoice-এর তথ্য ও return-এর পরিমাণ দেখা যায়। পুরোনো/manual ledger entry-তে linked sale item না থাকলে response-এ আগের `product_name` থাকে।

### Migration (Code and Explanation)

কোনো migration প্রয়োজন হয়নি। বিদ্যমান `sale_items` ও `product_returns` থেকে item এবং return quantity পড়া হয়।

### Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন হয়নি। Sale form বিদ্যমান `sales.customer.lookup` route-এ phone পাঠায়:

```php
Route::get('/sales/customer-lookup', [SaleController::class, 'customerByPhone'])
    ->middleware('permission:sales.create|blank-invoice')
    ->name('sales.customer.lookup');
```

### Model (Code and Explanation)

নতুন model relation যোগের প্রয়োজন হয়নি। বিদ্যমান `CustomerLedger::saleItems()`, `SaleItem::product()` ও `SaleItem::returns()` relation-গুলো ব্যবহার করা হয়। `SaleItem::display_product_name` product-এর নাম ও size-সহ display label দেয়।

### Blade Template (Code and Explanation)

ফাইল: [`resources/views/backend/Sales/salesIndex.blade.php`](./resources/views/backend/Sales/salesIndex.blade.php), customer lookup-এর ledger table তৈরির JavaScript।

```javascript
if (entry.type === 'sale' && entry.items && entry.items.length > 0) {
    const itemList = document.createElement('div');
    itemList.className = 'd-flex flex-column gap-1 text-break';
    entry.items.forEach((item) => {
        const itemLine = document.createElement('div');
        itemLine.append(document.createTextNode(`${item.name} × ${item.quantity}`));
        if (item.returned_quantity > 0) {
            const returnNote = document.createElement('span');
            returnNote.className = 'text-danger';
            returnNote.textContent = ` (Remark-${item.returned_quantity} Return)`;
            itemLine.appendChild(returnNote);
        }
        itemList.appendChild(itemLine);
    });
    itemCell.appendChild(itemList);
} else {
    itemCell.textContent = entry.type === 'payment' ?
        'Payment received' : (entry.item || 'Sale');
}
```

প্রতিটি বকেয়া invoice-এর পণ্য আলাদা লাইনে দেখায়। Return থাকলে মূল বিক্রির quantity-এর পরে `(Remark-N Return)` যোগ হয়—যেমন `A4 Paper 70 GSM (Ream) - Rim × 5 (Remark-2 Return)`। DOM node ও `textContent` ব্যবহার করায় product/customer text নিরাপদে প্রদর্শিত হয়; পুরোনো item data না থাকলে আগের description দেখানো হয়।

## ১. কাস্টমার তালিকা থেকে Ledger খোলা

ফাইল: [`resources/views/backend/customer/customerIndex.blade.php`](./resources/views/backend/customer/customerIndex.blade.php)

- প্রতিটি customer row-তে `customer.ledger` permission থাকলে Ledger button দেখা যায়।
- button `customer.ledger` route-এ customer ID পাঠায়।
- customer-এর row-তে due দেখাতে opening due + ledger total − ledger payment হিসাব করা হয়।
- এই তালিকার Ledger button আগে থেকেই ছিল; এই কাজের অংশ হিসেবে customer তালিকার অন্য কোনো আচরণ বদলানো হয়নি।

## ২. Route ও Permission

ফাইল: [`routes/web.php`](./routes/web.php)

| Route name | Method | Controller function | Permission | কাজ |
|---|---|---|---|---|
| `customer.ledger` | GET | `ledger` | `customer.ledger` | customer ledger ও summary দেখায় |
| `customer.ledger.store` | POST | `ledgerStore` | `Customer.ledger.payment` | sale অথবা standalone payment entry তৈরি করে |
| `customer.ledger.invoice` | GET | `ledgerInvoice` | `customer.ledger` | sale invoice বা payment receipt দেখায় |
| `customer.ledger.payments.store` | POST | `ledgerPaymentStore` | `Customer.ledger.payment` | একটি sale-এর due-তে তারিখসহ payment যোগ করে |
| `customer.ledger.edit` | GET | `ledgerEdit` | `Customer.ledger.edit` | নির্বাচিত entry edit করার form দেখায় |
| `customer.ledger.update` | PUT | `ledgerUpdate` | `Customer.ledger.edit` | যাচাই করা sale/payment entry update করে |
| `customer.ledger.destroy` | DELETE | `ledgerDestroy` | `Customer.ledger.delete` | entry মুছে; payment history থাকলে তা cascade-এ মুছে যায় |

Permission-গুলো [`database/seeders/PermissionSeederTable.php`](./database/seeders/PermissionSeederTable.php)-এ সংজ্ঞায়িত। ব্যবহারকারীর role-এ প্রাসঙ্গিক permission না থাকলে route middleware অনুরোধ প্রত্যাখ্যান করে। Ledger page-ও `@can` দিয়ে add, payment, edit ও delete control আড়াল করে।

## ৩. Controller-এর কাজ

ফাইল: [`app/Http/Controllers/CustomerController.php`](./app/Http/Controllers/CustomerController.php)

### Ledger-সংশ্লিষ্ট function

- `ledger($id)` customer, দায়িত্বপ্রাপ্ত employee, ledger entries, entry-র employee/receiver এবং date-ordered payment history eager-load করে। Employee তালিকা এবং মোট sale, মোট received payment ও opening due-সহ customer due হিসাব করে `customerLedger` view-তে পাঠায়।
- `ledgerStore(Request $request)` entry type, date, sale field, payment amount ও employee ID validate করে। Customer row lock করে transaction-এর মধ্যে নতুন entry বানায়। Sale total server-এ quantity × unit price থেকে হিসাব হয়; cash/full payment হলে total-টাই received amount, due sale-এ দেওয়া payment amount ধরা হয়। Sale total-এর বেশি payment বা current customer due-এর বেশি standalone payment reject হয়। Standalone payment opening due বা নির্দিষ্ট sale-এ বরাদ্দ না করা পুরোনো due কমাতে ব্যবহার করা যায়।
- `ledgerEdit($id)` entry ও payment history আনে, সংশ্লিষ্ট customer এবং সব ledger row-সহ একই page edit mode-এ render করে।
- `ledgerUpdate(Request $request, $id)` transaction এবং customer/ledger row lock ব্যবহার করে edit করে। Sale-এ product, quantity, unit price, date, invoice ও employee পরিবর্তন করা যায়; আগে আদায় করা অর্থ অপরিবর্তিত থাকে। নতুন sale total paid amount-এর নিচে নামানো যায় না এবং due পুনর্গণনা হয়। Standalone payment entry-তে date/amount/payer পরিবর্তন করা যায়, তবে amount customer-এর বাকি due অতিক্রম করতে পারে না।
- `ledgerPaymentStore(Request $request, $id)` ভবিষ্যৎ নয় এমন payment date ও শূন্যের বেশি amount validate করে। শুধু sale entry-তে payment নেয়। Transaction ও row lock-এর মধ্যে sale due পরীক্ষা করে; due-এর বেশি amount বাতিল করে। সফল হলে `customer_ledger_payments`-এ date/amount/receiver/note রাখে এবং parent sale-এর aggregate `payment_amount` ও `due_amount` আপডেট করে।
- `ledgerDestroy($id)` entry মুছে customer ledger page-এ ফেরায়। Sale entry-র payment history foreign-key cascade দিয়ে মুছে যায়।
- `ledgerInvoice($id)` customer, employee, receiver ও payment history eager-load করে। invoice number না থাকলে `CUS-` prefix ও padded ledger ID দিয়ে reference বানিয়ে print view-তে পাঠায়।
- `moneyToCents($amount)` amount-কে integer পয়সায় রূপান্তর করে, যাতে due/payment comparison-এ floating-point হিসাবের ভুল না হয়।

### Customer management-এর অন্য function

- `index()` customer তালিকা, employee এবং ledger summary নিয়ে customer index render করে।
- `create()` employee তালিকাসহ নতুন customer form দেখায়।
- `store()` customer input validate করে customer তৈরি করে; customer code না থাকলে ID থেকে code বানায়।
- `edit()` employee তালিকাসহ customer edit form দেখায়।
- `update()` customer-এর profile field validate ও সংরক্ষণ করে।
- `destroy()` customer record মুছে customer list-এ ফেরায়।

এই কাজ customer profile-এর CRUD logic পরিবর্তন করে না।

## ৪. Model ও Relationship

### Customer

ফাইল: [`app/Models/Customer.php`](./app/Models/Customer.php)

- `employee()` — `employee_id` দিয়ে `users` table-এর দায়িত্বপ্রাপ্ত কর্মীর সঙ্গে `belongsTo` relation।
- `ledgers()` — customer-এর সব `CustomerLedger` row-এর `hasMany` relation।

### CustomerLedger

ফাইল: [`app/Models/CustomerLedger.php`](./app/Models/CustomerLedger.php)

- `$fillable` sale/payment row তৈরিতে অনুমোদিত customer, entry, amount, employee, receiver ও note field নির্ধারণ করে।
- `$casts` `sale_date`-কে date এবং money field-গুলোকে দুই decimal-এর decimal হিসেবে দেয়।
- `customer()` — ledger row-এর customer-কে `belongsTo` হিসেবে ফেরায়।
- `employee()` — `employee_id` দিয়ে sale-সংশ্লিষ্ট employee-কে `belongsTo` হিসেবে ফেরায়।
- `receiver()` — `received_by` দিয়ে payment গ্রহণকারী user-কে `belongsTo` হিসেবে ফেরায়।
- `payments()` — পরবর্তী কিস্তির `CustomerLedgerPayment` row-গুলো `hasMany` হিসেবে ফেরায়; নতুন payment date ও ID অনুযায়ী আগে আসে।

### CustomerLedgerPayment

ফাইল: [`app/Models/CustomerLedgerPayment.php`](./app/Models/CustomerLedgerPayment.php)

- `$fillable` parent ledger ID, payment date, amount, receiver ও note mass assignment-এর জন্য অনুমোদন করে।
- `$casts` payment date-কে date এবং amount-কে decimal value করে।
- `ledger()` — payment history-কে তার sale ledger row-র সঙ্গে `belongsTo` করে।
- `receiver()` — payment গ্রহণকারী user-কে `belongsTo` করে।

এই payment history শুধু sale তৈরি হওয়ার পরের অতিরিক্ত কিস্তির রেকর্ড। Sale তৈরির সময়ের প্রথম payment parent `customer_ledgers.payment_amount`-এ থাকে; parent-এর aggregate payment amount-এ প্রথম payment ও পরবর্তী কিস্তি—দুটিই অন্তর্ভুক্ত।

## ৫. Database Migration

### Customer table

ফাইল: [`database/migrations/2026_07_06_073506_create_customers_table.php`](./database/migrations/2026_07_06_073506_create_customers_table.php)

- customer-এর name, phone, email, address, type, employee, shop name, customer code, opening due, status ও notes রাখে।
- `employee_id` `users` table-কে reference করে; user মুছলে employee foreign key null হয়।
- `customer_code` unique এবং nullable।
- `opening_due` শুরুতে customer-এর পূর্ববর্তী বকেয়া রাখে।
- এই migration এই feature-এর জন্য পরিবর্তন করা হয়নি।

### Customer ledger table

ফাইল: [`database/migrations/2026_10_11_000001_create_customer_ledgers_table.php`](./database/migrations/2026_10_11_000001_create_customer_ledgers_table.php)

- `customer_id` customer-কে reference করে; customer মুছলে তার ledger cascade-এ মুছে যায়।
- `employee_id` ও `received_by` user reference; user মুছলে সংশ্লিষ্ট reference null হয়।
- `entry_type` sale বা payment আলাদা করে।
- `sale_date`, `invoice_no`, `product_name`, `paid_by`, `quantity`, `unit_price`, `total_amount`, `payment_type`, `payment_amount`, `due_amount`, `notes` transaction-এর তথ্য রাখে।
- এই বিদ্যমান migration-এ নতুন edit/payment-history feature-এর জন্য পরিবর্তন করা হয়নি।

### নতুন Customer payment history table

ফাইল: [`database/migrations/2026_10_17_000002_create_customer_ledger_payments_table.php`](./database/migrations/2026_10_17_000002_create_customer_ledger_payments_table.php)

- `customer_ledger_id` sale ledger-কে reference করে; sale মুছলে সংশ্লিষ্ট কিস্তির history cascade-এ মুছে যায়।
- `payment_date` ও `amount` প্রতিটি অতিরিক্ত কিস্তির প্রকৃত তারিখ ও পরিমাণ রাখে।
- `received_by` user-কে reference করে; user মুছলে history রেখে receiver null হয়।
- `notes` payment-এর ঐচ্ছিক মন্তব্য; timestamps record তৈরির/পরিবর্তনের সময় রাখে।
- `down()` rollback-এ কেবল `customer_ledger_payments` table সরায়; পুরোনো customer বা ledger data মুছে না।

## ৬. Ledger Blade

### Ledger page

ফাইল: [`resources/views/backend/customer/customerLedger.blade.php`](./resources/views/backend/customer/customerLedger.blade.php)

- Customer-এর contact/employee তথ্য এবং total sale, payment ও due summary দেখায়।
- Permission থাকলে sale বা standalone payment entry form দেখায়। JavaScript entry type অনুযায়ী sale field ও payer field দেখায়, payment amount/receiver-এর `required` ও minimum amount সামঞ্জস্য করে।
- Sale entry-তে `quantity × unit_price` server-side-এ total হয়; browser পাঠানো due amount-কে হিসাবের উৎস ধরা হয় না।
- Standalone payment opening due বা unallocated balance কমায়। Sale row-র `Receive Payment` form নির্দিষ্ট sale-এর due-তে কিস্তি যোগ করে।
- Entry table date, invoice, product/payment description, quantity, total, received amount, due, payment type, employee ও receiver দেখায়।
- Sale row-এর অতিরিক্ত payment থাকলে expandable Payment History-তে date, amount, receiver ও note দেখা যায়।
- Invoice/receipt link print view খোলে। Edit ও Delete action কেবল সংশ্লিষ্ট permission থাকলে দেখা যায়; Delete `DELETE` request পাঠায় এবং confirmation চায়।
- Edit route-এ একই page-এ edit form দেখা যায়। Sale edit-এ aggregate received amount পরিবর্তন করা যায় না; standalone payment row-তে amount edit করা যায়।
- Controller-এর session success message এবং validation error view-তে দেখানো হয়।

### Printable invoice/receipt

ফাইল: [`resources/views/backend/customer/customerLedgerInvoice.blade.php`](./resources/views/backend/customer/customerLedgerInvoice.blade.php)

- `entry_type=sale` হলে product, quantity, unit price, total, aggregate received amount, due এবং অতিরিক্ত payment history-সহ invoice দেখায়।
- `entry_type=payment` হলে received amount, payer, receiver ও note-সহ payment receipt দেখায়।
- Customer contact/code, invoice reference, transaction date, print/back control ও print CSS ব্যবহার করে।
- Print stylesheet dashboard navigation/control লুকিয়ে শুধু invoice বা receipt কাগজে রাখে।

## ৭. Balance ও নিরাপত্তার নিয়ম

- Customer-এর মোট due = opening due + সব ledger sale total − সব ledger payment amount।
- Sale row-এর due = sale total − সেই sale-এ aggregate received amount।
- Linked installment parent row-র `payment_amount` বাড়ায়; তাই summary-তে payment history আবার যোগ করা হয় না।
- Standalone payment row-র total ও due শূন্য থাকে; তার payment amount customer-level due কমায়।
- Sale-এর total আগে পাওয়া payment-এর নিচে নামানো যায় না।
- Linked installment sale-এর due ছাড়াতে পারে না; standalone payment customer-এর মোট due ছাড়াতে পারে না।
- Money validation integer পয়সায় হয়; database-এ decimal currency field-এ সংরক্ষিত হয়।
- নতুন payment history migration চালাতে হবে: `php artisan migrate`।

## ৮. পরিবর্তিত ফাইলে inline comment

নতুন controller calculation, route permission, migration column, model relationship এবং feature test-এ বাংলা inline comment যোগ করা হয়েছে। Blade-এ comment মূলত শর্ত, payment history ও print behavior-এর মতো logic block-এ রাখা হয়েছে; প্রতিটি static HTML tag-এ comment যোগ করা হয়নি, যাতে template পাঠযোগ্য থাকে।
