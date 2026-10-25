# Quotation & System Updates Documentation

This document provides a comprehensive, production-grade record of all logic, code implementations, and functional changes across **Controllers**, **Migrations**, **Models**, **Blade Templates**, **Seeders**, and **Configuration Providers** for the 4 requested tasks:
1. **Sidebar Responsiveness Fix for All Users (Sales & Admin)**
2. **Product Seeder Population with Null Defaults (Except Product Name)**
3. **Organization Logo Display in Customer Ledger, Customer Invoice, and Login Page**
4. **"Get Quotation" Feature with Automatic Calculations, Organization & Customer Layout, Terms & Conditions**

---

## 1. Sidebar Responsiveness Fix (for Sales & All Roles)

### Problem Diagnosis
In `public/assets/js/app.js`, line 3 initialized PerfectScrollbar synchronously:
```javascript
new PerfectScrollbar(".header-message-list"), new PerfectScrollbar(".header-notifications-list"),
```
In `resources/views/dashboard/dashboard.blade.php`, the `.header-message-list` and `.header-notifications-list` DOM elements are guarded by `@can('product.index')`.
When an administrator logs in, they possess the `product.index` permission, so the elements exist in the DOM. However, when a `sales` user logs in, they lack `product.index`, causing `document.querySelector(".header-message-list")` to return `null`. This caused `PerfectScrollbar` to throw an uncaught exception:
`Error: no element is specified to initialize PerfectScrollbar`

Because of this uncaught exception at line 3 of `app.js`, all subsequent event bindings were aborted:
- Sidebar toggle button (`$(".toggle-icon").click(...)`)
- Mobile menu toggle (`$(".mobile-toggle-menu").on("click", ...)`)
- MetisMenu initialization (`$("#menu").metisMenu()`)

### Code Implementation

#### `public/assets/js/app.js`
```javascript
$(function() {
	"use strict";
	if ($(".header-message-list").length) {
		new PerfectScrollbar(".header-message-list");
	}
	if ($(".header-notifications-list").length) {
		new PerfectScrollbar(".header-notifications-list");
	}

	$(".mobile-search-icon").on("click", function() {
		$(".search-bar").addClass("full-search-bar")
	}),

	$(".search-close").on("click", function() {
		$(".search-bar").removeClass("full-search-bar")
	}),

	$(".mobile-toggle-menu").on("click", function() {
		$(".wrapper").toggleClass("toggled")
	}),
```
**Explanation:** By checking `$(".header-message-list").length` and `$(".header-notifications-list").length` before instantiating `PerfectScrollbar`, no exception is thrown for users without access to notifications, allowing sidebar toggling and responsive menus to work identically for sales, admin, and all other user roles.

#### `resources/views/dashboard/dashboard.blade.php`
```blade
    <script>
        if (document.querySelector(".app-container")) {
            new PerfectScrollbar(".app-container");
        }
    </script>
    <script>
        if (document.getElementById('myTable')) {
            let table = new DataTable('#myTable', {
                "pageLength": 5,
                "lengthMenu": [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ]
            });
        }
    </script>

    <script>
        const imageInput = document.getElementById('imageInput');
        if (imageInput) {
            imageInput.onchange = function(evt) {
                const [file] = this.files;
                if (file) {
                    const preview = document.getElementById('preview');
                    if (preview) {
                        preview.src = URL.createObjectURL(file);
                    }
                }
            };
        }
    </script>
    @yield('scripts')
```
**Explanation:** Added defensive DOM checks before initializing `PerfectScrollbar(".app-container")`, `DataTable('#myTable')`, and `#imageInput.onchange`. Replaced the static `@section('scripts')` in the layout with `@yield('scripts')` to allow child Blade templates to output page-specific scripts cleanly.

---

## 2. Product Nullable Migration & Product Seeder

### Problem & Solution
The `products` table schema originally had non-nullable constraints on `product_categorie_id`, `brand_id`, `price`, and `unit`. Setting these to `null` required altering the database table schema so that all columns other than `productName` are nullable.

### Migration
**File:** `database/migrations/2026_10_25_000001_make_product_fields_nullable_in_products_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_categorie_id')->nullable()->change();
            $table->unsignedBigInteger('brand_id')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->string('unit', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_categorie_id')->nullable(false)->change();
            $table->unsignedBigInteger('brand_id')->nullable(false)->change();
            $table->decimal('price', 8, 2)->nullable(false)->change();
            $table->string('unit', 100)->nullable(false)->change();
        });
    }
};
```
**Explanation:** Uses `$table->...->nullable()->change()` to modify foreign key and numeric columns to allow `null` values while preserving foreign key constraints.

