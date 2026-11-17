# Product Subcategory — বাংলা কোড ও শেখার নোট

এই নথিতে Product Category-এর অধীনে Subcategory যোগ করার কোড, কোন ফাইলে আছে,
কীভাবে কাজ করে এবং কেন যোগ করা হয়েছে তা বাংলায় ব্যাখ্যা করা হয়েছে।
কোডের আসল কপি সংশ্লিষ্ট source file-এ আছে; নিচের code block-গুলো পরিবর্তিত
অংশের raw code, আর তার পরের ব্যাখ্যায় code-এর প্রতিটি কার্যকর লাইন/markup-এর
কাজ বলা হয়েছে।

## Controller (Code and Explanation)

### Subcategory CRUD ও JSON endpoint

**ফাইল:** [ProductSubcategoryController.php](./app/Http/Controllers/ProductSubcategoryController.php)

```php
public function index(): View
{
    $subcategories = ProductSubcategory::query()
        ->with('category')
        ->withCount('products')
        ->orderBy('name')
        ->get();

    return view('backend.Product.Subcategory.index', compact('subcategories'));
}

public function create(Request $request): View
{
    $categories = ProductCategory::query()->orderBy('category_name')->get();
    $selectedCategoryId = $request->integer('category_id');
    $isModal = $request->ajax();
    $view = $isModal
        ? 'backend.Product.Subcategory._form'
        : 'backend.Product.Subcategory.create';

    return view($view, compact('categories', 'selectedCategoryId', 'isModal'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'category_id' => ['required', 'integer', 'exists:product_categories,id'],
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('product_subcategories', 'name')
                ->where(fn ($query) => $query->where('category_id', $request->input('category_id'))),
        ],
    ]);

    $subcategory = ProductSubcategory::create($validated);

    if ($request->expectsJson()) {
        return response()->json([
            'subcategory' => [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'category_id' => $subcategory->category_id,
            ],
        ], 201);
    }

    return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully.');
}

public function edit(ProductSubcategory $subcategory): View
{
    $categories = ProductCategory::query()->orderBy('category_name')->get();

    return view('backend.Product.Subcategory.edit', compact('subcategory', 'categories'));
}

public function update(Request $request, ProductSubcategory $subcategory)
{
    $validated = $request->validate([
        'category_id' => ['required', 'integer', 'exists:product_categories,id'],
        'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('product_subcategories', 'name')
                ->where(fn ($query) => $query->where('category_id', $request->input('category_id')))
                ->ignore($subcategory->id),
        ],
    ]);

    DB::transaction(function () use ($subcategory, $validated): void {
        $subcategory->update($validated);
        $subcategory->products()->update([
            'product_categorie_id' => $validated['category_id'],
        ]);
    });

    return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully.');
}

public function destroy(ProductSubcategory $subcategory)
{
    $subcategory->delete();

    return redirect()->route('subcategories.index')->with('success', 'Subcategory deleted successfully.');
}

public function options(ProductCategory $category): JsonResponse
{
    return response()->json([
        'subcategories' => $category->subcategories()
            ->orderBy('name')
            ->get(['id', 'category_id', 'name']),
    ]);
}
```

**লাইন/ব্লক ব্যাখ্যা**

- `index()`-এর `query()` সাবক্যাটাগরির তালিকার query শুরু করে; `with('category')`
  parent category-কে একসঙ্গে load করে; `withCount('products')` প্রতিটি row-তে
  পণ্যের সংখ্যা দেয়; `orderBy()` নাম অনুযায়ী সাজায়; `get()` ফলাফল নেয়।
- `return view(...)` তালিকা ও তার ডেটা `Subcategory/index.blade.php`-এ পাঠায়।
- `create()` সব Product Category dropdown-এ পাঠায়। `integer()` modal-এর
  `category_id` query parameter-কে integer হিসেবে নেয়; `ajax()` দিয়ে modal request
  আর standalone create page আলাদা করা হয়।
- `$view` ternary-এর প্রথম Blade হলো modal-এর জন্য শুধু form partial; অন্যটি
  পুরো create page। `compact()` প্রয়োজনীয় data-গুলো view-তে পাঠায়।
