# প্রডাক্ট এডিট ব্লেড ও কন্ট্রোলার আপডেট — বাংলা নির্দেশিকা (Product Edit Blade Update Guide)

## ভূমিকা ও উদ্দেশ্য
এই আপডেটের মাধ্যমে `editProduct.blade.php` (প্রডাক্ট সম্পাদনা পাতা) কে সম্পূর্ণভাবে `createProduct.blade.php` (প্রডাক্ট যোগ করার পাতা)-এর অনুরূপ করা হয়েছে। এর মধ্যে অন্তর্ভুক্ত রয়েছে:
1. **Product Name \***
2. **Category \***
3. **Brand \***
4. **Supplier (ঐচ্ছিক ড্রপডাউন)**
5. **Purchase Order Price (৳) (ক্রয়মূল্য)**
6. **Product Selling Price (৳) \* (বিক্রয়মূল্য)**
7. **Quantity / Unit \* (মজুত পরিমাণ)**
8. **Timeline & Expiry Dates (ক্রয় তারিখ, উৎপাদনের তারিখ, মেয়াদোত্তীর্ণের তারিখ এবং স্বয়ংক্রিয় মেয়াদকাল হিসাব)**
9. **Product Description (বিস্তারিত বিবরণ)**
10. **Product Image & Replacement Preview (বর্তমান ছবি প্রদর্শন ও নতুন ছবি আপলোড প্রিভিউ)**

প্রকল্পের বিদ্যমান অন্য কোনো কোড পরিবর্তন না করে শুধুমাত্র নির্দিষ্ট রিকোয়ারমেন্ট অনুযায়ী কন্ট্রোলার ও ব্লেড টেমপ্লেটে পরিবর্তন আনা হয়েছে।

---

## ১. Controller (কোড ও ব্যাখ্যা)

**ফাইল পাথ:** `app/Http/Controllers/ProductController.php`

### ১.১ `edit($id)` মেথড আপডেট
পূর্বে `edit()` মেথডে `$suppliers` কুয়েরি ও ভিউতে পাস করা ছিল না। তাই এডিট ফর্মে সাপ্লায়ার ড্রপডাউন তালিকা খালি বা অনুপস্থিত থাকত। নিচে হালনাগাদ করা কোড:

```php
/**
 * Show the form for editing the specified resource.
 */
public function edit($id)
{
    $product = Product::findOrFail($id);
    $ProductCategories = ProductCategory::latest()->get();
    $brands = Brand::all();
    // এডিট পেজের ড্রপডাউনে দেখানোর জন্য সকল সাপ্লায়ারের তালিকা আনা হয়েছে
    $suppliers = Supplier::orderBy('name')->get();

    return view('backend.Product.product.editProduct', compact('product', 'ProductCategories', 'brands', 'suppliers'));
}
```

**ব্যাখ্যা:**
- `findOrFail($id)` দিয়ে নির্দিষ্ট প্রডাক্টটি ডাটাবেজ থেকে নিয়ে আসা হয়; আইডি না পাওয়া গেলে স্বয়ংক্রিয়ভাবে ৪-০-৪ হ্যান্ডেল করে।
- `Supplier::orderBy('name')->get()` দিয়ে সকল সক্রিয় সাপ্লায়ার বর্ণানুক্রমিকভাবে সাজিয়ে আনা হয়েছে।
- `compact(...)` এর মাধ্যমে `$product`, `$ProductCategories`, `$brands`, এবং `$suppliers` ভিউতে পাঠানো হয়েছে।

---

### ১.২ `update(Request $request, $id)` মেথড আপডেট
পূর্বে `update()` মেথডে `supplier_id` এবং `purchase_price` ভ্যালিডেশন ও মডেলে সেভ করা হতো না। এখন তা যুক্ত করা হয়েছে:

```php
/**
 * Update the specified resource in storage.
 */
public function update(Request $request, $id)
{
    // ১. ভ্যালিডেশন
    $request->validate([
        'productName' => 'nullable|string|max:255',
        'product_categorie_id' => 'nullable',
        'brand_id' => 'nullable',
        'supplier_id' => 'nullable',
        'purchase_price' => 'nullable',
        'price' => 'nullable',
        'unit' => 'nullable',
        'purchase_date' => 'nullable|date',
        'menufecher_date' => 'nullable|date',
        'expire_date' => 'nullable|date',
        'description' => 'nullable|string|max:500',
        'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // সর্বোচ্চ ২MB ইমেজ
    ]);

    // ২. প্রডাক্ট খুঁজে বের করা
    $product = Product::findOrFail($id);

    // ডিফল্টভাবে ডেটাবেজে থাকা বর্তমান ইমেজের নাম রাখা হচ্ছে
    $productImageName = $product->img_url;

    // ৩. নতুন ইমেজ আপলোড চেক করা (ইনপুট নেম 'imageName')
    if ($request->hasFile('imageName')) {

        // পুরাতন ইমেজ ডিলিট করা (যদি ডেটাবেজে থাকে এবং ফাইল পাথে বিদ্যমান থাকে)
        if ($product->img_url && file_exists(public_path('uploads/products/'.$product->img_url))) {
            unlink(public_path('uploads/products/'.$product->img_url));
        }

        // নতুন ইমেজ সেভ করা
        $productImage = $request->file('imageName');
        $extension = $productImage->getClientOriginalExtension();

        // ইউনিক নাম তৈরি করা (প্রডাক্টের নাম + টাইমস্ট্যাম্প)
        $productImageName = time().'_'.str_replace(' ', '_', $request->productName).'.'.$extension;

        // ফাইলটি নির্ধারিত ফোল্ডারে মুভ করা
        $productImage->move(public_path('uploads/products'), $productImageName);
    }

    // ৪. ডেটাবেজ আপডেট করা ($product মডেল ব্যবহার করে)
    $product->update([
        'productName' => $request->productName,
        'product_categorie_id' => $request->product_categorie_id,
        'brand_id' => $request->brand_id,
        'supplier_id' => $request->supplier_id,
        'purchase_price' => $request->purchase_price,
        'price' => $request->price,
        'unit' => $request->unit,
        'purchase_date' => $request->purchase_date,
        'mfg_date' => $request->menufecher_date,
        'expiry_date' => $request->expire_date,
        'description' => $request->description,
        'img_url' => $productImageName,
    ]);

    return redirect()->route('product.index')->with('success', 'Product Updated Successfully');
}
```

**ব্যাখ্যা:**
- `supplier_id` এবং `purchase_price` এর জন্য রিকোয়েস্ট ভ্যালিডেশন রুলস যুক্ত করা হয়েছে।
- নতুন ছবি আপলোড হলে পূর্বের ছবি `unlink(public_path('uploads/products/'.$product->img_url))` দ্বারা নিরাপদে মুছে নতুন ছবি সংরক্ষণ করা হয়।
- `$product->update([...])` এ `supplier_id` এবং `purchase_price` সহ সকল ফিল্ড সঠিকভাবে আপডেট করা হয়।

---

## ২. Migration (মাইগ্রেশন বিবরণ)

**তথ্য:**
- ডাটাবেজে `products` টেবিলে `supplier_id` এবং `purchase_price` কলামগুলো পূর্বে তৈরি করা মাইগ্রেশন `2026_10_12_095500_add_supplier_and_purchase_price_to_products_table.php` দ্বারা আগেই যুক্ত করা ছিল।
- এছাড়া `2026_10_25_000001_make_product_fields_nullable_in_products_table.php` দ্বারা ফিল্ডগুলো nullable করা হয়েছে।
- সুতরাং নতুন করে কোনো অতিরিক্ত মাইগ্রেশন তৈরির প্রয়োজন পড়েনি; বিদ্যমান স্কিমার সাথে ফর্মটি পুরোপুরি সামঞ্জস্যপূর্ণ করা হয়েছে।

---

## ৩. Blade Template (কোড ও ব্যাখ্যা)

**ফাইল পাথ:** `resources/views/backend/Product/product/editProduct.blade.php`

### ৩.১ ফিল্ডসমূহের বিন্যাস ও কোড
প্রডাক্ট অ্যাড পাতার (`createProduct.blade.php`) মতো হুবহু কার্ড ও গ্রিড লেআউট ব্যবহার করে এডিট পাতাটি তৈরি করা হয়েছে:

```blade
@extends('dashboard.dashboard')
@section('title')
    Edit Product
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!-- Breadcrumb -->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Product Manager</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('product.index') }}">Products</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Product</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('product.index') }}" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- বাম পাশ: ফর্মের মূল তথ্য -->
                    <div class="col-lg-8">
                        <!-- সাধারণ তথ্যাবলির কার্ড -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                            <i class="bx bx-info-circle"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-dark fw-bold">General Information</h5>
                                            <p class="text-muted small mb-0">Update core product identifiers, supplier, and pricing</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill font-13">
                                        SKU: #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <!-- ১. Product Name -->
                                    <div class="col-12">
                                        <label for="productName" class="form-label fw-semibold text-secondary">
                                            Product Name <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-box"></i>
                                            </span>
                                            <input type="text"
                                                name="productName"
                                                id="productName"
                                                class="form-control border-start-0 @error('productName') is-invalid @enderror"
                                                placeholder="e.g. Wireless Ergonomic Mouse"
                                                value="{{ old('productName', $product->productName) }}"
                                                required>
                                        </div>
                                        @error('productName')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ২. Category -->
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label fw-semibold text-secondary">
                                            Category <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-category"></i>
                                            </span>
                                            <select class="form-select border-start-0 @error('product_categorie_id') is-invalid @enderror"
                                                name="product_categorie_id"
                                                id="category_id"
                                                required>
                                                <option value="" disabled>Select Category</option>
                                                @foreach ($ProductCategories as $ProductCategory)
                                                    <option value="{{ $ProductCategory->id }}"
                                                        {{ (old('product_categorie_id', $product->product_categorie_id) == $ProductCategory->id) ? 'selected' : '' }}>
                                                        {{ $ProductCategory->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('product_categorie_id')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ৩. Brand -->
                                    <div class="col-md-6">
                                        <label for="brand_id" class="form-label fw-semibold text-secondary">
                                            Brand <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-tag"></i>
                                            </span>
                                            <select class="form-select border-start-0 @error('brand_id') is-invalid @enderror"
                                                name="brand_id"
                                                id="brand_id"
                                                required>
                                                <option value="" disabled>Select Brand</option>
                                                @foreach ($brands as $brand)
                                                    <option value="{{ $brand->id }}"
                                                        {{ (old('brand_id', $product->brand_id) == $brand->id) ? 'selected' : '' }}>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('brand_id')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ৪. Supplier -->
                                    <div class="col-md-6">
                                        <label for="supplier_id" class="form-label fw-semibold text-secondary">
                                            Supplier
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-store"></i>
                                            </span>
                                            <select class="form-select border-start-0 @error('supplier_id') is-invalid @enderror"
                                                name="supplier_id"
                                                id="supplier_id">
                                                <option value="">Select Supplier (Optional)</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        {{ (old('supplier_id', $product->supplier_id) == $supplier->id) ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('supplier_id')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ৫. Purchase Order Price (৳) -->
                                    <div class="col-md-6">
                                        <label for="purchase_price" class="form-label fw-semibold text-secondary">
                                            Purchase Order Price (৳)
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">৳</span>
                                            <input type="number"
                                                step="0.01"
                                                min="0"
                                                name="purchase_price"
                                                id="purchase_price"
                                                class="form-control border-start-0 @error('purchase_price') is-invalid @enderror"
                                                placeholder="0.00"
                                                value="{{ old('purchase_price', $product->purchase_price) }}">
                                        </div>
                                        @error('purchase_price')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ৬. Product Selling Price (৳) -->
                                    <div class="col-md-6">
                                        <label for="price" class="form-label fw-semibold text-secondary">
                                            Product Selling Price (৳) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">৳</span>
                                            <input type="number"
                                                step="0.01"
                                                name="price"
                                                id="price"
                                                class="form-control border-start-0 @error('price') is-invalid @enderror"
                                                placeholder="0.00"
                                                value="{{ old('price', $product->price) }}"
                                                required>
                                        </div>
                                        @error('price')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- ৭. Quantity / Unit -->
                                    <div class="col-md-6">
                                        <label for="unit" class="form-label fw-semibold text-secondary">
                                            Quantity / Unit <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-layer"></i>
                                            </span>
                                            <input type="number"
                                                name="unit"
                                                id="unit"
                                                class="form-control border-start-0 @error('unit') is-invalid @enderror"
                                                placeholder="e.g. 50"
                                                value="{{ old('unit', $product->unit) }}"
                                                required>
                                        </div>
                                        @error('unit')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- মেয়াদ ও তারিখের কার্ড -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                        <i class="bx bx-calendar-event"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Timeline & Expiry Dates</h5>
                                        <p class="text-muted small mb-0">Record manufacturing and shelf-life details</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="purchase_date" class="form-label fw-semibold text-secondary">Purchase Date</label>
                                        <input type="date" name="purchase_date" id="purchase_date" class="form-control"
                                            value="{{ old('purchase_date', optional($product->purchase_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="menufecher_date" class="form-label fw-semibold text-secondary">Manufacturing Date</label>
                                        <input type="date" id="menufecher_date" name="menufecher_date" class="form-control"
                                            value="{{ old('menufecher_date', optional($product->mfg_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="expire_date" class="form-label fw-semibold text-secondary">Expire Date</label>
                                        <input type="date" id="expire_date" name="expire_date" class="form-control"
                                            value="{{ old('expire_date', optional($product->expiry_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-12">
                                        <label for="total_duration" class="form-label fw-semibold text-secondary">Shelf Life / Total Duration</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-timer"></i>
                                            </span>
                                            <input type="text" id="total_duration" name="total_duration" class="form-control border-start-0 bg-light"
                                                readonly placeholder="Calculated automatically from Mfg & Expire date"
                                                value="{{ old('total_duration', $product->shelfDurationDays() !== null ? $product->shelfDurationDays().' days' : '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- বিবরণ কার্ড -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-secondary text-secondary me-3">
                                        <i class="bx bx-align-left"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Product Description</h5>
                                        <p class="text-muted small mb-0">Optional details and notes about this item</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <textarea name="description" id="description" rows="4" class="form-control"
                                    placeholder="Enter detailed specifications, warranty notes, or product highlights...">{{ old('description', $product->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ডান পাশ: প্রডাক্ট ইমেজ ও সাবমিট বাটন -->
                    <div class="col-lg-4">
                        <!-- ছবি আপলোড ও প্রিভিউ কার্ড -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                        <i class="bx bx-image"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Product Image</h5>
                                        <p class="text-muted small mb-0">Current photo & replacement</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="border border-2 border-dashed rounded p-3 mb-3 bg-light text-center"
                                    style="min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    @if ($product->img_url && file_exists(public_path('uploads/products/' . $product->img_url)))
                                        <img id="preview"
                                            src="{{ asset('uploads/products/' . $product->img_url) }}"
                                            alt="{{ $product->productName }}"
                                            class="img-fluid rounded mb-2 shadow-sm"
                                            style="max-height: 180px; object-fit: contain;" />
                                        <div id="uploadPlaceholder" class="text-muted small mt-1">
                                            Current image displayed above. Select new file to change.
                                        </div>
                                    @else
                                        <img id="preview" src="" alt="Preview" class="img-fluid rounded mb-2 d-none shadow-sm"
                                            style="max-height: 180px; object-fit: contain;" />
                                        <div id="uploadPlaceholder" class="text-muted">
                                            <i class="bx bx-cloud-upload fs-1 text-primary d-block mb-1"></i>
                                            <span class="fw-semibold">No image currently</span>
                                            <p class="small text-muted mb-0">JPG, PNG, GIF up to 2MB</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-start">
                                    <label for="imageInput" class="form-label fw-semibold text-secondary small">Replace Image (Optional)</label>
                                    <input type="file" name="imageName" id="imageInput" class="form-control @error('imageName') is-invalid @enderror" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <!-- সেভ/আপডেট বাটন কার্ড -->
                        <div class="card radius-10 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3">Save Changes</h6>
                                <p class="text-muted small mb-4">Ensure all mandatory fields marked with (<span class="text-danger">*</span>) are filled correctly.</p>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary py-2 shadow-sm fw-semibold">
                                        <i class="bx bx-save me-1"></i> Update Product
                                    </button>
                                    <a href="{{ route('product.index') }}" class="btn btn-light py-2 text-secondary">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
```

