# Purchase Return Module — কোড ও ব্যাখ্যা

এই নথিতে নতুন Purchase Return module এবং Supplier ও Inventory-র সঙ্গে এর সংযোগ বর্ণনা করা হয়েছে। কোডগুলো `app/`, `routes/`, `database/`, `resources/` ও `tests/`-এর উল্লেখিত ফাইলে রাখা হয়েছে। Purchase আলাদা invoice table-এ নয়, বর্তমান ব্যবস্থায় `supplierledgers`-এর প্রতিটি purchase line-এ সংরক্ষিত হয়; তাই return-ও সংশ্লিষ্ট supplier ledger line-কে ধরে কাজ করে।

## Controller (Code and Explanation)

### Role permission পরিবর্তন সংরক্ষণ

**ফাইল:** [RoleController.php](./app/Http/Controllers/RoleController.php)

Admin নিজের admin role থেকে কোনো permission সরালে তার নিজের effective permission-ও চলে যায়। আগে controller নতুন permission গ্রহণের সময় শুধু current user-এর permission ও target role-এর বর্তমান permission পরীক্ষা করত, ফলে পরেরবার revoked permission-টি আবার select করে save করা যেত না। এখন admin সব existing permission restore/reassign করতে পারে; non-admin editor-দের জন্য আগের সীমা—নিজের অনুমোদিত permission অথবা target role-এ ইতিমধ্যে থাকা permission—বজায় থাকে:

```php
private function manageablePermissionIds(User $user, Role $role): array
{
    if ($user->hasRole('admin')) {
        return Permission::query()->pluck('id')->all();
    }

    return array_unique(array_merge(
        $user->getAllPermissions()->modelKeys(),
        $role->permissions->modelKeys()
    ));
}
```

### নতুন Purchase Return controller

**ফাইল:** [PurchaseReturnController.php](./app/Http/Controllers/PurchaseReturnController.php)

এই controller-এ supplier/invoice search, supplier-এর purchase history, returnable line নির্বাচন, transaction-সহ return সংরক্ষণ এবং date-filtered report রয়েছে। History ও purchase-details endpoint Product relation-এ `unit` এবং `purchase_price`-ও eager-load করে; এগুলো না আনলে payload-এর stock শূন্য দেখিয়ে `Select Product` button আড়াল হয়ে যায়:

```php
->with(['product:id,productName,unit,purchase_price', 'supplier:id,name'])
```

`store()`-এর transaction-এর মধ্যে ব্যবহৃত মূল হিসাব ও validation:

```php
$quantityMillis = $this->quantityMillis($validated['quantity']);
$unitPrice = is_numeric($ledger->price)
    ? (float) $ledger->price
    : (is_numeric($product->purchase_price) ? (float) $product->purchase_price : null);
$unitPriceCents = (int) round($unitPrice * 100);
$currentQuantityMillis = $this->quantityMillis($ledger->quantity);
$remainingQuantityMillis = $currentQuantityMillis;
$stockMillis = $this->quantityMillis($product->unit);

if ($quantityMillis > $remainingQuantityMillis) {
    throw ValidationException::withMessages([
        'quantity' => 'The return quantity cannot exceed the remaining purchased quantity.',
    ]);
}
if ($quantityMillis > $stockMillis) {
    throw ValidationException::withMessages([
        'quantity' => 'The return quantity cannot exceed the product quantity currently in stock.',
    ]);
}

$currentTotalCents = (int) round((float) $ledger->total * 100);
$returnAmountCents = $quantityMillis === $remainingQuantityMillis
    ? $currentTotalCents
    : min(
        (int) round($unitPriceCents * $quantityMillis / 1000),
        $currentTotalCents
    );

$dueCents = max((int) round((float) $ledger->due * 100), 0);
$paymentCents = max((int) round((float) $ledger->payment * 100), 0);
$amountAppliedToDueCents = min($returnAmountCents, $dueCents);
$refundCents = $returnAmountCents - $amountAppliedToDueCents;

if ($returnAmountCents > $currentTotalCents) {
    throw ValidationException::withMessages([
        'supplierledger_id' => 'The return value exceeds the remaining purchase value.',
    ]);
}
if ($refundCents > $paymentCents) {
    throw ValidationException::withMessages([
        'supplierledger_id' => 'The supplier refund exceeds the amount paid for this purchase.',
    ]);
}
```