- `store()`-এর `category_id` validation নিশ্চিত করে parent category আছে; `name`
  বাধ্যতামূলক, string ও 255 অক্ষরের মধ্যে। `Rule::unique()->where(...)` একই
  category-র মধ্যে duplicate নাম ঠেকায়, কিন্তু ভিন্ন category-তে একই নাম রাখা যায়।
- `create($validated)` যাচাইকৃত category ও নাম দিয়ে DB row তৈরি করে।
- `expectsJson()` modal-এর AJAX অনুরোধ শনাক্ত করে; `response()->json()` নতুন
  subcategory-এর id, নাম ও parent category ফেরায়, আর `201` হলো created status।
- JSON না চাইলে `redirect()->route(...)` তালিকায় ফেরত পাঠায় ও success message দেয়।
- `edit()` route model binding দিয়ে নির্দিষ্ট সাবক্যাটাগরি নেয়; edit dropdown-এর জন্য
  category list পাঠায়।
- `update()` store-এর মতোই validation করে, তবে `ignore($subcategory->id)` বর্তমান
  row-টিকে duplicate হিসেবে গণনা করে না। `$subcategory->update()` validated
  তথ্য সংরক্ষণ করে। `DB::transaction()` একই সময়ে subcategory-এর parent category-ও
  update করে এবং তার সঙ্গে যুক্ত product-গুলোর `product_categorie_id` মিলিয়ে দেয়;
  এতে category/subcategory সম্পর্ক অমিল হয় না।
- `destroy()` নির্বাচিত row মুছে তালিকায় ফেরায়। Migration-এর `nullOnDelete()`-এর
  কারণে এই সাবক্যাটাগরিতে থাকা পণ্য মুছবে না; তাদের `subcategory_id` null হবে।
- `options()` category model-এর relationship দিয়েই ঐ category-র সাবক্যাটাগরি আনে।
  ফলে অন্য category-র সাবক্যাটাগরি API response-এ আসতে পারে না।

### Product save-এ parent/child মিল যাচাই

**ফাইল:** [ProductController.php](./app/Http/Controllers/ProductController.php)

```php
'product_categorie_id' => ['required', 'integer', 'exists:product_categories,id'],
'subcategory_id' => [
    'required',
    'integer',
    Rule::exists('product_subcategories', 'id')
        ->where('category_id', $request->input('product_categorie_id')),
],
```

- প্রথম rule category নির্বাচন বাধ্যতামূলক করে এবং database-এ তার অস্তিত্ব যাচাই করে।
- দ্বিতীয় rule subcategory নির্বাচন বাধ্যতামূলক করে; `Rule::exists()` subcategory
  থাকতে হবে বলে; `where()` নিশ্চিত করে সেটি একই selected category-র child।
- `store()`-এর `Product::create()` array-তে
  `'subcategory_id' => $request->subcategory_id` এবং `update()`-এর array-তে
  `'subcategory_id' => $request->subcategory_id` যুক্ত হয়েছে। তাই দুটো পথেই
  নির্বাচিত parent ও child একই product row-তে সেভ হয়।

### Inventory report-এর filter logic

**ফাইল:** [ProductInventoryReportController.php](./app/Http/Controllers/ProductInventoryReportController.php)

```php
$subcategories = ProductSubcategory::query()
    ->with('category:id,category_name')
    ->orderBy('name')
    ->get(['id', 'category_id', 'name']);

'subcategory_id' => ['nullable', 'integer', 'exists:product_subcategories,id'],

if (
    isset($filters['subcategory_id'])
    && (int) $row['subcategory_id'] !== (int) $filters['subcategory_id']
) {
    return false;
}

'subcategory_id' => $product->subcategory_id,
```

- প্রথম query report filter dropdown-এর জন্য সব subcategory ও parent category-র নাম
  একবারে আনে; `get()` শুধু প্রয়োজনীয় তিনটি column নেয়।
- view data array-তে `subcategories` পাঠানো হয়েছে, যাতে Blade dropdown render করতে পারে।
- validation rule filter-কে optional রাখে; দেওয়া হলে সেটি integer ও database-এর
  বিদ্যমান subcategory হতে হবে।
- `filteredRows()`-এর condition selected subcategory-র সঙ্গে row ID না মিললে
  product row বাদ দেয়। category ও stock filter আগের মতোই পাশাপাশি কাজ করে।
