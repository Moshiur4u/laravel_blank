# Product Return — বাংলা কোড ও ব্যাখ্যা

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/ProductReturnController.php`

এই controller-এ search suggestion, customer purchase history, invoice-এর পণ্যের তালিকা, একবারে একটি product ফেরত এবং date range report-এর action আছে।

নাম, ফোন বা invoice number দিয়ে suggestion তৈরির মূল query:

```php
$customerInvoices = SaleInvoice::query()
    ->where(function ($query) use ($like) {
        $query->where('customer_name', 'like', $like)
            ->orWhere('customer_phone', 'like', $like);
    })
    ->latest('invoice_date')
    ->limit(40)
    ->get(['id', 'customer_id', 'customer_name', 'customer_phone']);

$invoices = SaleInvoice::query()
    ->where('invoice_no', 'like', $like)
    ->latest('invoice_date')
    ->limit(10)
    ->get(['id', 'invoice_no', 'customer_id', 'customer_name', 'customer_phone', 'invoice_date']);
```

সাধারণ customer নির্বাচন করলে customer ID/ফোন দিয়ে আগের invoice load হয়। Walk-in Customer-এর customer ID ও ফোন না থাকলেও invoice snapshot-এর নাম দিয়ে তার history পাওয়া যায়। Return form submit করার সময় invoice-টি Walk-in Customer-এর হলে আলাদা confirmation prompt দেখায়; নিশ্চিত না করলে return জমা হয় না। Invoice নির্বাচন করলে সেই invoice-এর product item ও আগের return quantity-সহ JSON দেওয়া হয়:

```php
$invoices = $query
    ->with(['items' => fn ($itemQuery) => $itemQuery->withSum('returns as returned_quantity', 'quantity')])
    ->latest('invoice_date')
    ->limit(50)
    ->get();

return response()->json([
    'invoices' => $invoices->map(fn (SaleInvoice $invoice) => $this->invoicePayload($invoice))->values(),
]);
```

এক product-এর বাকি return quantity হিসাব করে client-কে পাঠানো হয়। অতীতে ফেরত দেওয়া quantity বাদ যায়, আর মজুতে আর নেই এমন product ফেরত দেওয়ার জন্য দেখানো হয় না:

```php
$returnedQuantity = (int) ($item->returned_quantity ?? 0);
$remainingQuantity = max((int) $item->quantity - $returnedQuantity, 0);

return [
    'id' => $item->id,
    'product_id' => $item->product_id,
    'product_name' => $item->product_name,
    'quantity' => (int) $item->quantity,
    'returned_quantity' => $returnedQuantity,
    'remaining_quantity' => $remainingQuantity,
    'unit_price' => (float) $item->unit_price,
    'line_total' => (float) $item->line_total,
    'returnable' => $item->product_id !== null && $remainingQuantity > 0,
];
```

`store()`-এ invoice row lock করে একই sale item-এ একসঙ্গে একাধিক return-এর quantity race আটকানো হয়। ফেরতের মোট মূল্য invoice-এর discount, VAT ও AIT পণ্যের বিক্রয়-অংশ অনুযায়ী ভাগ করে হিসাব হয়। শেষ অবশিষ্ট quantity ফেরত হলে round-off-এর অবশিষ্ট পয়সাও শেষ return-এ যোগ হয়:

```php
$subtotal = (float) $invoice->subtotal;
$lineTotal = (float) $saleItem->line_total;
$lineShare = $subtotal > 0 ? $lineTotal / $subtotal : 0;
$fullLineReturnAmount = round(
    $lineTotal
    - ((float) $invoice->discount_amount * $lineShare)
    + ((float) $invoice->vat_amount * $lineShare)
    + ((float) $invoice->ait_amount * $lineShare),
    2
);
$alreadyReturnedAmount = (float) $previousReturns->sum('return_amount');
$returnAmount = $quantity === $remainingQuantity
    ? round($fullLineReturnAmount - $alreadyReturnedAmount, 2)
    : min(
        round($fullLineReturnAmount * $quantity / (int) $saleItem->quantity, 2),
        round($fullLineReturnAmount - $alreadyReturnedAmount, 2)
    );
```

ফেরতের পরিমাণ আগে sale-এর অবশিষ্ট due কমায়। পরিশোধিত অংশ ফেরত দিতে হলে কেবল refund অংশের জন্য linked debit voucher হয়; stock, customer ledger, return record ও voucher একই database transaction-এ লেখা হয়:

```php
$saleBalanceDue = max(round($ledgerTotal - $ledgerPayment, 2), 0);
$amountAppliedToDue = min($returnAmount, $saleBalanceDue);
$refundAmount = round($returnAmount - $amountAppliedToDue, 2);