এই অংশগুলো request validation এবং `DB::transaction()`-এর মধ্যে চলে। Return value মূল purchase ledger-এর প্রতি একক `price × ফেরত quantity`; পুরোনো ledger row-তে price না থাকলে Product-এর `purchase_price` fallback হয়। শেষের সব অবশিষ্ট quantity ফেরত দিলে rounding residue এড়াতে অবশিষ্ট ledger total-টাই শূন্য করা হয়। টাকার হিসাব পয়সায় (integer cents) এবং quantity তিন দশমিক ঘরে করা হয়েছে। প্রথমে supplier-এর বকেয়া কমে; বকেয়ার অতিরিক্ত অংশ থাকলে সেটি supplier থেকে পাওয়া cash refund হিসেবে ধরা হয়। Return row, supplier ledger, product stock এবং প্রয়োজনে credit voucher একই transaction-এ তৈরি/আপডেট হয়।

### Supplier, ledger, product এবং voucher-এর consistency guard

নিচের বিদ্যমান controller-গুলিও return history রক্ষা করে:

- [SupplierController.php](./app/Http/Controllers/SupplierController.php): return রেকর্ড থাকা supplier মুছতে দেয় না।
- [SupplierledgerController.php](./app/Http/Controllers/SupplierledgerController.php): return-যুক্ত purchase line সম্পাদনা বা মুছতে এবং ঐ purchase-এ নতুন/পুরোনো payment যোগ বা সম্পাদনা করতে দেয় না।
- [ProductController.php](./app/Http/Controllers/ProductController.php): purchase return-যুক্ত পণ্যের stock, supplier বা purchase price পরিবর্তন করে return ledger overwrite করা ঠেকায়।
- [CombinedVoucherReportController.php](./app/Http/Controllers/CombinedVoucherReportController.php): purchase-return refund voucher edit/delete এবং সংশ্লিষ্ট purchase payment voucher পরিবর্তন আটকায়।

উদাহরণ—return-এর পরে supplier ledger সম্পাদনা বন্ধ:

```php
if ($ledger->purchaseReturns()->exists()) {
    throw ValidationException::withMessages([
        'total' => 'This purchase ledger entry cannot be edited after a purchase return has been recorded.',
    ]);
}
```

Return history থাকা supplier মুছতেও একইভাবে বাধা আছে:

```php
if ($supplier->purchaseReturns()->exists()) {
    return redirect()->route('supplier.index')->with(
        'error',
        'This supplier cannot be deleted because purchase return records are linked to the supplier.'
    );
}
```

Product edit-এ return history থাকলে সংবেদনশীল field পরিবর্তনও আটকে যায়:

```php
$hasPurchaseReturns = $product->purchaseLedgers()
    ->whereHas('purchaseReturns')
    ->exists();

if ($hasPurchaseReturns && $purchaseReturnSensitiveFieldsChanged) {
    throw ValidationException::withMessages([
        'unit' => 'Stock, supplier, and purchase price cannot be changed after this product has purchase return history.',
    ]);
}
```

`CombinedVoucherReportController`-এ return refund voucher update/delete-এর জন্য `PurchaseReturn::class` guard রয়েছে; Supplierledger payment voucher edit/delete-ও return-পরবর্তী অবস্থায় বন্ধ।

```php
if ($voucher->voucherable_type === PurchaseReturn::class) {
    throw ValidationException::withMessages([
        'voucher' => 'Purchase return refund vouchers can only be changed by processing a purchase return.',
    ]);
}
```