- `reportRows()`-এর row array-তে `subcategory_id` রাখা হয়, যাতে একই row data
  report screen ও CSV export—দুটোতেই filter করা যায়।

## Migration (Code and Explanation)

### সাবক্যাটাগরি টেবিল

**ফাইল:** [2026_11_17_000001_create_product_subcategories_table.php](./database/migrations/2026_11_17_000001_create_product_subcategories_table.php)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subcategories');
    }
};
```

- `Schema::create()` `product_subcategories` table বানায়।
- `id()` primary key তৈরি করে।
- `foreignId('category_id')` parent category ID রাখে; `constrained(...)` সেটিকে
  `product_categories.id`-এর foreign key করে; `cascadeOnDelete()` category মুছলে
  তার subcategory-গুলোও মুছে দেয়।
- `string('name')` subcategory-র নাম রাখে।
- `timestamps()` Laravel-এর `created_at` এবং `updated_at` যোগ করে।
- `unique(['category_id', 'name'])` একই category-তে duplicate নাম database স্তরেও
  প্রতিরোধ করে।
- `down()`-এর `dropIfExists()` rollback-এ শুধু এই নতুন table সরায়।

### Product-এর সঙ্গে nullable সম্পর্ক

**ফাইল:** [2026_11_17_000002_add_subcategory_id_to_products_table.php](./database/migrations/2026_11_17_000002_add_subcategory_id_to_products_table.php)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('product_categorie_id')
                ->constrained('product_subcategories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
        });
    }
};
```

- `Schema::table()` বিদ্যমান `products` table-এ নতুন column যোগ করে।
- `foreignId('subcategory_id')` subcategory-এর ID রাখে।
- `nullable()` পুরোনো product-গুলোর জন্য null গ্রহণ করে; migration চালালে পুরোনো
  inventory row-গুলোকে কৃত্রিম subcategory দিতে হয় না।
- `after(...)` database-এ category field-এর পর column রাখে (সমর্থিত engine-এ)।
- `constrained(...)` subcategory table-এর ID-র সঙ্গে FK সম্পর্ক তৈরি করে।
- `nullOnDelete()` কোনো subcategory মুছলে product অক্ষত রেখে তার child link null করে।
- `down()`-এর `dropConstrainedForeignId()` rollback-এ column ও constraint একসঙ্গে সরায়।

## Route (Code and Explanation)

**ফাইল:** [web.php](./routes/web.php)

```php
use App\Http\Controllers\ProductSubcategoryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product/subcategories', [ProductSubcategoryController::class, 'index'])
        ->middleware('permission:product.subcategory.index')->name('subcategories.index');
    Route::get('/product/subcategories/create', [ProductSubcategoryController::class, 'create'])
        ->middleware('permission:product.subcategory.create')->name('subcategories.create');
    Route::post('/product/subcategories', [ProductSubcategoryController::class, 'store'])
        ->middleware('permission:product.subcategory.create')->name('subcategories.store');
    Route::get('/product/subcategories/{subcategory}/edit', [ProductSubcategoryController::class, 'edit'])
        ->middleware('permission:product.subcategory.edit')->name('subcategories.edit');
    Route::put('/product/subcategories/{subcategory}', [ProductSubcategoryController::class, 'update'])
        ->middleware('permission:product.subcategory.update')->name('subcategories.update');
    Route::delete('/product/subcategories/{subcategory}', [ProductSubcategoryController::class, 'destroy'])
        ->middleware('permission:product.subcategory.delete')->name('subcategories.destroy');
});

Route::get('/product/categories/{category}/subcategories', [ProductSubcategoryController::class, 'options'])
    ->middleware('permission:product.create|product.edit')
    ->whereNumber('category')
    ->name('product.subcategories');
```

- `auth` ও `verified` login করা এবং verified user-দেরই CRUD route-এ ঢুকতে দেয়।
- `GET` index/create/edit route-গুলো screen দেখায়; `POST` create, `PUT` update,
  `DELETE` delete action চালায়।
- প্রতিটি CRUD route-এ আলাদা Spatie permission দেওয়া হয়েছে; যেমন create page ও save
  request দুটোতেই `product.subcategory.create` লাগে।
