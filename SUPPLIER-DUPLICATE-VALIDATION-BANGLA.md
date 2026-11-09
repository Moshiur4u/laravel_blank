# Supplier-এর ডুপ্লিকেট নাম যাচাই

Product তৈরির পেজের Supplier quick-create modal-এ আগে থেকে থাকা Supplier-এর নাম দিয়ে Save করলে `This supplier already exists.` বার্তাটি modal alert-এ দেখা যায়; একই বার্তা Supplier input-এর নিচে দেখানো হয় না। অন্য validation error আগের মতো সংশ্লিষ্ট input-এর নিচে দেখানো হয়।

## Controller (Code and Explanation)

ফাইল: `app/Http/Controllers/SupplierController.php`, `store()` method:

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|unique:suppliers,name',
    'email' => 'required',
    'phone' => 'required',
    'address' => 'required',
    'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048',
], [
    'name.unique' => 'This supplier already exists.',
]);

if ($validator->fails() && $request->expectsJson()) {
    return response()->json([
        'message' => $validator->errors()->first(),
        'errors' => $validator->errors(),
    ], 422);
}

$validator->validate();

$supplier = new Supplier;
$supplier->name = $request->name;
$supplier->email = $request->email;
$supplier->phone = $request->phone;
$supplier->address = $request->address;

if ($request->hasFile('logo')) {
    $image = $request->file('logo');
    $extension = $image->extension();
    $logoName = time().'.'.$extension;
    $image->move(public_path('uploads/suppliers/'), $logoName);
    $supplier->logo = $logoName;
}

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

**ব্যাখ্যা:** `unique:suppliers,name` একই নামে Supplier আগে থেকে আছে কি না যাচাই করে। Modal-এর AJAX request-এ validation ব্যর্থ হলে controller JSON response ও HTTP `422` status দেয়; এতে modal validation message পায়, HTML response parse করতে গিয়ে JSON error হয় না। সাধারণ form submission-এ `$validator->validate()` আগের validation আচরণ বজায় রাখে। Logo validation, upload, সফল JSON response ও redirect আগের মতোই থাকে।

## Migration (Code and Explanation)

এই পরিবর্তনের জন্য কোনো migration যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান migration `database/migrations/2026_10_06_111559_create_suppliers_table.php`-এর প্রাসঙ্গিক অংশ:

```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('email', 100);
    $table->string('phone', 20);
    $table->string('address', 255);
    $table->string('logo', 255)->nullable();
    $table->timestamps();
});
```

**ব্যাখ্যা:** Supplier-এর নাম বিদ্যমান `suppliers.name` field-এ রাখা হয়। এই পরিবর্তনে validation দিয়ে duplicate ঠেকানো হয়; database schema পরিবর্তন করা হয়নি।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি। বিদ্যমান `routes/web.php` route:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/supplier/create', [SupplierController::class, 'create'])
        ->middleware('permission:supplier.create')
        ->name('supplier.create');
    Route::post('/supplier/store', [SupplierController::class, 'store'])
        ->middleware('permission:supplier.create')
        ->name('supplier.store');
});
```

**ব্যাখ্যা:** Modal এবং পূর্ণ Supplier form একই `supplier.store` route ব্যবহার করে। Authentication, email verification ও `supplier.create` permission আগের মতোই কার্যকর।

## Model (Code and Explanation)

কোনো Model পরিবর্তন করা হয়নি। বিদ্যমান `app/Models/Supplier.php`-এর প্রাসঙ্গিক অংশ:

```php
protected $fillable = [
    'name',
    'email',
    'phone',
    'address',
    'logo',
    'is_active',
];

public function supplierledgers()
{
    return $this->hasMany(Supplierledger::class);
}
```

**ব্যাখ্যা:** Supplier-এর প্রয়োজনীয় field ও ledger relation আগে থেকেই Model-এ আছে। Duplicate যাচাই Controller-এ হওয়ায় Model পরিবর্তন প্রয়োজন হয়নি।

## Blade Template (Code and Explanation)

ফাইল: `resources/views/backend/Product/product/createProduct.blade.php`। Quick-create AJAX handler validation errors-এর জন্য modal alert দেখায় এবং `summaryOnlyErrors`-এ থাকা বার্তা input-এর নিচে পুনরায় দেখায় না:

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

Supplier modal-এর configuration:

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
    logoPreview: true,
    showValidationSummary: true,
    summaryOnlyErrors: ['This supplier already exists.']
});
```

**ব্যাখ্যা:** `showValidationSummary: true` Supplier modal-এ validation errors alert হিসেবে দেখায়। `summaryOnlyErrors`-এ duplicate বার্তাটি থাকায় `This supplier already exists.` শুধু alert-এ দেখা যায়, Supplier input-এর নিচে নয়। অন্য validation error field-এর নিচে দেখানো হয়; `logoPreview: true` দিয়ে Supplier logo preview-এর আগের আচরণ বজায় থাকে।