এগুলো audit ও হিসাবের অপরিবর্তনীয়তা বজায় রাখে। Supplier ledger-এর `total`, `payment`, `due`, `quantity`-ই supplier summary এবং Product Inventory Report-এ ব্যবহৃত হয়; return সংরক্ষণের সঙ্গে সেগুলো কমে যাওয়ায় ওই দুই জায়গায় net purchase ও stock সঙ্গে সঙ্গে আপডেট হয়। Supplier ledger view-তে return quantity/value-র history দেখায় এবং return-এর পরে payment add/edit form গোপন করে।

## Migration (Code and Explanation)

**ফাইল:** [2026_11_09_000001_create_purchase_returns_table.php](./database/migrations/2026_11_09_000001_create_purchase_returns_table.php)

```php
Schema::create('purchase_returns', function (Blueprint $table) {
    $table->id();
    $table->string('return_no', 50)->unique();
    $table->foreignId('supplierledger_id')->constrained('supplierledgers')->restrictOnDelete();
    $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('invoice_no', 100);
    $table->string('supplier_name');
    $table->string('product_name');
    $table->decimal('quantity', 12, 3);
    $table->decimal('unit_price', 15, 2)->default(0);
    $table->date('return_date')->index();
    $table->decimal('return_amount', 15, 2)->default(0);
    $table->decimal('refund_amount', 15, 2)->default(0);
    $table->text('reason')->nullable();
    $table->timestamps();

    $table->index(['supplierledger_id', 'return_date']);
    $table->index(['supplier_id', 'return_date']);
});
```

প্রতিটি return-এ মূল supplier ledger row, supplier/product ID এবং invoice/product/supplier-এর নাম snapshot হিসেবে থাকে। Supplier/product/user মুছে গেলে সংশ্লিষ্ট optional foreign key null হতে পারে, কিন্তু return-এর snapshot অক্ষত থাকে। `supplierledger_id`-এ `restrictOnDelete()` থাকায় return থাকা মূল purchase line মুছে ফেলা যায় না।

## Route (Code and Explanation)

**ফাইল:** [web.php](./routes/web.php)

```php
use App\Http\Controllers\PurchaseReturnController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/purchase-returns', [PurchaseReturnController::class, 'index'])
        ->middleware('permission:purchase.return|product.index|product.create|product.edit')
        ->name('purchase-returns.index');
    Route::get('/purchase-returns/search', [PurchaseReturnController::class, 'search'])
        ->middleware('permission:purchase.return|product.index|product.create|product.edit')
        ->name('purchase-returns.search');
    Route::get('/purchase-returns/supplier-history', [PurchaseReturnController::class, 'supplierHistory'])
        ->middleware('permission:purchase.return|product.index|product.create|product.edit')
        ->name('purchase-returns.supplier-history');
    Route::get('/purchase-returns/purchases/{id}', [PurchaseReturnController::class, 'purchaseDetails'])
        ->whereNumber('id')
        ->middleware('permission:purchase.return|product.index|product.create|product.edit')
        ->name('purchase-returns.purchase');
    Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])
        ->middleware('permission:purchase.return|product.index|product.create|product.edit')
        ->name('purchase-returns.store');
    Route::get('/purchase-returns/report', [PurchaseReturnController::class, 'report'])
        ->middleware('permission:purchase.return.report|reports.all_access')
        ->name('purchase-returns.report');
});
```

সব route authentication ও email verification চায়। Return screen/API-গুলো `purchase.return` অথবা Product module-এর `product.index`, `product.create`, `product.edit` permission-এ সীমিত—তাই আগে থেকে Product module access থাকা ব্যবহারকারীরাও Purchase Return খুলতে পারবেন। Report route `purchase.return.report` অথবা আগের broad `reports.all_access` permission-এ সীমিত।

`PermissionSeederTable`-এ নতুন দুই permission যোগ করা হয়েছে; `RolePermissionSeeder`-এর manager preset-এ return/report access রয়েছে। Admin preset বিদ্যমান seeder-এর মতো সব permission পায়।

