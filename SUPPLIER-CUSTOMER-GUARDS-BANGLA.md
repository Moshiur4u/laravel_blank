# Supplier ও Customer সুরক্ষা এবং Supplier Inactive অপশন

## Controller (Code and Explanation)

### `SupplierController`

Supplier মুছে দেওয়ার আগে তার প্রতিটি ledger entry-র সরাসরি linked এবং পুরোনো morph-বিহীন voucher পরীক্ষা করা হয়। voucher থাকলে supplier ও logo অক্ষত থাকে এবং ব্যবহারকারীকে Combined Voucher Report থেকে voucher মুছে পুনরায় চেষ্টা করতে বলা হয়।

```php
$hasVouchers = $supplier->supplierledgers()
    ->with('payments')
    ->get()
    ->contains(function (Supplierledger $ledger) use ($supplier) {
        $paymentRows = $ledger->payments
            ->map(fn (SupplierLedgerPayment $payment) => [
                'date' => $payment->payment_date->toDateString(),
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
            ])
            ->all();
        $initialPayment = round(
            (float) $ledger->payment - $ledger->payments->sum(fn (SupplierLedgerPayment $payment) => (float) $payment->amount),
            2
        );

        if ($initialPayment > 0) {
            $paymentRows[] = [
                'date' => $ledger->created_at->toDateString(),
                'amount' => number_format($initialPayment, 2, '.', ''),
            ];
        }

        return VoucherEntry::existsForSource(
            $ledger,
            'debit',
            'Supplier Payment ('.$supplier->name.')',
            'Payment to supplier '.$supplier->name,
            $ledger->invoice_id,
            $paymentRows
        );
    });

if ($hasVouchers) {
    return redirect()->route('supplier.index')->with(
        'error',
        'Delete this supplier’s associated vouchers from the Combined Voucher Report before deleting the supplier.'
    );
}
```

Supplier-এর status পরিবর্তন করার handler:

```php
public function toggleStatus($id)
{
    $supplier = Supplier::findOrFail($id);
    $supplier->update(['is_active' => ! $supplier->is_active]);

    return redirect()->route('supplier.index')->with(
        'success',
        $supplier->is_active ? 'Supplier activated successfully!' : 'Supplier marked inactive successfully!'
    );
}
```

### `CustomerController`

Customer-এর ledger এবং payment history থেকে সংশ্লিষ্ট voucher পরীক্ষা করা হয়। voucher থাকলে customer delete হয় না।

```php
$hasVouchers = $customer->ledgers()
    ->with('payments')
    ->get()
    ->contains(function (CustomerLedger $ledger) use ($customer) {
        $paymentRows = $ledger->payments
            ->map(fn (CustomerLedgerPayment $payment) => [
                'date' => $payment->payment_date->toDateString(),
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
            ])
            ->all();
        $initialPayment = round(
            (float) $ledger->payment_amount - $ledger->payments->sum(fn (CustomerLedgerPayment $payment) => (float) $payment->amount),
            2
        );

        if ($initialPayment > 0) {
            $paymentRows[] = [
                'date' => $ledger->sale_date->toDateString(),
                'amount' => number_format($initialPayment, 2, '.', ''),
            ];
        }

        return VoucherEntry::existsForSource(
            $ledger,
            'credit',
            $customer->name,
            'Payment received from customer '.$customer->name,
            $ledger->invoice_no,
            $paymentRows
        );
    });

if ($hasVouchers) {
    return redirect()->route('customer.index')->with(
        'error',
        'Delete this customer’s associated vouchers from the Combined Voucher Report before deleting the customer.'
    );
}
```

### `ProductController`

নতুন product তৈরির সময় শুধু active supplier গ্রহণ করা হয়:

```php
'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)],
```

Create form-এ শুধু active supplier পাঠানো হয়; edit form-এ সক্রিয় supplier-এর সঙ্গে product-এর আগের supplier-টিও রাখা হয়, যাতে আগে থেকে inactive supplier-এ যুক্ত product-এর সম্পর্ক অজান্তে বদলে না যায়।

```php
$suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
```

```php
$suppliers = Supplier::where('is_active', true)
    ->when($product->supplier_id, fn ($query) => $query->orWhereKey($product->supplier_id))
    ->orderBy('name')
    ->get();
```

Update request-এ inactive supplier অন্য product-এ নির্বাচন করা যায় না; বর্তমান product-এর একই inactive supplier রাখা যায়।

```php
if ($request->filled('supplier_id')
    && (int) $request->supplier_id !== (int) $product->supplier_id
    && ! Supplier::whereKey($request->supplier_id)->where('is_active', true)->exists()) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'supplier_id' => 'Please select an active supplier.',
    ]);
}
```

## Migration (Code and Explanation)

