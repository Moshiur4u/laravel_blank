# Product Category, Supplier ও Brand Modal থেকে দ্রুত তৈরি

Product Create পেজে Category, Supplier ও Brand field-এর icon-এ click করলে সংশ্লিষ্ট create form modal-এ আসে। Save সফল হলে পেজ reload ছাড়াই নতুন record dropdown-এ যোগ হয় এবং select হয়।

## Controller (Code and Explanation)

### Product Category

ফাইল: `app/Http/Controllers/ProductCategoryController.php`, `store()` method:

```php
$productCategory = ProductCategory::create([
    'category_name' => $request->input('category_name'),
]);

if ($request->expectsJson()) {
    return response()->json([
        'category' => [
            'id' => $productCategory->id,
            'category_name' => $productCategory->category_name,
        ],
    ], 201);
}

return redirect()->route('category.create');
```

**ব্যাখ্যা:** আগের validation ও save বজায় আছে। Modal-এর JSON request-এ নতুন category-র ID/name ফেরত যায়; সাধারণ form submission আগের মতো redirect করে।

### Supplier

ফাইল: `app/Http/Controllers/SupplierController.php`, `store()` method-এর save-এর পর:

```php
$supplier->save();

if ($request->expectsJson()) {
    return response()->json([
        'supplier' => [
            'id' => $supplier->id,
            'name' => $supplier->name,
        ],
    ], 201);
}

return redirect()->route('supplier.index')->with('success', 'Supplier added successfully!');
```

**ব্যাখ্যা:** Supplier-এর আগের validation, logo upload ও save অপরিবর্তিত। Modal request-এ ID/name JSON-এ ফেরত আসে; সাধারণ form submission আগের redirect-ই পায়।

### Brand

ফাইল: `app/Http/Controllers/BrandController.php`, `store()` method:

```php
$request->validate([
    'name' => 'required|unique:brands,name',
]);

$brand = Brand::create(['name' => $request->input('name')]);

if ($request->expectsJson()) {
    return response()->json([
        'brand' => [
            'id' => $brand->id,
            'name' => $brand->name,
        ],
    ], 201);
}

return redirect()->route('brand.index');
```

**ব্যাখ্যা:** বিদ্যমান unique name validation বজায় থাকে। Modal request-এ তৈরি Brand-এর ID/name ফেরত আসে; সাধারণ request আগের Brand list page-এ redirect করে।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন হয়নি। বিদ্যমান `product_categories`, `suppliers` ও `brands` table ব্যবহার করা হয়েছে। Category table-এর schema:

```php
Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->string('category_name');
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
});
```

**ব্যাখ্যা:** Modal feature-এর জন্য নতুন database field লাগে না; দুই table-ই আগে থেকেই প্রয়োজনীয় তথ্য রাখে।

Brand-এর বিদ্যমান table-এ `name` field-টি unique validation ও নতুন Brand সংরক্ষণে ব্যবহৃত হয়।

## Route (Code and Explanation)

নতুন route যোগ করা হয়নি। Product Create modal-এ বিদ্যমান route-গুলোই ব্যবহার করা হয়।

`routes/web.php`-এর Category route:

```php
Route::get('/productCategory/create', [ProductCategoryController::class, 'create'])
    ->middleware('permission:product.category.create')
    ->name('category.create');
Route::post('/productCategory/store', [ProductCategoryController::class, 'store'])
    ->middleware('permission:product.category.create')
    ->name('category.store');
```

Supplier route:

```php
Route::get('/supplier/create', [SupplierController::class, 'create'])
    ->middleware('permission:supplier.create')
    ->name('supplier.create');
Route::post('/supplier/store', [SupplierController::class, 'store'])
    ->middleware('permission:supplier.create')
    ->name('supplier.store');
```

Brand route:

```php
Route::get('/product/brand/create', [BrandController::class, 'create'])
    ->middleware('permission:brand.create')
    ->name('brand.create');
Route::post('/product/brand/store', [BrandController::class, 'store'])
    ->middleware('permission:brand.create')
    ->name('brand.store');
```

**ব্যাখ্যা:** authentication, email verification ও permission middleware আগের মতোই কার্যকর থাকে। একই route সাধারণ create page ও modal—দুই ক্ষেত্রেই ব্যবহৃত হয়।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। `app/Models/ProductCategory.php`-এ category name আগে থেকেই fillable:

```php
protected $fillable = ['category_name'];
```

`app/Models/Supplier.php`-এ supplier-এর প্রয়োজনীয় field-গুলো আগে থেকেই আছে:

```php
protected $fillable = [
    'name',
    'email',
    'phone',
    'address',
    'logo',
    'is_active',
];
```

**ব্যাখ্যা:** বিদ্যমান Model ও relation নতুন Category/Supplier তৈরি এবং Product-এর সঙ্গে সংযুক্ত করতে যথেষ্ট।

Brand-এর জন্য `app/Models/Brand.php`-তেও কোনো পরিবর্তন হয়নি। বিদ্যমান `name` fillable field দিয়েই Brand তৈরি হয়।

## Blade Template (Code and Explanation)

### Product Create

ফাইল: `resources/views/backend/Product/product/createProduct.blade.php`। Category, Supplier ও Brand-এর icon-ই modal trigger করে:

```blade
<button type="button" class="input-group-text bg-light text-secondary border-end-0"
    data-bs-toggle="modal" data-bs-target="#categoryCreateModal"
    aria-label="Create a product category" title="Create a product category">
    <i class="bx bx-category"></i>
</button>

<button type="button" class="input-group-text bg-light text-secondary border-end-0"
    data-bs-toggle="modal" data-bs-target="#supplierCreateModal"
    aria-label="Create a supplier" title="Create a supplier">
    <i class="bx bx-store"></i>
</button>

<button type="button" class="input-group-text bg-light text-secondary border-end-0"
    data-bs-toggle="modal" data-bs-target="#brandCreateModal"
    aria-label="Create a brand" title="Create a brand">
    <i class="bx bx-tag"></i>
</button>
```

দুই modal-এর body-তে server থেকে পাওয়া create form বসে। Category form শনাক্ত করতে `resources/views/backend/Product/Category/ProductCategoryCreate.blade.php`-এ ID দেওয়া হয়েছে:

```blade
<form action="{{ route('category.store') }}" method="POST" id="product-category-create-form">
```

Supplier form শনাক্ত করতে `resources/views/backend/Supplier/addSupplier.blade.php`-এ ID দেওয়া হয়েছে:

```blade
<form action="{{ route('supplier.store') }}" method="POST" enctype="multipart/form-data"
    id="supplier-create-form">
```

Brand form শনাক্ত করতে `resources/views/backend/Product/Brand/createBrand.blade.php`-এ ID দেওয়া হয়েছে:

```blade
<form action="{{ route('brand.store') }}" method="POST" id="brand-create-form">
```

Supplier logo input/preview-এর ID-গুলো Product form-এর image preview-এর সঙ্গে সংঘর্ষ এড়াতে `supplierLogoInput`, `supplierLogoPreview` ও `supplierLogoPreviewContainer` করা হয়েছে।

### Shared AJAX flow

Product Create view-এর `@section('scripts')`-এ একই quick-create handler দুই form-এর জন্য configure করা হয়। Supplier configuration:

```javascript
bindQuickCreateModal({
    modalId: 'supplierCreateModal',
    bodyId: 'supplierCreateModalBody',
    formId: 'supplier-create-form',
    createUrl: @json(route('supplier.create')),
    selectId: 'supplier_id',
    responseKey: 'supplier',
    nameKey: 'name',
    label: 'supplier',
    logoPreview: true
});
```

Category configuration-ও একই handler-এ বিদ্যমান `category.create` URL, `product-category-create-form`, `category_id`, `category` response key ও `category_name` field দিয়ে যুক্ত।

Brand configuration-ও একই handler-এ যুক্ত:

```javascript
bindQuickCreateModal({
    modalId: 'brandCreateModal',
    bodyId: 'brandCreateModalBody',
    formId: 'brand-create-form',
    createUrl: @json(route('brand.create')),
    selectId: 'brand_id',
    responseKey: 'brand',
    nameKey: 'name',
    label: 'brand'
});
```

**ব্যাখ্যা:** modal খুললে handler create page fetch করে form বসায়। Submit-এ `FormData` দিয়ে AJAX request যায়, তাই Supplier logo-ও পাঠানো যায়। Validation error modal-এই দেখানো হয়। সফল হলে response-এর record সংশ্লিষ্ট dropdown-এ যোগ করে select করে modal বন্ধ হয়—Product Create পেজ reload হয় না।

### Permission অনুযায়ী icon ও modal দেখানো

Product Create Blade-এ create permission থাকলে icon-টি modal trigger button হয়। Permission না থাকলেও একই icon static `span` হিসেবে দেখা যায়; তবে সেটি click করা যায় না। Modal markup ও JavaScript handler কেবল অনুমতিপ্রাপ্ত ব্যবহারকারীর জন্য render হয়:

```blade
@can('product.category.create')
    <button type="button" data-bs-toggle="modal" data-bs-target="#categoryCreateModal">
        <i class="bx bx-category"></i>
    </button>
@else
    <span aria-hidden="true"><i class="bx bx-category"></i></span>
@endcan

@can('brand.create')
    <button type="button" data-bs-toggle="modal" data-bs-target="#brandCreateModal">
        <i class="bx bx-tag"></i>
    </button>
@else
    <span aria-hidden="true"><i class="bx bx-tag"></i></span>
@endcan

@can('supplier.create')
    <button type="button" data-bs-toggle="modal" data-bs-target="#supplierCreateModal">
        <i class="bx bx-store"></i>
    </button>
@else
    <span aria-hidden="true"><i class="bx bx-store"></i></span>
@endcan
```

**ব্যাখ্যা:** icon সব ব্যবহারকারীর জন্য দৃশ্যমান থাকে। শুধু সংশ্লিষ্ট permission থাকা ব্যবহারকারীর icon button হিসেবে modal খোলে; permission না থাকলে icon static, তাই click করে modal খোলা যায় না। Modal markup ও JavaScript handler-ও permission অনুযায়ী সীমাবদ্ধ। Product Create page-এ Category/Brand/Supplier dropdown আগের মতোই দেখা যায়। `routes/web.php`-এর create ও store route-গুলোতেও permission middleware থাকায় সরাসরি URL/request দিয়েও অনুমতি ছাড়া form load বা record save করা যায় না।