```php
'purchase.return',
'purchase.return.report',
```

বর্তমান database-এ manager/admin role-কে permission দেওয়া এবং permission cache-সহ deploy/update নিরাপদ রাখতে [PurchaseReturnPermissionSeeder.php](./database/seeders/PurchaseReturnPermissionSeeder.php) idempotent seeder-ও যোগ করা হয়েছে। [DatabaseSeeder.php](./database/seeders/DatabaseSeeder.php)-এ এটি চালানোর জন্য যুক্ত হয়েছে। এটি role-গুলোর অন্য permission-গুলোকে sync বা মুছে ফেলে না:

```php
// DatabaseSeeder.php
$this->call([
    PurchaseReturnPermissionSeeder::class,
]);

// PurchaseReturnPermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PurchaseReturnPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $returnPermission = Permission::firstOrCreate([
            'name' => 'purchase.return',
            'guard_name' => 'web',
        ]);
        $reportPermission = Permission::firstOrCreate([
            'name' => 'purchase.return.report',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->whereIn('name', ['admin', 'manager'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($returnPermission, $reportPermission): void {
                $role->givePermissionTo([$returnPermission, $reportPermission]);
            });
    }
}
```

`RolePermissionSeeder`-এর manager preset-এও এই দুই permission যোগ করা হয়েছে:

```php
'purchase.return',
'purchase.return.report',
```

Dashboard navigation-এর Product menu-তে Purchase Return এবং Reports menu-তে Purchase Return Report যোগ করা হয়েছে। Purchase Return link `purchase.return` অথবা বিদ্যমান Product permission থাকা ব্যবহারকারীর Product menu-তে দেখা যায়।

## Model (Code and Explanation)

**নতুন model:** [PurchaseReturn.php](./app/Models/PurchaseReturn.php)

```php
class PurchaseReturn extends Model
{
    protected $fillable = [
        'return_no', 'supplierledger_id', 'supplier_id', 'product_id', 'returned_by',
        'invoice_no', 'supplier_name', 'product_name', 'quantity', 'unit_price',
        'return_date', 'return_amount', 'refund_amount', 'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'return_date' => 'date',
        'return_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function supplierLedger(): BelongsTo
    {
        return $this->belongsTo(Supplierledger::class, 'supplierledger_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

এ ছাড়া [Supplier.php](./app/Models/Supplier.php)-এ `purchaseReturns()`; [Supplierledger.php](./app/Models/Supplierledger.php)-এ `purchaseReturns()`; এবং [Product.php](./app/Models/Product.php)-এ `purchaseReturns()` relationship যোগ হয়েছে। এগুলো supplier deletion guard, return eligibility ও voucher linkage-এ ব্যবহৃত।

```php
// Supplier.php
public function purchaseReturns(): HasMany
{
    return $this->hasMany(PurchaseReturn::class);
}

// Supplierledger.php
public function purchaseReturns(): HasMany
{
    return $this->hasMany(PurchaseReturn::class, 'supplierledger_id');
}