### Seeder Implementation
**File:** `database/seeders/ProductSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Products = [
            '13.3"  DISPLAY DELL FHD CARKITLESS IPS', 
            '2.5 Inch SATAIII SSD 500GB',
            // ... 661 real product catalog items ...
            'YESPRINT TONER CARTRIDGE PC 216'
        ];

        foreach ($Products as $Product) {
            Product::create([
                'productName' => $Product,
                'product_categorie_id' => null,
                'brand_id' => null,
                'supplier_id' => null,
                'purchase_price' => null,
                'price' => null,
                'unit' => null,
                'stock_unit' => null,
                'purchase_date' => null,
                'mfg_date' => null,
                'expiry_date' => null,
                'description' => null,
                'img_url' => null,
            ]);
        }
    }
}
```
**Explanation:** Populates 661 catalog items from the user's product list into the database, setting all attributes to `null` by default except `productName`.

---

## 3. Organization Logo on Customer Ledger, Invoices, and Login Page

### Service Provider View Composer
**File:** `app/Providers/AppServiceProvider.php`

```php
View::composer([
    'backend.Sales.salesInvoice',
    'backend.Sales.salesReport',
    'backend.reports.productinventory',
    'backend.Accounts Entries.combinedVoucherReport',
    'backend.Accounts Entries.voucherEntryReport',
    'backend.Accounts Entries.VoucherEntries',
    'backend.Accounts Entries.employeePaymentLedger',
    'backend.customer.customerLedger',
    'backend.customer.customerLedgerInvoice',
    'backend.Supplier.supplierledger',
    'frontend.loginpage.login',
    'backend.quotation.*',
], function ($view) {
    $view->with('organization', Organization::query()->find(1));
});
```
**Explanation:** Registers `frontend.loginpage.login`, `backend.customer.customerLedgerInvoice`, and `backend.quotation.*` with the view composer to ensure the active organization model (`Organization::find(1)`) is automatically available in all these templates.

### Blade Templates

#### 1. Login Page: `resources/views/frontend/loginpage/login.blade.php`
```blade
<div class="mb-3 text-center">
    @if ($organization?->logo)
        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
            alt="{{ $organization->name ?: 'Organization Logo' }}"
            style="max-width: 140px; max-height: 80px; object-fit: contain;">
    @else
        <img src="{{ asset('assets/images/logo-icon.png') }}" width="60"
            alt="logo icon">
    @endif
</div>
<div class="mb-4 text-center">
    <h6 class="">Welcome to</h6>
    <h5 class="">{{ $organization?->name ?: 'My-POS' }}</h5>
    <p class="mb-0">Please log in to your account</p>
</div>
```
**Explanation:** Replaces the static logo icon on the organization login page with the uploaded organization logo and name, falling back to default branding if none is configured.

#### 2. Customer Ledger: `resources/views/backend/customer/customerLedger.blade.php`
```blade
@if ($organization?->logo || $organization?->name)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
                @if ($organization?->logo)
                    <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                        alt="{{ $organization->name ?: 'Organization logo' }}"
                        style="max-width: 90px; max-height: 60px; object-fit: contain;">
                @endif
                <div>
                    <h5 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h5>
                    <div class="small text-muted">
                        @if ($organization?->phone) Phone: {{ $organization->phone }} @endif
                        @if ($organization?->email) | Email: {{ $organization->email }} @endif
                        @if ($organization?->address) | {{ $organization->address }} @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
```
**Explanation:** Displays a prominent organization header with the logo, organization name, phone, email, and address at the top of the customer ledger screen.

#### 3. Customer Ledger Invoice / Receipt: `resources/views/backend/customer/customerLedgerInvoice.blade.php`
```blade
@if ($organization?->logo || $organization?->name)
    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
        @if ($organization?->logo)
            <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                alt="{{ $organization->name ?: 'Organization logo' }}"
                style="max-width: 90px; max-height: 65px; object-fit: contain;">
        @endif
        <div>
            <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
            @if ($organization?->address)<div class="small text-muted">{{ $organization->address }}</div>@endif
            @if ($organization?->phone)<div class="small text-muted">Phone: {{ $organization->phone }}</div>@endif
            @if ($organization?->email)<div class="small text-muted">Email: {{ $organization->email }}</div>@endif
        </div>
    </div>
@endif
```
**Explanation:** Formats the organization's logo and contact details at the top of the customer ledger invoice and payment receipt.

#### 4. Sales Invoice: `resources/views/backend/Sales/salesInvoice.blade.php`
```blade
@if ($organization?->logo || $organization?->name)
    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
        @if ($organization?->logo)
            <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                alt="{{ $organization->name ?: 'Organization logo' }}"
                style="max-width: 90px; max-height: 65px; object-fit: contain;">
        @endif
        <div>
            <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name }}</h4>
            @if ($organization?->address)<div class="small text-muted">{{ $organization->address }}</div>@endif
            @if ($organization?->phone)<div class="small text-muted">Phone: {{ $organization->phone }}</div>@endif
            @if ($organization?->email)<div class="small text-muted">Email: {{ $organization->email }}</div>@endif
        </div>
    </div>
@endif
```