- `{subcategory}` model binding-এর মাধ্যমে নির্দিষ্ট সাবক্যাটাগরি controller-এ পৌঁছায়।
- `options` GET endpoint শুধু product create/edit অনুমতিপ্রাপ্ত user-কে নির্বাচিত
  category-র subcategory JSON দেয়।
- `whereNumber('category')` URL-এর category key-কে সংখ্যায় সীমাবদ্ধ রাখে।
- `name()` Blade ও controller থেকে স্থিতিশীল route name ব্যবহারের সুযোগ দেয়।

## Model (Code and Explanation)

### নতুন Subcategory model

**ফাইল:** [ProductSubcategory.php](./app/Models/ProductSubcategory.php)

```php
class ProductSubcategory extends Model
{
    protected $fillable = ['category_id', 'name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }
}
```

- `ProductSubcategory` Eloquent model হিসেবে নতুন table-এর সঙ্গে relation রাখে।
- `$fillable` mass assignment-এ যাচাইকৃত category ID ও নাম সেভ করতে দেয়।
- `category()` অনেক subcategory থেকে একটি parent category-র `belongsTo` relation।
- `products()` একটি subcategory-র সঙ্গে যুক্ত একাধিক product দেখায়।

### আগের model-এ যুক্ত relation

**ফাইল:** [ProductCategory.php](./app/Models/ProductCategory.php)

```php
public function subcategories(): HasMany
{
    return $this->hasMany(ProductSubcategory::class, 'category_id');
}
```

এই relation category থেকে তার সব subcategory query করার পথ দেয়। `Product`
model-এ `$fillable`-এর মধ্যে `subcategory_id` এবং `subcategory()`-এর
`belongsTo(ProductSubcategory::class, 'subcategory_id')` যোগ করা হয়েছে; এতে product
থেকে child record পড়া যায় এবং create/update-এর mass assignment-এ ID সেভ হয়।

**ফাইল:** [Product.php](./app/Models/Product.php)

```php
'subcategory_id',

public function subcategory()
{
    return $this->belongsTo(ProductSubcategory::class, 'subcategory_id');
}
```

`subcategory_id` `$fillable` list-এ থাকায় validated product create/update data save হয়;
`subcategory()` product row-কে child model-এ resolve করে।

## Blade Template (Code and Explanation)

### Product create/edit form-এ child dropdown

**ফাইল:** [subcategory-field.blade.php](./resources/views/backend/Product/product/partials/subcategory-field.blade.php)

```blade
<label for="subcategory_id">Subcategory <span class="text-danger">*</span></label>
@can('product.subcategory.create')
    <button type="button" data-bs-toggle="modal" data-bs-target="#subcategoryCreateModal">
        <i class="bx bx-category-alt"></i>
    </button>
@else
    <span aria-hidden="true"><i class="bx bx-category-alt"></i></span>
@endcan
<select name="subcategory_id" id="subcategory_id"
    data-options-url="{{ route('product.subcategories', ['category' => '__CATEGORY__']) }}"
    data-selected="{{ $selectedSubcategoryId }}" required>
    <option value="">Select Subcategory</option>
</select>
```

- `label` required field-টি চিহ্নিত করে।
- `@can` থাকলে icon-টি modal খোলে; অনুমতি না থাকলে একই icon শুধু visual indicator,
  action চালায় না।
- `data-options-url` JavaScript-কে category-ভিত্তিক JSON endpoint দেয়; placeholder-টি
  নির্বাচিত category ID দিয়ে বদলানো হয়।
- `data-selected` create validation error বা edit screen-এ আগের subcategory আবার
  select করতে সাহায্য করে।
- `required` product save-এর আগে একটি child নির্বাচন বাধ্যতামূলক করে।

### Modal-এ দ্রুত সাবক্যাটাগরি তৈরি

**ফাইল:** [subcategory-modal.blade.php](./resources/views/backend/Product/product/partials/subcategory-modal.blade.php)

```blade
@can('product.subcategory.create')
    <div class="modal fade" id="subcategoryCreateModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="subcategoryCreateModalBody">
                    Loading subcategory form...
                </div>
            </div>
        </div>
    </div>
@endcan
```

`@can` server-side permission gate; তাই অনুমতি না থাকলে modal root-ই render হয় না।
Modal body AJAX-এ category dropdown-সহ form নেয়। Create/edit product view-এ এই partial
form-এর বাইরে include করা হয়েছে, যাতে modal-এর form product form-এর ভেতরে nested না হয়।