`database/migrations/2026_11_02_000001_add_is_active_to_suppliers_table.php`-এ নতুন boolean column যোগ করা হয়েছে। `default(true)` থাকায় পুরোনো supplier-রা migration-এর পরও active থাকবেন।

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
```

## Route (Code and Explanation)

`routes/web.php`-এ `supplier.inactive` permission-সহ PATCH route যোগ করা হয়েছে, যাতে supplier সম্পাদনা করা আর supplier নিষ্ক্রিয়/সক্রিয় করার অনুমতি আলাদাভাবে নিয়ন্ত্রণ করা যায়।

```php
Route::patch('/supplier/{id}/status', [SupplierController::class, 'toggleStatus'])
    ->middleware('permission:supplier.inactive')
    ->name('supplier.status');
```

Blank money receipt route-এ dedicated `money-receipt` permission প্রয়োগ করা হয়েছে:

```php
Route::get('/money-receipts/create', [MoneyReceiptController::class, 'create'])
    ->middleware('permission:money-receipt')
    ->name('money-receipts.create');
```

## Model (Code and Explanation)

`Supplier` model-এ status-কে mass assignment এবং boolean casting-এর জন্য ঘোষণা করা হয়েছে:

```php
protected $fillable = [
    'name',
    'email',
    'phone',
    'address',
    'logo',
    'is_active',
];

protected $casts = [
    'is_active' => 'boolean',
];
```

`VoucherEntry::existsForSource()` একই source ledger-এর morph-linked voucher-ও খোঁজে, এবং legacy voucher-এর ক্ষেত্রে voucher type, head, description, date, amount, reference—সব মিলিয়ে যাচাই করে। এই shared helper দুই controller-এ একই matching logic বজায় রাখে।

```php
public static function existsForSource(
    Model $source,
    string $voucherType,
    string $voucherHead,
    string $description,
    ?string $reference,
    array $payments
): bool {
    if (static::query()
        ->where('voucherable_type', $source::class)
        ->where('voucherable_id', $source->getKey())
        ->exists()) {
        return true;
    }

    foreach ($payments as $payment) {
        $legacyQuery = static::query()
            ->whereNull('voucherable_type')
            ->whereNull('voucherable_id')
            ->where('voucher_type', $voucherType)
            ->where('expense_income_type', $voucherHead)
            ->where('description', $description)
            ->whereDate('voucher_date', $payment['date'])
            ->where('amount', $payment['amount']);

        if ($reference === null) {
            $legacyQuery->whereNull('reference');
        } else {
            $legacyQuery->where('reference', $reference);
        }

        if ($legacyQuery->exists()) {
            return true;
        }
    }

    return false;
}
```

## Blade Template (Code and Explanation)

`resources/views/backend/Supplier/supplierList.blade.php`-এ supplier-এর বর্তমান status এবং Inactive/Activate form দেখানো হয়েছে। শুধু `supplier.inactive` permission থাকলে status control দেখা যাবে।

```blade
<span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
</span>

@can('supplier.inactive')
    <form action="{{ route('supplier.status', $supplier->id) }}" method="POST">
        @csrf
        @method('PATCH')
        <button type="submit"
            class="btn btn-sm {{ $supplier->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
            {{ $supplier->is_active ? 'Inactive' : 'Activate' }}
        </button>
    </form>
@endcan
```

Money Receipt button কেবল এই permission-ধারী user-দের দেখানো হয়:

```blade
@can('money-receipt')
    <a href="{{ route('money-receipts.create') }}" class="btn btn-outline-success">
        <i class="bx bx-receipt me-1"></i>Print Money Receipt
    </a>
@endcan
```

Supplier ও customer list-এ delete guard-এর error flash দেখানো হয়। Product create form-এ inactive supplier আসবে না; edit form-এ পুরোনো inactive supplier-কে স্পষ্টভাবে চিহ্নিত করে রাখা হয়।

```blade
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
```

## Permission Seeder এবং Role Assignment

`PermissionSeederTable`-এ `supplier.inactive` ও `money-receipt` permission যোগ করা হয়েছে। `RolePermissionSeeder`-এ inactive toggle manager-কে এবং Money Receipt sales ও manager role-কে দেওয়া হয়েছে। Admin role-এর permission তালিকা dynamically সম্পূর্ণ permission seeder থেকে নেয়।

```php
// PermissionSeederTable
'supplier.inactive',
'money-receipt',

// RolePermissionSeeder: sales
'money-receipt',

// RolePermissionSeeder: manager
'supplier.inactive',
'money-receipt',
```

Product edit dropdown-এ আগে থেকেই যুক্ত inactive supplier-টিকে ধরে রাখা হয়:

```blade
@foreach ($suppliers as $supplier)
    <option value="{{ $supplier->id }}"
        {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
        {{ $supplier->is_active ? $supplier->name : $supplier->name . ' (Inactive - current supplier)' }}
    </option>
@endforeach
```