// Product.php
public function purchaseReturns(): HasMany
{
    return $this->hasMany(PurchaseReturn::class);
}
```

## Blade Template (Code and Explanation)

### Role edit permission selection

**ফাইল:** [role-edit.blade.php](./resources/views/backend/roles/role-edit.blade.php) এবং [role-create.blade.php](./resources/views/backend/roles/role-create.blade.php)

Card click handler-এ label-এর click-ও bubble হয়ে checkbox-কে custom handler ও browser native action—দুইবার toggle করত। Handler এখন checkbox/label-এর native action-কে bypass করে; card-এর background-এ click হলে `checkbox.click()` দিয়ে একবার toggle করে। Validation-এ ফিরে এলে submitted selection ও error message-ও দেখায়:

```javascript
function toggleCheckbox(event, id) {
    if (event.target.closest('input, label')) {
        return;
    }
    const checkbox = document.getElementById(id);
    if (checkbox) {
        checkbox.click();
    }
}
```

### Return তৈরি

**ফাইল:** [create.blade.php](./resources/views/backend/PurchaseReturns/create.blade.php)

```blade
<form id="purchase-return-form" action="{{ route('purchase-returns.store') }}" method="POST">
    @csrf
    <input type="hidden" name="supplierledger_id" id="supplierledger_id">
    <label for="purchase-return-quantity">Quantity to return</label>
    <input type="number" name="quantity" id="purchase-return-quantity"
        min="0.001" step="0.001" value="{{ old('quantity') }}" required>
    <label for="purchase-return-date">Return date</label>
    <input type="date" name="return_date" id="purchase-return-date"
        value="{{ old('return_date', today()->toDateString()) }}"
        max="{{ today()->toDateString() }}" required>
    <label for="purchase-return-reason">Reason (optional)</label>
    <input type="text" name="reason" id="purchase-return-reason"
        value="{{ old('reason') }}" maxlength="2000">
    <button type="submit">Confirm Purchase Product Return</button>
</form>
```

পেজের JavaScript supplier বা invoice খুঁজে JSON search endpoint ব্যবহার করে, supplier history থেকে returnable purchase line দেখায় এবং selected line-এর সর্বোচ্চ quantity (অবশিষ্ট ক্রয় ও বর্তমান stock—দুটির মধ্যে কমটি) form-এ বসায়। AJAX error হলে response-এর validation message বা HTTP status দেখায়; আর server-side validation ব্যর্থ হয়ে পেজে ফেরত এলে আগের purchase, quantity, date ও reason পুনরুদ্ধার করে, যাতে form আবার পূরণ না করতে হয়। Submit-এর আগে confirmation চায়। পৃষ্ঠায় search, history table ও AJAX handling-সহ সম্পূর্ণ template উপরের ফাইলে রয়েছে।

Sales Return-এর মতো supplier বেছে নেওয়ার পর purchase product-গুলোর তালিকা আসে। প্রতিটি returnable product-এর পাশে `Select Product` চাপলে শুধু সেই একটি product-এর জন্য `Record One Purchase Product Return` form খোলে; সেখানে quantity, return date ও reason দিয়ে `Confirm Purchase Product Return` করা যায়।

```blade
<h5>Purchase Products</h5>
<button type="button">Select Product</button>

<h5>Record One Purchase Product Return</h5>
<button type="submit">Confirm Purchase Product Return</button>
```

```javascript
if (!response.ok) {
    let message = `Could not load purchase information (HTTP ${response.status}).`;
    try {
        const body = await response.json();
        const validationMessage = body.errors
            ? Object.values(body.errors).flat().join(' ')
            : '';
        message = validationMessage || body.message || message;
    } catch (error) {
        // JSON না এলে HTTP status-সহ message দেখানো হয়।
    }
    throw new Error(message);
}
```

```javascript
const oldPurchaseId = @json(old('supplierledger_id'));
if (oldPurchaseId) {
    const url = purchaseUrl.replace('__PURCHASE_ID__', encodeURIComponent(oldPurchaseId));
    getJson(url).then(async function(purchase) {
        await loadHistory(purchase.supplier_id, purchase.supplier_name);
        openReturnForm(purchase, @json(old('quantity')));
    }).catch(error => showError(error.message));
}
```

### Return report

**ফাইল:** [report.blade.php](./resources/views/backend/PurchaseReturns/report.blade.php)

```blade
<form action="{{ route('purchase-returns.report') }}" method="GET">
    <input type="date" name="from_date" value="{{ $fromDate }}">
    <input type="date" name="to_date" value="{{ $toDate }}">
    <button type="submit">Apply Filter</button>
</form>