**ফর্মের source:** [\_form.blade.php](./resources/views/backend/Product/Subcategory/_form.blade.php)

- Category dropdown-এ সব বিদ্যমান Product Category আসে; category না থাকলে user
  subcategory তৈরি করতে পারে না।
- `name` input subcategory-র নাম নেয়; POST route server-এ category ও name validate করে।
- standalone create page-এ Cancel তালিকায় ফেরায়; modal-এ Cancel modal বন্ধ করে।
- [create.blade.php](./resources/views/backend/Product/Subcategory/create.blade.php),
  [edit.blade.php](./resources/views/backend/Product/Subcategory/edit.blade.php) এবং
  [index.blade.php](./resources/views/backend/Product/Subcategory/index.blade.php)
  যথাক্রমে standalone create, update ও CRUD list screen।
- Delete button `DELETE` form submit করে; action-টি `product.subcategory.delete`
  permission দ্বারা সুরক্ষিত।

### Dropdown-এর JavaScript আচরণ

**ফাইল:** [subcategory-script.blade.php](./resources/views/backend/Product/product/partials/subcategory-script.blade.php)

কোডের কার্যকর ধাপগুলো:

1. `DOMContentLoaded` callback-এ category select, subcategory select, error text,
   modal ও modal body element-গুলো খোঁজা হয়।
2. `loadSubcategories(categoryId, selectedId)` dropdown-কে loading state-এ নেয়;
   category না থাকলে child select বন্ধ করে function শেষ করে।
3. Category ID endpoint URL-এ বসিয়ে `fetch()` JSON নেয়; HTTP error বা response shape
   ভুল হলে error message user-কে দেখায়।
4. Success response-এ option-গুলো category অনুযায়ী তৈরি হয়; `selectedId` মিলে গেলে
   আগের/current child option selected হয়।
5. Category dropdown change হলে নতুন category-র child list load হয়; ফলে আগের
   category-র সাবক্যাটাগরি আর নির্বাচনযোগ্য থাকে না।
6. Modal খোলার সময় AJAX create form আনা হয়; modal form submit-এ `FormData` JSON
   endpoint-এ পাঠানো হয়।
7. Success হলে modal থেকে পাওয়া subcategory-র category parent dropdown-এ বসে,
   matching child option select হয়, তারপর modal বন্ধ হয়।
8. Request বা validation ব্যর্থ হলে message modal-এর alert-এ দেখানো হয়; submit button
   `finally` block-এ আবার সক্রিয় করা হয়।

Create form-এ partial-টি `old('subcategory_id', '')` দিয়ে; edit form-এ
`old('subcategory_id', $product->subcategory_id)` দিয়ে include হয়।
**ফাইল:** [createProduct.blade.php](./resources/views/backend/Product/product/createProduct.blade.php),
[editProduct.blade.php](./resources/views/backend/Product/product/editProduct.blade.php)

### Sidebar, permission এবং inventory filter

- [dashboard.blade.php](./resources/views/dashboard/dashboard.blade.php)-তে
  Subcategory List/New Subcategory, Product Categories menu-র child link হিসেবে আছে।
  প্রতিটি link আলাদা `@can` permission check করে।
- [PermissionSeederTable.php](./database/seeders/PermissionSeederTable.php)-তে
  `product.subcategory.index/create/edit/update/delete` permission seed হয়।
- [RolePermissionSeeder.php](./database/seeders/RolePermissionSeeder.php)-তে manager
  preset-কে ওই CRUD permissions দেওয়া হয়েছে; admin preset seeder-এর সব permission নেয়।
- [productinventory.blade.php](./resources/views/backend/reports/productinventory.blade.php)-তে
  `Filter by Subcategory` dropdown category নামসহ subcategory দেখায়। Apply form-এর
  `subcategory_id` report controller-এ যায় এবং Export link একই filter CSV request-এ নেয়।

Sidebar-এর permission-gated অংশের code:

```blade
@can('product.subcategory.index')
    <a href="{{ route('subcategories.index') }}">All Subcategories</a>
@endcan
@can('product.subcategory.create')
    <a href="{{ route('subcategories.create') }}">New Subcategory</a>
@endcan
```

