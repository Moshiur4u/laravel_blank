# Brand-এর ডুপ্লিকেট নাম যাচাই

Product তৈরির পেজের modal থেকে আগে থেকেই থাকা Brand-এর নাম দিয়ে save করলে `This brand already exists.` বার্তাটি modal-এর alert-এ দেখা যায়। এই বার্তাটি Brand নামের input-এর নিচে দেখানো হয় না। নতুন Brand তৈরি হয় না।

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/BrandController.php`, `store()` method:

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|unique:brands,name'
], [
    'name.unique' => 'This brand already exists.',
]);

if ($validator->fails() && $request->expectsJson()) {
    return response()->json([
        'message' => $validator->errors()->first(),
        'errors' => $validator->errors(),
    ], 422);
}

$validator->validate();

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

**ব্যাখ্যা:** `unique:brands,name` একই নামের Brand আছে কি না যাচাই করে। AJAX request-এ validation ব্যর্থ হলে controller সরাসরি JSON response এবং HTTP `422` status ফেরত দেয়। ফলে modal সঠিকভাবে validation message পায়, আর web route-এর validation exception থেকে HTML response এসে JSON parse error তৈরি করে না। সাধারণ non-AJAX request-এ `$validator->validate()` আগের Laravel validation আচরণ বজায় রাখে। সফল save-এর response এবং redirect-ও আগের মতোই থাকে।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান migration `database/migrations/2026_06_29_111603_create_brands_table.php`:

```php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
});
```

**ব্যাখ্যা:** Brand-এর নাম বিদ্যমান `brands.name` field-এ রাখা হয়। Duplicate যাচাই controller-এর validation দিয়ে করা হয়; তাই এই পরিবর্তনে schema বা migration পরিবর্তনের প্রয়োজন নেই।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান route `routes/web.php`-এ:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::Post('/product/brand/store', [BrandController::class, 'store'])
        ->middleware('permission:brand.create')
        ->name('brand.store');
});
```

**ব্যাখ্যা:** Brand modal এবং সাধারণ Brand form একই `brand.store` route ব্যবহার করে। Authentication, email verification ও `brand.create` permission middleware আগের মতোই কার্যকর।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। বিদ্যমান `app/Models/Brand.php`-এর প্রাসঙ্গিক অংশ:

```php
protected $fillable = [
    'name'
];

public function Products(){
    return $this->hasMany(Product::class);
}
```

**ব্যাখ্যা:** `name` field-টি Brand তৈরির জন্য আগে থেকেই mass assignment-এ অনুমোদিত। Duplicate validation controller-এ থাকায় Model বা relation-এ পরিবর্তন প্রয়োজন হয়নি।

## Blade Template (Code and Explanation)

ফাইল: `resources/views/backend/Product/product/createProduct.blade.php`। Modal-এর AJAX response JSON হিসেবে parse হয়। JSON parse ব্যর্থ হলে ব্যবহারকারীকে parse exception না দেখিয়ে স্পষ্ট request error দেখানো হয়:

```javascript
const responseText = await response.text();
let result = {};
try {
    result = responseText ? JSON.parse(responseText) : {};
} catch {
    throw new Error(response.ok ?
        'The server returned an invalid response.' :
        `Unable to save ${config.label} (HTTP ${response.status}).`);
}
```

Brand validation ব্যর্থ হলে alert দেখানো হয়। Brand duplicate message-টিকে input-এর নিচে দেখানো থেকে বাদ দেওয়া হয়:

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

Brand modal-এর configuration:

```javascript
bindQuickCreateModal({
    modalId: 'brandCreateModal',
    bodyId: 'brandCreateModalBody',
    formId: 'brand-create-form',
    createUrl: @json(route('brand.create')),
    selectId: 'brand_id',
    responseKey: 'brand',
    nameKey: 'name',
    label: 'brand',
    showValidationSummary: true,
    summaryOnlyErrors: ['This brand already exists.']
});
```

**ব্যাখ্যা:** Controller-এর JSON `422` response থেকে duplicate বার্তাটি modal-এর উপরে alert হিসেবে দেখানো হয়। `summaryOnlyErrors`-এ বার্তাটি থাকায় একই duplicate message Brand input-এর নিচে পুনরায় আসে না। Category ও Supplier modal-এ এই Brand-নির্দিষ্ট configuration নেই, তাই তাদের বিদ্যমান validation display অপরিবর্তিত।