<div>Return Transactions: {{ number_format($returnCount) }}</div>
<div>Returned Quantity: {{ number_format($totalQuantity, 3) }}</div>
<div>Return Value: ৳{{ number_format($totalReturnAmount, 2) }}</div>
<div>Refund Received: ৳{{ number_format($totalRefundAmount, 2) }}</div>
```

নিচের report table-এ return number, date, supplier invoice, product, supplier, quantity, return value, cash refund, প্রক্রিয়াকারী ও reason দেখায়। Pagination, date range filter ও print action-ও আছে।

Product menu-তে button যোগ করা হয়েছে [dashboard.blade.php](./resources/views/dashboard/dashboard.blade.php)-তে:

```blade
@canany(['product.index', 'product.create', 'product.edit', 'purchase.return'])
    <li>
        <a href="javascript:;" class="has-arrow">
            <div class="menu-title">Product</div>
        </a>
        <ul>
            @can('product.index')
                <li><a href="{{ route('product.index') }}">All Product</a></li>
            @endcan
            @can('product.create')
                <li><a href="{{ route('product.create') }}">Create Product</a></li>
            @endcan
            @canany(['purchase.return', 'product.index', 'product.create', 'product.edit'])
                <li>
                    <a href="{{ route('purchase-returns.index') }}">
                        <i class="bx bx-undo"></i>Purchase Return
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcanany
```

Supplier ledger row-তে return-এর ইতিহাস দেখানোর অংশ [supplierledger.blade.php](./resources/views/backend/Supplier/supplierledger.blade.php)-এ রয়েছে:

```blade
@if ($entry->purchaseReturns->isNotEmpty())
    <details class="mt-1 small fw-normal">
        <summary class="text-danger">
            Purchase returns ({{ $entry->purchaseReturns->count() }})
        </summary>
        @foreach ($entry->purchaseReturns as $purchaseReturn)
            <div class="text-nowrap">
                {{ $purchaseReturn->return_date->format('d M Y') }}:
                {{ number_format($purchaseReturn->quantity, 3) }} unit(s),
                −৳{{ number_format($purchaseReturn->return_amount, 2) }}
            </div>
        @endforeach
    </details>
@endif
```

Dashboard sidebar-এর পরিবর্তন [dashboard.blade.php](./resources/views/dashboard/dashboard.blade.php)-তে করা হয়েছে; permission না থাকলে সংশ্লিষ্ট link দেখায় না। Inventory controller আলাদাভাবে বদলানোর প্রয়োজন হয়নি: inventory report supplier ledger-এর quantity/total এবং Product-এর বর্তমান `unit` ব্যবহার করে, আর return transaction সেগুলো আপডেট করে।

## Seeder এবং পরীক্ষা (অতিরিক্ত)

**Permission seeder:** [PermissionSeederTable.php](./database/seeders/PermissionSeederTable.php) ও [RolePermissionSeeder.php](./database/seeders/RolePermissionSeeder.php)-তে নতুন return ও report permission যোগ করা হয়েছে।

**Feature test:** [PurchaseReturnTest.php](./tests/Feature/PurchaseReturnTest.php)-এ partial/final return, due ও paid balance, inventory stock, Supplier Ledger summary, Inventory Report, refund voucher এবং voucher edit/delete guard যাচাই করা হয়েছে।
Product module permission দিয়ে Return screen খোলা যায়—এটিও আলাদা test-এ যাচাই করা হয়েছে।

```php
$this->actingAs($user)
    ->post(route('purchase-returns.store'), [
        'supplierledger_id' => $ledger->id,
        'quantity' => '2',
        'return_date' => today()->toDateString(),
    ])
    ->assertRedirect(route('purchase-returns.index', ['supplier_id' => $supplier->id]));

expect((float) $ledger->fresh()->quantity)->toBe(3.0)
    ->and((float) $ledger->fresh()->total)->toBe(300.0)
    ->and((float) $product->fresh()->unit)->toBe(4.0);
```

Test চালানোর command:

```powershell
php artisan test tests\Feature\PurchaseReturnTest.php
```