---

## 4. "Get Quotation" Feature

### Database Migration
**File:** `database/migrations/2026_10_25_000002_create_quotations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no')->unique();
            $table->date('quotation_date');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type')->default('flat');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
```
**Explanation:** 
- `quotations`: Stores quotation headers including auto-generated quotation numbers, customer details, calculated monetary totals (subtotal, discount, VAT, grand total), terms & conditions, notes, and the author user ID.
- `quotation_items`: Child records for items added to the quotation with cascade deletion on quote removal.

---

### Eloquent Models

#### 1. `app/Models/Quotation.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_no',
        'quotation_date',
        'customer_name',
        'customer_phone',
        'customer_address',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'vat_percentage',
        'vat_amount',
        'total_amount',
        'terms_conditions',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'subtotal' => 'float',
        'discount_value' => 'float',
        'discount_amount' => 'float',
        'vat_percentage' => 'float',
        'vat_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

#### 2. `app/Models/QuotationItem.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'integer',
        'line_total' => 'float',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

### Controller: `app/Http/Controllers/QuotationController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    /**
     * Display a listing of quotations.
     */
    public function index()
    {
        $quotations = Quotation::with('user', 'items')
            ->latest('quotation_date')
            ->latest('id')
            ->get();

        return view('backend.quotation.index', compact('quotations'));
    }

    /**
     * Show the form for creating a new quotation.
     */
    public function create()
    {
        $products = Product::query()->orderBy('productName')->get();
        $organization = Organization::query()->find(1);

        // Generate next quotation number format: QT-YYYYMM-0001
        $currentMonth = Carbon::now()->format('Ym');
        $latestQuotation = Quotation::where('quotation_no', 'like', "QT-{$currentMonth}-%")
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($latestQuotation) {
            $parts = explode('-', $latestQuotation->quotation_no);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $nextNumber = (int)$parts[2] + 1;
            }
        }
        $generatedQuotationNo = "QT-{$currentMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $defaultTerms = "1. This quotation is valid for 15 days from the date of issue.\n2. Prices are inclusive/exclusive of applicable taxes as indicated.\n3. Delivery will be scheduled upon confirmation of the order.\n4. Payment terms: 50% advance, balance upon delivery.";

        return view('backend.quotation.create', compact('products', 'organization', 'generatedQuotationNo', 'defaultTerms'));
    }

    /**
     * Store a newly created quotation in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_address' => 'nullable|string|max:1000',
            'quotation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:flat,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0',
            'terms_conditions' => 'nullable|string|max:3000',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated) {
            $currentMonth = Carbon::parse($validated['quotation_date'])->format('Ym');
            $latestQuotation = Quotation::where('quotation_no', 'like', "QT-{$currentMonth}-%")
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $nextNumber = 1;
            if ($latestQuotation) {
                $parts = explode('-', $latestQuotation->quotation_no);
                if (isset($parts[2]) && is_numeric($parts[2])) {
                    $nextNumber = (int)$parts[2] + 1;
                }
            }
            $quotationNo = "QT-{$currentMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $lineTotal = round($qty * $unitPrice, 2);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'product_name' => trim($item['product_name']),
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($itemsData)) {
                throw ValidationException::withMessages(['items' => 'Please add at least one valid product item.']);
            }

            $discountType = $validated['discount_type'] ?? 'flat';
            $discountValue = (float)($validated['discount_value'] ?? 0);
            $discountAmount = 0;

            if ($discountType === 'percentage') {
                $discountAmount = round(($subtotal * $discountValue) / 100, 2);
            } else {
                $discountAmount = min(round($discountValue, 2), $subtotal);
            }

            $vatPercentage = (float)($validated['vat_percentage'] ?? 0);
            $taxableSubtotal = max($subtotal - $discountAmount, 0);
            $vatAmount = round(($taxableSubtotal * $vatPercentage) / 100, 2);

            $grandTotal = round($taxableSubtotal + $vatAmount, 2);

            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'quotation_date' => $validated['quotation_date'],
                'customer_name' => trim($validated['customer_name']),
                'customer_phone' => trim($validated['customer_phone']),
                'customer_address' => trim($validated['customer_address'] ?? ''),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'vat_percentage' => $vatPercentage,
                'vat_amount' => $vatAmount,
                'total_amount' => $grandTotal,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            foreach ($itemsData as $data) {
                $quotation->items()->create($data);
            }

            return redirect()->route('quotation.show', $quotation->id)
                ->with('success', 'Quotation generated successfully.');
        });
    }

    /**
     * Display the specified quotation document.
     */
    public function show($id)
    {
        $quotation = Quotation::with('items', 'user')->findOrFail($id);
        $organization = Organization::query()->find(1);

        return view('backend.quotation.show', compact('quotation', 'organization'));
    }

    /**
     * Remove the specified quotation from storage.
     */
    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);
        $quotation->delete();

        return redirect()->route('quotation.index')
            ->with('success', 'Quotation deleted successfully.');
    }
}
```

**Key Function Explanations:**
1. `create()`: Prepares the "Get Quotation" screen by fetching products, resolving the organization profile, generating the next sequential reference code (`QT-YYYYMM-XXXX`), and loading editable default terms and conditions.
2. `store()`: Validates customer credentials, row items, discount type, and VAT. Runs inside a database transaction with table lock to prevent collision in sequential numbering. Recalculates line totals, discounts, taxes, and grand totals server-side before persisting records.
3. `show()`: Displays the finalized quotation document formatted for on-screen review and high-resolution printing.
4. `index()` & `destroy()`: Provides record history management and safe deletion.

---

### Routes & Sidebar Integration

#### `routes/web.php`
```php
// মিডলওয়্যার সহ কোটেশন (Quotation) রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/quotations', [QuotationController::class, 'index'])->middleware('permission:sales.create|sales.view')->name('quotation.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('permission:sales.create')->name('quotation.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('permission:sales.create')->name('quotation.store');
    Route::get('/quotations/{id}', [QuotationController::class, 'show'])->middleware('permission:sales.create|sales.view')->name('quotation.show');
    Route::delete('/quotations/{id}', [QuotationController::class, 'destroy'])->middleware('permission:sales.create')->name('quotation.destroy');
});
```

#### Sidebar: `resources/views/dashboard/dashboard.blade.php`
```blade
@canany(['sales.create', 'sales.view'])
    <li class="menu-label">Regural Operation</li>
    <li>
        <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class='bx bx-cart-alt'></i></div>
            <div class="menu-title">Sales</div>
        </a>
        <ul>
            @can('sales.create')
                <li>
                    <a href="{{ route('sales.index') }}"><i class='bx bx-plus-medical'></i>Create Sale</a>
                </li>
            @endcan
        </ul>
    </li>
    <li>
        <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class='bx bx-file'></i></div>
            <div class="menu-title">Quotation</div>
        </a>
        <ul>
            <li>
                <a href="{{ route('quotation.create') }}"><i class='bx bx-plus-circle'></i>Get Quotation</a>
            </li>
            <li>
                <a href="{{ route('quotation.index') }}"><i class='bx bx-list-ul'></i>All Quotations</a>
            </li>
        </ul>
    </li>