$ledger->update([
    'total_amount' => round($ledgerTotal - $returnAmount, 2),
    'payment_amount' => round($ledgerPayment - $refundAmount, 2),
    'due_amount' => max(round(
        ($ledgerTotal - $returnAmount) - ($ledgerPayment - $refundAmount),
        2
    ), 0),
    'quantity' => max((int) $ledger->quantity - $quantity, 0),
    'payment_type' => $saleBalanceDue > $amountAppliedToDue ? 'due' : 'full_payment',
]);

$product->unit = (string) ((float) $product->unit + $quantity);
$product->save();

if ($refundAmount > 0) {
    VoucherEntry::createWithCode([
        'voucher_type' => 'debit',
        'expense_income_type' => $invoice->customer_name ?: 'Walk-in Customer',
        'voucher_date' => $validated['return_date'],
        'amount' => number_format($refundAmount, 2, '.', ''),
        'reference' => $productReturn->return_no,
        'description' => "Refund for returned product {$saleItem->product_name} from invoice {$invoice->invoice_no}",
        'created_by' => $creator->id,
        'created_by_name' => $creator->name,
        'voucherable_type' => ProductReturn::class,
        'voucherable_id' => $productReturn->id,
    ]);
}
```

Report action `from_date` ও `to_date` যাচাই করে নির্দিষ্ট সময়ের return quantity, return value, refund value এবং pagination-সহ record দেখায়:

```php
$returns = ProductReturn::query()
    ->whereBetween('return_date', [$fromDate, $toDate]);
$totalQuantity = (int) (clone $returns)->sum('quantity');
$totalReturnAmount = (float) (clone $returns)->sum('return_amount');
$totalRefundAmount = (float) (clone $returns)->sum('refund_amount');
$rows = (clone $returns)
    ->with(['returnedBy', 'invoice'])
    ->latest('return_date')
    ->latest('id')
    ->paginate(30)
    ->withQueryString();
```

**Void invoice-এর সঙ্গে সমন্বয়:** `app/Http/Controllers/SaleController.php`-এর `destroy()`-এ invoice void করার সময় আগে ফেরত দেওয়া quantity বাদ দিয়ে কেবল অবশিষ্ট বিক্রিত stock ফেরত যোগ হয়। একই invoice-এর return debit voucher-ও সরানো হয়; invoice void হলে তার dependent return history-ও cascade delete হয়। এতে return করা পণ্য void-এর সময় দ্বিতীয়বার stock-এ যোগ হয় না।

ফেরত voucher হলো return হিসাবের সঙ্গে যুক্ত financial record। তাই `app/Http/Controllers/CombinedVoucherReportController.php`-এর edit/delete action-এ ProductReturn-linked voucher-কে server-side-এ block করা হয়েছে; `combinedVoucherReport.blade.php`-এও এই voucher-এর জন্য edit/delete form-এর বদলে “Managed by product return” দেখায়। এতে voucher একা পরিবর্তন/মুছে ledger ও return amount-এর মধ্যে অসামঞ্জস্য তৈরি করতে পারে না।

**Sales Report-এর সমন্বয়:** `app/Http/Controllers/SalesReportController.php`-এর page ও CSV export—দুই জায়গাতেই invoice-সংযুক্ত return amount gross sale থেকে বাদ হয়। Product-wise report-এও return quantity বাদ দিয়ে net quantity, sales, cost, profit, VAT/AIT হিসাব করা হয়। Invoice details-এ ফেরত value ও cost বাদ দিয়ে net sales, profit এবং update হওয়া due দেখানো হয়।

```php
$returnTotalsByInvoice = DB::table('product_returns')
    ->select('sale_invoice_id')
    ->selectRaw('SUM(return_amount) as returned_amount')
    ->groupBy('sale_invoice_id');

$totalSalesAmount = (float) ((clone $invoiceQuery)
    ->leftJoinSub($returnTotalsByInvoice, 'report_returns', 'report_returns.sale_invoice_id', '=', 'sale_invoices.id')
    ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount + ait_amount - COALESCE(report_returns.returned_amount, 0)), 0) as total')
    ->value('total') ?? 0);

$returnTotalsByItem = DB::table('product_returns')
    ->select('sale_item_id')
    ->selectRaw('SUM(quantity) as returned_quantity')
    ->groupBy('sale_item_id');