`@can` route link দেখানোর আগে user permission যাচাই করে; `route()` hard-coded URL-এর
বদলে route name ব্যবহার করে। Seeder-এ একই পাঁচটি permission name নিবন্ধিত হয়:

```php
'product.subcategory.index',
'product.subcategory.create',
'product.subcategory.edit',
'product.subcategory.update',
'product.subcategory.delete',
```

এই পাঁচটি নাম যথাক্রমে list দেখা, তৈরি, edit page দেখা, update এবং delete action
অনুমোদন করে। `RolePermissionSeeder`-এর manager preset-এও একই নামগুলো যোগ করা হয়েছে।

Report dropdown-এর raw markup:

```blade
<label for="subcategory_id" class="form-label">Filter by Subcategory</label>
<select name="subcategory_id" id="subcategory_id" class="form-select">
    <option value="">All Subcategories</option>
    @foreach ($subcategories as $subcategory)
        <option value="{{ $subcategory->id }}"
            @selected((string) ($filters['subcategory_id'] ?? '') === (string) $subcategory->id)>
            {{ $subcategory->category->category_name }} — {{ $subcategory->name }}
        </option>
    @endforeach
</select>
```

- `name="subcategory_id"` query parameter-টি controller-এর filter validation-এ পৌঁছায়।
- প্রথম option নির্বাচন করলে subcategory filter থাকে না।
- `@foreach` controller থেকে পাঠানো subcategory ঘোরে; option value হলো database ID।
- `@selected` apply করার পর আগের filter নির্বাচন করা অবস্থায় রাখে।
- category ও subcategory-র নাম একসঙ্গে দেখালে একই নামে থাকা child-গুলোও চেনা যায়।
- Export link-এ `request()->only(['category_id', 'subcategory_id', 'stock_status'])`
  পাঠানো হয়; ফলে screen ও CSV একই filter ব্যবহার করে।

Product-এর create ও edit view-তে shared field partial include করা হয়েছে:

```blade
@include('backend.Product.product.partials.subcategory-field', [
    'selectedSubcategoryId' => old('subcategory_id', $product->subcategory_id),
])
```

`@include` একই dropdown behavior দুই screen-এ reuse করে; `old()` validation error-এর
পরে submitted selection ফেরায়, আর edit-এর default হলো database-এ থাকা subcategory ID।
Create view-তে `$product` নেই বলে default `old('subcategory_id', '')` ব্যবহার করা হয়।

Create modal form ও AJAX behavior-এর পূর্ণ source যথাক্রমে
[\_form.blade.php](./resources/views/backend/Product/Subcategory/_form.blade.php)
এবং
[subcategory-script.blade.php](./resources/views/backend/Product/product/partials/subcategory-script.blade.php)-এ
রয়েছে। Form-এর `@csrf` POST request-কে Laravel CSRF middleware-এর সঙ্গে সামঞ্জস্যপূর্ণ
করে; `category_id` select parent category নেয়; `name` input child নাম নেয়।
JavaScript-এর প্রতিটি ধাপ উপরের “Dropdown-এর JavaScript আচরণ” অংশে ব্যাখ্যা করা হয়েছে।

Automated coverage-এর source:
[ProductSubcategoryTest.php](./tests/Feature/ProductSubcategoryTest.php) create/list/update/delete
এবং subcategory parent বদলালে product category sync পরীক্ষা করে;
[ProductInventoryReportTest.php](./tests/Feature/ProductInventoryReportTest.php)-এ
report subcategory filter-এর test আছে।

### যাচাই ও চালানোর নির্দেশনা

1. Database schema যোগ করতে `php artisan migrate` চালান।
2. নতুন permission ও manager role access যোগ করতে `php artisan db:seed --class=PermissionSeederTable`
   এবং `php artisan db:seed --class=RolePermissionSeeder` চালান।
3. নতুন category তৈরি করে তার অধীনে subcategory তৈরি করুন; তারপর product create/edit
   form-এ category বেছে child dropdown ব্যবহার করুন।
4. টেস্টে `ProductSubcategoryTest` CRUD flow এবং `ProductInventoryReportTest`
   subcategory filter যাচাই করে। বর্তমান development environment-এ `pdo_sqlite`
   extension না থাকায় এই database-backed tests skip হতে পারে।