@endcan
```

---

### Blade Views

#### 1. `resources/views/backend/quotation/create.blade.php`
- **Customer Information Fields:** Name, Mobile Number, Quotation Date, Reference, Address.
- **Organization Panel:** Rendered side-by-side with customer details, displaying organization logo, name, phone, email, and address.
- **Dynamic Item Entry:**
  - Auto-filling product datalist dropdown (`#quotationProductOptions`).
  - Unit Price and Quantity inputs with real-time recalculations.
  - "Add Product" button dynamically appends new item rows.
  - Trash button removes row and recalculates totals instantly.
- **Price Calculation Panel:** Real-time JavaScript handles Subtotal, Discount (Flat ৳ or Percentage %), VAT rate (%), and Grand Total.
- **Terms & Conditions Section:** Editable textarea pre-populated with standard business terms, plus an optional notes field.

#### 2. `resources/views/backend/quotation/show.blade.php`
- **Document Header:** Titled **"Quotation"** with reference number and issue date.
- **Layout:**
  - Left side: Organization Logo, Name, Address, Phone, and Email.
  - Right side: Customer Name, Phone, Address, and Date.
- **Table:** `#`, `Product Description`, `Unit Price (৳)`, `Quantity`, and `Total Amount (৳)`.
- **Totals Section:** Formatted identically to the customer invoice (Subtotal, Discount deduction, VAT addition, Total Price).
- **Terms & Conditions:** Renders the input terms and conditions in a distinct bordered block.
- **Signatures & Print:** Includes "Customer Acceptance" and "Authorized Signature" lines, along with a `@media print` stylesheet that removes web chrome and produces clean printouts.

#### 3. `resources/views/backend/quotation/index.blade.php`
- Displays all saved quotations with sequential Reference number, Customer Name, Contact Number, Total Price, Created By employee, and Action buttons (Print/View and Delete).