$netQuantity = 'CASE WHEN sale_items.quantity > COALESCE(report_item_returns.returned_quantity, 0) THEN sale_items.quantity - COALESCE(report_item_returns.returned_quantity, 0) ELSE 0 END';
$netRevenue = "CASE WHEN sale_items.quantity > 0 THEN sale_items.line_total * ({$netQuantity}) / sale_items.quantity ELSE 0 END";
```

`resources/views/backend/Sales/salesReport.blade.php`-এ Invoice-wise sale amount থেকে return value বাদ হয়; invoice profit-এ returned product-এর বিক্রয়মূল্য বাদ, তার ক্রয়মূল্য ফেরত যোগ এবং ফেরত হওয়া পণ্যের আনুপাতিক discount আবার যোগ হয়। CSV export-এও একই হিসাব প্রয়োগ করা হয়েছে। Return save-এর সময় `ProductReturnController` invoice-এর `total_amount`, `amount_paid`, `due_amount` ও customer ledger আপডেট করে, তাই Sales Report-এর due তাৎক্ষণিকভাবে সঠিক থাকে।

`app/Http/Controllers/ProductInventoryReportController.php`-এর Product Inventory Report-এও `product_returns` থেকে প্রতিটি sale item-এর ফেরত quantity বাদ দিয়ে net sold quantity, total sales, cost ও profit গণনা হয়। Inventory report-এর Excel-compatible CSV-তেও এই net মান ব্যবহৃত হয়; অর্থাৎ Product Inventory-এর “Total Qty Sold” ও “Profit” কলাম return-এর পরে আপডেট থাকে।

## Migration (Code and Explanation)

**নতুন ফাইল:** `database/migrations/2026_11_05_000001_create_product_returns_table.php`

```php
Schema::create('product_returns', function (Blueprint $table) {
    $table->id();
    $table->string('return_no', 50)->unique();
    $table->foreignId('sale_invoice_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('invoice_no', 100);
    $table->string('product_name');
    $table->unsignedInteger('quantity');
    $table->date('return_date')->index();
    $table->decimal('return_amount', 15, 2)->default(0);
    $table->decimal('refund_amount', 15, 2)->default(0);
    $table->text('reason')->nullable();
    $table->timestamps();

    $table->index(['sale_invoice_id', 'sale_item_id']);
});
```

প্রতিটি return একটি invoice-এর একটি sale item-এর সঙ্গে বাঁধা। invoice/item মুছে গেলে return record-ও মুছে যায়; product/customer/user পরে মুছে গেলেও return record থাকে। `return_amount` মোট হিসাবের value এবং `refund_amount` বাস্তবে পরিশোধিত debit voucher-এর অঙ্ক—দুটো আলাদা রাখা হয়েছে।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php` — authenticated ও verified route group-এ।

```php
Route::get('/product-returns', [ProductReturnController::class, 'index'])
    ->middleware('permission:product.return')
    ->name('product-returns.index');
Route::get('/product-returns/search', [ProductReturnController::class, 'search'])
    ->middleware('permission:product.return')
    ->name('product-returns.search');
Route::get('/product-returns/customer-history', [ProductReturnController::class, 'customerHistory'])
    ->middleware('permission:product.return')
    ->name('product-returns.customer-history');
Route::get('/product-returns/invoices/{id}', [ProductReturnController::class, 'invoiceDetails'])
    ->middleware('permission:product.return')
    ->name('product-returns.invoice');
Route::post('/product-returns', [ProductReturnController::class, 'store'])
    ->middleware('permission:product.return')
    ->name('product-returns.store');
Route::get('/product-returns/report', [ProductReturnController::class, 'report'])
    ->middleware('permission:product.return.report|reports.all_access')
    ->name('product-returns.report');
```

Return তৈরি ও customer/invoice lookup-এর জন্য `product.return`; report-এর জন্য `product.return.report` অথবা বিদ্যমান `reports.all_access` লাগে।

## Model (Code and Explanation)

**নতুন ফাইল:** `app/Models/ProductReturn.php`

```php
class ProductReturn extends Model
{
    protected $fillable = [
        'return_no', 'sale_invoice_id', 'sale_item_id', 'product_id',
        'customer_id', 'returned_by', 'invoice_no', 'product_name',
        'quantity', 'return_date', 'return_amount', 'refund_amount', 'reason',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class, 'sale_invoice_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function vouchers(): MorphMany
    {
        return $this->morphMany(VoucherEntry::class, 'voucherable');
    }
}
```

**পরিবর্তিত model:** `SaleItem`-এ `returns(): HasMany` এবং `SaleInvoice`-এ `productReturns(): HasMany` যোগ হয়েছে। এগুলো দিয়ে পূর্বের ফেরত quantity ও invoice-সম্পর্কিত return-গুলো query করা হয়। বিদ্যমান VoucherEntry polymorphic relation-এ refund debit voucher সংরক্ষিত হয়।

## Blade Template (Code and Explanation)

**নতুন ফাইল:** `resources/views/backend/ProductReturns/create.blade.php`। এতে নাম/ফোন/invoice search box, customer suggestion, invoice history, invoice-এর product list এবং এক product-এর return form আছে। Search suggestion নিরাপদে DOM `textContent` দিয়ে তৈরি হয়। Customer বা invoice নির্বাচন করলে AJAX-এ আগের ক্রয় ও ফেরত quantity আসে; প্রতিটি item-এর `remaining_quantity`-এর বেশি ফেরত দেওয়া যায় না। Walk-in Customer invoice-এ return submit করলে অতিরিক্ত confirmation চাওয়া হয়।

Form-এর মূল অংশ:

```blade
<form action="{{ route('product-returns.store') }}" method="POST" class="row g-3">
    @csrf
    <input type="hidden" name="sale_item_id" id="sale_item_id">
    <div class="col-md-4">
        <label for="return-quantity" class="form-label">Quantity to return</label>
        <input type="number" name="quantity" id="return-quantity" class="form-control" min="1" required>
    </div>
    <div class="col-md-4">
        <label for="return-date" class="form-label">Return date</label>
        <input type="date" name="return_date" id="return-date" class="form-control"
            value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
    </div>
    <div class="col-md-4">
        <label for="return-reason" class="form-label">Reason (optional)</label>
        <input type="text" name="reason" id="return-reason" class="form-control" maxlength="2000">
    </div>
    <button type="submit" class="btn btn-danger">Confirm Product Return</button>
</form>
```

Dashboard-এর `resources/views/dashboard/dashboard.blade.php`-এ `product.return` permission থাকলে Sales menu-তে Product Return link এবং `product.return.report`/`reports.all_access` থাকলে report link দেখা যায়।

```blade
@can('product.return')
    <a href="{{ route('product-returns.index') }}">Product Return</a>
@endcan

@canany(['product.return.report', 'reports.all_access'])
    <a href="{{ route('product-returns.report') }}">Product Return Report</a>
@endcanany
```

**নতুন ফাইল:** `resources/views/backend/ProductReturns/report.blade.php`। From/To date filter, return count, total quantity/value/refund এবং invoice/product/customer/কর্মীসহ paginated return list দেখায়।

```blade
<form action="{{ route('product-returns.report') }}" method="GET">
    <label for="from_date">From date</label>
    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}">
    <label for="to_date">To date</label>
    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}">
    <button type="submit">Apply Filter</button>
</form>
```

## Permission Seeder (Role-এ বরাদ্দ)

`database/seeders/PermissionSeederTable.php`-এ `product.return` ও `product.return.report` permission যোগ হয়েছে। `database/seeders/ProductReturnPermissionSeeder.php` এই permission-দুটি তৈরি করে এবং কেবল `admin` role-এ দেয়। `database/seeders/DatabaseSeeder.php`-এ seeder-টি role preset তৈরির পরে চালানো হয়। ফলে Sales ও Manager role ডিফল্টভাবে return করতে পারে না; admin বিদ্যমান Role & Permission UI থেকে অনুমোদিত role-এ permission দিতে পারেন।

```php
$returnPermission = Permission::firstOrCreate([
    'name' => 'product.return',
    'guard_name' => 'web',
]);
$reportPermission = Permission::firstOrCreate([
    'name' => 'product.return.report',
    'guard_name' => 'web',
]);

Role::query()
    ->where('name', 'admin')
    ->where('guard_name', 'web')
    ->get()
    ->each(fn (Role $role) => $role->givePermissionTo([$returnPermission, $reportPermission]));
```

`DatabaseSeeder`-এ ক্রমটি:

```php
PermissionSeederTable::class,
RolePermissionSeeder::class,
ProductReturnPermissionSeeder::class,
```

Product return refund voucher-এর balance যেন আলাদা edit/delete করে নষ্ট না হয়, তার controller guard-ও যোগ হয়েছে:

```php
if ($voucher->voucherable_type === ProductReturn::class) {
    throw ValidationException::withMessages([
        'voucher' => 'Product return refund vouchers can only be changed by processing a product return.',
    ]);
}
```

## Test

`tests/Feature/ProductReturnTest.php`-এ partial return, বাকি quantity, discount/VAT ভাগ, due কমানো, পরিশোধিত অংশের debit voucher, inventory update, product-return report totals, return-এর পর Sales Report-এ net sales/due/quantity আপডেট, unauthorized access এবং void করা invoice-এর সঙ্গে return stock/voucher cleanup যাচাইয়ের test যোগ হয়েছে।

## Setup

```powershell
php artisan migrate
php artisan db:seed --class=ProductReturnPermissionSeeder
```

`php artisan migrate` নতুন return table তৈরি করে। Permission seeder বিদ্যমান database-এ admin role-কে Product Return ও report access দেয়।
