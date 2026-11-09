# Product Category-এর ডুপ্লিকেট নাম যাচাই

Product তৈরির পেজের Category quick-create modal-এ আগে থেকে থাকা Category-এর নাম দিয়ে Save করলে `This product category already exists.` বার্তাটি modal alert-এ দেখা যায়; একই বার্তা Category input-এর নিচে দেখানো হয় না। অন্য validation error আগের মতো সংশ্লিষ্ট input-এর নিচে দেখা যায়।

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/ProductCategoryController.php`, `store()` method:

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'category_name' => 'required|unique:product_categories,category_name',
], [
    'category_name.unique' => 'This product category already exists.',
]);

if ($validator->fails() && $request->expectsJson()) {
    return response()->json([
        'message' => $validator->errors()->first(),
        'errors' => $validator->errors(),
    ], 422);
}

$validator->validate();

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

**ব্যাখ্যা:** `unique:product_categories,category_name` একই নামের Category আগে থেকে আছে কি না যাচাই করে। Modal-এর AJAX request-এ validation ব্যর্থ হলে controller JSON response ও HTTP `422` status ফেরত দেয়; ফলে modal-এ validation message আসে, HTML response parse করতে গিয়ে JSON error হয় না। সাধারণ form submission-এ `$validator->validate()` আগের Laravel validation আচরণ বজায় রাখে। সফল modal request এবং সাধারণ form submission-এর আগের আচরণও অপরিবর্তিত।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান migration `database/migrations/2026_06_20_153436_create_product_categories_table.php`-এর প্রাসঙ্গিক অংশ:

```php
Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->string('category_name')->unique;
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
});
```

**ব্যাখ্যা:** এই কাজের জন্য database schema বদলানো হয়নি। Duplicate যাচাই controller-এর `unique` validation rule-এ হয়। উপরের migration code বিদ্যমান অবস্থাই দেখায়; migration-এ কোনো পরিবর্তন এই কাজের অংশ নয়।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান `routes/web.php` route:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/productCategory/create', [ProductCategoryController::class, 'create'])
        ->middleware('permission:product.category.create')
        ->name('category.create');
    Route::post('/productCategory/store', [ProductCategoryController::class, 'store'])
        ->middleware('permission:product.category.create')
        ->name('category.store');
});
```

**ব্যাখ্যা:** Modal এবং পূর্ণ Category form একই `category.store` route ব্যবহার করে। Authentication, email verification ও `product.category.create` permission আগের মতোই কার্যকর।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। `app/Models/ProductCategory.php`-এর প্রাসঙ্গিক অংশ:

```php
protected $fillable = ['category_name'];

public function Products()
{
    return $this->hasMany(Product::class, 'product_categorie_id', 'id');
}
```

**ব্যাখ্যা:** `category_name` field-টি আগে থেকেই mass assignment-এর জন্য অনুমোদিত এবং Product-এর সঙ্গে Category relation বিদ্যমান। Duplicate যাচাই Controller-এ হওয়ায় Model পরিবর্তন প্রয়োজন হয়নি।

## Blade Template (Code and Explanation)

ফাইল: `resources/views/backend/Product/product/createProduct.blade.php`। Quick-create AJAX handler-এর validation block modal alert দেখায় এবং `summaryOnlyErrors`-এ থাকা বার্তা field feedback-এ দেখানো এড়িয়ে যায়:

```javascript
if (response.status === 422 && result.errors) {
    if (config.showValidationSummary) {
        const summary = document.createElement('div');
        summary.className = 'alert alert-danger quick-create-validation-summary';
        summary.setAttribute('role', 'alert');
        summary.textContent = Object.values(result.errors).flat().join(' ');
        form.prepend(summary);
    }

    Object.entries(result.errors).forEach(([field, messages]) => {
        if (config.summaryOnlyErrors?.includes(messages[0])) {
            return;
        }

        const input = Array.from(form.elements).find((element) => element.name === field);
        if (!input) {
            return;
        }

        input.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block quick-create-validation-error';
        feedback.textContent = messages[0];
        input.closest('.mb-3, .mb-4')?.append(feedback);
    });
    return;
}
```

Category modal-এর configuration:

```javascript
bindQuickCreateModal({
    modalId: 'categoryCreateModal',
    bodyId: 'categoryCreateModalBody',
    formId: 'product-category-create-form',
    createUrl: @json(route('category.create')),
    selectId: 'category_id',
    responseKey: 'category',
    nameKey: 'category_name',
    label: 'category',
    showValidationSummary: true,
    summaryOnlyErrors: ['This product category already exists.']
});
```

**ব্যাখ্যা:** `showValidationSummary: true` Category-এর validation errors modal alert-এ দেখায়। `summaryOnlyErrors`-এ duplicate বার্তাটি থাকায় `This product category already exists.` শুধু alert-এ দেখা যায়, input-এর নিচে পুনরাবৃত্তি হয় না। অন্য Category validation error input-এর নিচে দেখানো অব্যাহত থাকে। Supplier ও Brand-এর modal configuration এই পরিবর্তনে বদলায়নি।
