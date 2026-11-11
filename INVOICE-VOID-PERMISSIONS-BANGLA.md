# Invoice Void Permission (বাংলা)

Invoice বাতিলের জন্য দুটি permission যোগ করা হয়েছে:

- `Voide_today_Invoice`: শুধু আজকের invoice বাতিল করা যাবে।
- `Voide_anyday_Invoice`: যেকোনো তারিখের invoice বাতিল করা যাবে।

Permission-এর নাম ব্যবহারকারীর দেওয়া `Voide` বানান রেখে `Invoice`-এর বানান একরূপ করা হয়েছে। Role/permission ব্যবস্থাপনা থেকে প্রয়োজন অনুযায়ী এগুলো নির্দিষ্ট role-এ বরাদ্দ করতে হবে। `sales.create` permission একা আর invoice বাতিলের অনুমতি দেয় না।

## Controller (Code and Explanation)

`SaleController::destroy()` আগের মতোই `SaleInvoiceVoidService`-কে invoice বাতিলের দায়িত্ব দেয়। এই সার্ভিসটি controller এবং combined voucher report—দুই entry point থেকেই ব্যবহৃত হয় বলে permission ও তারিখের নিয়ম সার্ভিসের শুরুতেই যাচাই করা হয়:

```php
$invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($id);
$user = auth()->user();
$canVoidAnyDay = $user?->can('Voide_anyday_Invoice') ?? false;
$canVoidToday = $user?->can('Voide_today_Invoice') ?? false;

abort_unless(
    $canVoidAnyDay || ($canVoidToday && $invoice->invoice_date?->isToday()),
    403
);
```

`Voide_anyday_Invoice` থাকলে তারিখ নির্বিশেষে বাতিল করা যায়। শুধু `Voide_today_Invoice` থাকলে invoice-এর তারিখ আজকের হতে হবে। কোনো permission না থাকলে বা তারিখ না মিললে অনুরোধ `403` দিয়ে বন্ধ হয়। এই যাচাই rollback transaction-এর ভেতর invoice lock করার পর চলে, ফলে shared service-এর অন্য call site দিয়েও নিয়ম এড়িয়ে যাওয়া যায় না।

## Permission Seeder (Code and Explanation)

**ফাইল:** `database/seeders/PermissionSeederTable.php` — sales permission-গুলোর তালিকায়:

```php
'sales.document.watermark',
'Voide_today_Invoice',
'Voide_anyday_Invoice',
```

Seeder চালালে দুটি permission তৈরি হবে। এগুলো স্বয়ংক্রিয়ভাবে sales/manager role-এ দেওয়া হয়নি, যাতে invoice বাতিলের ক্ষমতা কেবল প্রয়োজনীয় role-এ বরাদ্দ করা যায়। Admin role-এর permission তালিকা seeder থেকে পাওয়া সব permission অন্তর্ভুক্ত করে।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন করা হয়নি। নতুন database column বা table প্রয়োজন নেই; permission-গুলো বিদ্যমান Spatie permission table-এ seeder দিয়ে যোগ হয়।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php` — invoice list, detail এবং DELETE route:

```php
Route::get('/sales', [SaleController::class, 'index'])
    ->middleware('permission:sales.create|sales.view|Voide_today_Invoice|Voide_anyday_Invoice')
    ->name('sales.index');

Route::get('/sales/invoice/{id}', [SaleController::class, 'invoice'])
    ->middleware('permission:sales.report|Voide_today_Invoice|Voide_anyday_Invoice')
    ->name('sales.invoice');

Route::delete('/sales/invoice/{id}', [SaleController::class, 'destroy'])
    ->middleware('permission:Voide_today_Invoice|Voide_anyday_Invoice')
    ->name('sales.destroy');
```

Void permission-প্রাপ্ত ব্যবহারকারী sales page-এর সাম্প্রতিক invoice তালিকা ও invoice detail খুলতে পারেন, যাতে তাদের invoice বাতিলের button-এ পৌঁছানোর পথ থাকে। DELETE route-এ নতুন দুটি permission-এর যেকোনো একটি থাকতে হবে; `sales.create` একা আর যথেষ্ট নয়। Middleware-এর পাশাপাশি shared service-ও আজকের/যেকোনো দিনের সীমা যাচাই করে।

## Model (Code and Explanation)

এই পরিবর্তনে কোনো model যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান `SaleInvoice` model-এর `invoice_date` ব্যবহার করে আজকের তারিখ যাচাই করা হয়।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/dashboard/dashboard.blade.php` ও `resources/views/backend/Sales/salesIndex.blade.php` — Void permission-প্রাপ্ত ব্যবহারকারীদের Recent Invoices-এ যাওয়ার menu এবং invoice list:

```blade
@canany(['sales.view', 'Voide_today_Invoice', 'Voide_anyday_Invoice'])
    <li>
        <a href="{{ route('sales.index') }}"><i class='bx bx-list-ul'></i>Recent Invoices</a>
    </li>
@endcanany

@canany(['sales.view', 'Voide_today_Invoice', 'Voide_anyday_Invoice'])
    {{-- Recent Invoices table --}}
@endcanany
```

এই permission-গুলো থাকলে sales menu ও সাম্প্রতিক invoice তালিকায় প্রবেশ পাওয়া যায়; invoice তৈরির form এখনও কেবল `sales.create`-এর জন্যই থাকে।

**ফাইল:** `resources/views/backend/Sales/salesInvoice.blade.php` — invoice action button:

```blade
@if (auth()->user()->can('Voide_anyday_Invoice') ||
        (auth()->user()->can('Voide_today_Invoice') && $invoice->invoice_date?->isToday()))
    <form action="{{ route('sales.destroy', $invoice->id) }}" method="POST"
        onsubmit="return confirm('Delete this invoice? If it is linked to a sale voucher, the invoice will be voided and its stock, ledger, payment voucher amounts, and due balance will be reversed.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="bx bx-trash me-1"></i>Void Invoice
        </button>
    </form>
@endif
```

যেকোনো দিনের permission থাকলে সব invoice-এ Void button দেখা যায়। শুধু আজকের permission থাকলে আজকের invoice-এ button দেখা যায়; পুরোনো invoice-এর button দেখানো হয় না। Route এবং service-এও অনুমতি যাচাই হয়, তাই শুধু Blade থেকে button লুকোনোই নিরাপত্তার একমাত্র ব্যবস্থা নয়।

## স্বয়ংক্রিয় পরীক্ষা

`tests/Feature/SalePaymentTest.php`-এ আজকের invoice-এ আজকের permission দিয়ে void এবং পুরোনো invoice-এ শুধু আজকের permission দিয়ে প্রত্যাখ্যান—এরপর যেকোনো দিনের permission যোগ করে সফল void—যাচাই করা হয়েছে। `tests/Feature/ProductReturnTest.php` ও `tests/Feature/CombinedVoucherReportTest.php`-এ সংশ্লিষ্ট invoice void test-গুলোকেও নতুন permission দেওয়া হয়েছে।