**ব্যাখ্যা:**
1. **Product Name \*:** `old('productName', $product->productName)` দিয়ে পূর্বের সংরক্ষিত নাম ড্র করা হয়েছে।
2. **Category \* & Brand \*:** যথাক্রমে ক্যাটাগরি ও ব্র্যান্ডের ড্রপডাউন এবং বর্তমান সিলেক্টেড ভ্যালু প্রদর্শন করা হয়েছে।
3. **Supplier:** `createProduct` ব্লেডের মতো সাপ্লায়ার নির্বাচন করার ড্রপডাউন যুক্ত করা হয়েছে এবং পূর্বে সিলেক্ট করা সাপ্লায়ার `selected` অবস্থায় রাখা হয়েছে।
4. **Purchase Order Price (৳):** ক্রয়ের দর (Purchase Price) ইনপুট বক্স যুক্ত করা হয়েছে।
5. **Product Selling Price (৳) \*:** বিক্রয়মূল্য ইনপুট ফিল্ডটিকে `Product Selling Price (৳) *` লেবেল দেওয়া হয়েছে।
6. **Quantity / Unit \*:** স্টকের পরিমাণ প্রদর্শনের জন্য ফিল্ডটি বিদ্যমান এবং হুবহু একই লেআউটে রাখা হয়েছে।
7. **স্বয়ংক্রিয় মেয়াদকাল জাভাস্ক্রিপ্ট:** ম্যানুফ্যাকচার ডেট এবং এক্সপায়ারি ডেট পরিবর্তনের সাথে সাথে `total_duration` ফিল্ডে স্বয়ংক্রিয়ভাবে বছর, মাস এবং দিনের মেয়াদ হিসাব করে প্রদর্শন করে।
