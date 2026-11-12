# পুরোনো Sales Invoice সম্পাদনার অনুমতি

`Update_anyday_Invoice` permission থাকা ব্যবহারকারী এখন পুরোনোসহ যেকোনো তারিখের Sales Invoice সম্পাদনা করতে পারবেন। `Update_today_Invoice` আগের মতোই শুধু আজকের invoice সম্পাদনার অনুমতি দেয়।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/SaleController.php`

```php
$user = auth()->user();
abort_unless(
    $user?->hasRole('admin')
        || $user?->can('Update_anyday_Invoice')
        || ($user?->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()),
    403
);
```

`editInvoice`-এ এই যাচাই edit form খোলার অনুমতি নিয়ন্ত্রণ করে। বিদ্যমান admin role-এর access বজায় আছে। একই authorization `app/Support/SaleInvoiceUpdateService.php`-এর `update` method-এও transaction-এর মধ্যে প্রয়োগ করা হয়েছে। ফলে সরাসরি PUT request করেও তারিখ বা permission যাচাই এড়ানো যাবে না।

**Permission নিবন্ধন — `database/seeders/PermissionSeederTable.php`:**

```php
'Update_today_Invoice',
'Update_anyday_Invoice',
```

Seeder চালালে permission-টি permission তালিকায় তৈরি হবে। বিদ্যমান admin role-এর access রাখা হয়েছে; admin preset-এ registered permission-গুলোও অন্তর্ভুক্ত হয়। অন্য role-এ এই permission প্রয়োজন হলে আলাদাভাবে assign করতে হবে। `Update_anyday_Invoice` sales/manager preset-এ স্বয়ংক্রিয়ভাবে যোগ করা হয়নি।

## Migration (Code and Explanation)

কোনো migration যোগ বা পরিবর্তন করা হয়নি। নতুন database column বা table প্রয়োজন নেই; শুধু বিদ্যমান permission ব্যবস্থায় একটি permission নিবন্ধন করা হয়েছে।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php`

```php
Route::get('/sales', [SaleController::class, 'index'])
    ->middleware('permission:sales.create|sales.view|Voide_today_Invoice|Voide_anyday_Invoice|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.index');

Route::get('/sales/invoice/{id}', [SaleController::class, 'invoice'])
    ->middleware('role_or_permission:admin|sales.report|Voide_today_Invoice|Voide_anyday_Invoice|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.invoice');

Route::get('/sales/invoice/{id}/edit', [SaleController::class, 'editInvoice'])
    ->middleware('role_or_permission:admin|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.invoice.edit');

Route::put('/sales/invoice/{id}', [SaleController::class, 'updateInvoice'])
    ->middleware('role_or_permission:admin|Update_today_Invoice|Update_anyday_Invoice')
    ->name('sales.invoice.update');
```

`|` দিয়ে edit route-এ admin role অথবা দুই permission-এর যেকোনো একটি গ্রহণ করা হয়। Sales তালিকা ও invoice দেখার route-এও নতুন permission যোগ করা হয়েছে, যাতে `Update_anyday_Invoice`-ধারী ব্যবহারকারী invoice পর্যন্ত যেতে পারেন।

## Model (Code and Explanation)

কোনো model পরিবর্তন করা হয়নি। বিদ্যমান `SaleInvoice` model এবং permission package-এর permission model-ই ব্যবহৃত হয়েছে।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php`

```blade
@if (auth()->user()->hasRole('admin') ||
        auth()->user()->can('Update_anyday_Invoice') ||
        (auth()->user()->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()))
    <a href="{{ route('sales.invoice.edit', $invoice->id) }}" class="btn btn-outline-primary">
        <i class="bx bx-edit me-1"></i>Update Invoice
    </a>
@endif
```

পুরোনো invoice-এর View পেজেও admin এবং `Update_anyday_Invoice` ব্যবহারকারীর জন্য Update Invoice বোতাম দেখা যায়। `resources/views/backend/Sales/salesIndex.blade.php`-এর sales তালিকার permission check-এও নতুন permission যোগ করা হয়েছে।

## পরীক্ষা (Code and Explanation)

**ফাইল:** `tests/Feature/SalePaymentTest.php`

পুরোনো invoice-এ শুধু `Update_today_Invoice` থাকলে edit ও update প্রত্যাখ্যাত হয়—এটি আগের মতোই যাচাই করা হয়েছে। এরপর admin role-এর আগের access এবং শুধু `Update_anyday_Invoice`-ধারী ব্যবহারকারীর sales তালিকা, invoice view, edit form ও update—সব যাচাই করা হয়েছে।

```php
$anydayUser = User::factory()->create(['email_verified_at' => now()]);
$anydayUser->givePermissionTo(Permission::findOrCreate('Update_anyday_Invoice', 'web'));

$this->actingAs($anydayUser)
    ->get(route('sales.invoice.edit', $invoice->id))
    ->assertOk()
    ->assertViewIs('backend.Sales.salesIndex');

$this->actingAs($anydayUser)
    ->put(route('sales.invoice.update', $invoice->id), [
        'discount_type' => 'amount',
        'discount_value' => 0,
        'vat_percentage' => 0,
        'ait_percentage' => 0,
        'amount_paid' => 300,
        'payment_method' => 'bank',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 150],
        ],
    ])
    ->assertRedirect(route('sales.invoice', $invoice->id))
    ->assertSessionHas('success');
```
