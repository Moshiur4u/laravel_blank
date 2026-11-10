@extends('dashboard.dashboard')
@section('title')
    Add Product
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
                            <li class="breadcrumb-item active" aria-current="page">Add New Product</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('product.index') }}" class="px-3 shadow-sm btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <!-- Left Column: Form Details -->
                    <div class="col-lg-8">
                        <!-- Basic Info Card -->
                        <div class="mb-4 border-0 shadow-sm card radius-10">
                            <div class="py-3 bg-transparent card-header border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                        <i class="bx bx-info-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">General Information</h5>
                                        <p class="mb-0 text-muted small">Provide core product identifiers and pricing</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 card-body">
                                <div class="row g-3">
                                    <!-- Product Name -->
                                    <div class="col-12">
                                        <label for="productName" class="form-label fw-semibold text-secondary">
                                            Product Name <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-box"></i>
                                            </span>
                                            <input type="text" name="productName" id="productName"
                                                class="form-control border-start-0 @error('productName') is-invalid @enderror"
                                                list="existingProductNames" placeholder="e.g. Wireless Ergonomic Mouse"
                                                value="{{ old('productName') }}" required>
                                        </div>
                                        <datalist id="existingProductNames">
                                            @foreach ($existingProductNames as $existingProductName)
                                                <option value="{{ $existingProductName }}"></option>
                                            @endforeach
                                        </datalist>
                                        @error('productName')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- Category -->
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label fw-semibold text-secondary">
                                            Category <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            @can('product.category.create')
                                                <button type="button"
                                                    class="input-group-text bg-light text-secondary border-end-0"
                                                    data-bs-toggle="modal" data-bs-target="#categoryCreateModal"
                                                    aria-label="Create a product category"
                                                    title="Create a product category">
                                                    <i class="bx bx-category"></i>
                                                </button>
                                            @else
                                                <span class="input-group-text bg-light text-secondary border-end-0"
                                                    aria-hidden="true">
                                                    <i class="bx bx-category"></i>
                                                </span>
                                            @endcan
                                            <select
                                                class="form-select border-start-0 @error('product_categorie_id') is-invalid @enderror"
                                                name="product_categorie_id" id="category_id" required>
                                                <option value="" selected disabled>Select Category</option>
                                                @foreach ($ProductCategories as $ProductCategory)
                                                    <option value="{{ $ProductCategory->id }}"
                                                        {{ old('product_categorie_id') == $ProductCategory->id ? 'selected' : '' }}>
                                                        {{ $ProductCategory->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('product_categorie_id')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- Brand -->
                                    <div class="col-md-6">
                                        <label for="brand_id" class="form-label fw-semibold text-secondary">
                                            Brand <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            @can('brand.create')
                                                <button type="button"
                                                    class="input-group-text bg-light text-secondary border-end-0"
                                                    data-bs-toggle="modal" data-bs-target="#brandCreateModal"
                                                    aria-label="Create a brand"
                                                    title="Create a brand">
                                                    <i class="bx bx-tag"></i>
                                                </button>
                                            @else
                                                <span class="input-group-text bg-light text-secondary border-end-0"
                                                    aria-hidden="true">
                                                    <i class="bx bx-tag"></i>
                                                </span>
                                            @endcan
                                            <select
                                                class="form-select border-start-0 @error('brand_id') is-invalid @enderror"
                                                name="brand_id" id="brand_id" required>
                                                <option value="" selected disabled>Select Brand</option>
                                                @foreach ($brands as $brand)
                                                    <option value="{{ $brand->id }}"
                                                        {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('brand_id')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    {{-- supplier ঐচ্ছিক রাখা হয়েছে, যাতে supplier ছাড়া product তৈরির পুরোনো সুবিধা থাকে। --}}
                                    <div class="col-md-6">
                                        <label for="supplier_id"
                                            class="form-label fw-semibold text-secondary"@required(true)>
                                            Supplier<span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            @can('supplier.create')
                                                <button type="button"
                                                    class="input-group-text bg-light text-secondary border-end-0"
                                                    data-bs-toggle="modal" data-bs-target="#supplierCreateModal"
                                                    aria-label="Create a supplier"
                                                    title="Create a supplier">
                                                    <i class="bx bx-store"></i>
                                                </button>
                                            @else
                                                <span class="input-group-text bg-light text-secondary border-end-0"
                                                    aria-hidden="true">
                                                    <i class="bx bx-store"></i>
                                                </span>
                                            @endcan
                                            <select
                                                class="form-select border-start-0 @error('supplier_id') is-invalid @enderror"
                                                name="supplier_id" id="supplier_id">
                                                <option value="">Select Supplier</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('supplier_id')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    {{-- ক্রয়মূল্য বিক্রয়মূল্য থেকে আলাদা field-এ রাখা হয়। --}}
                                    <div class="col-md-6">
                                        <label for="purchase_price" class="form-label fw-semibold text-secondary">
                                            Order Price (৳)
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">৳</span>
                                            <input type="number" step="0.01" min="0" name="purchase_price"
                                                id="purchase_price"
                                                class="form-control border-start-0 @error('purchase_price') is-invalid @enderror"
                                                placeholder="0.00" value="{{ old('purchase_price') }}">
                                        </div>
                                        @error('purchase_price')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    {{-- বিদ্যমান price field-টিই বিক্রয়মূল্য হিসেবে রাখা হয়েছে, তাই পুরোনো ব্যবহার বদলায় না। --}}
                                    <div class="col-md-6">
                                        <label for="price" class="form-label fw-semibold text-secondary">
                                            Selling Price (৳) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">৳</span>
                                            <input type="number" step="0.01" name="price" id="price"
                                                class="form-control border-start-0 @error('price') is-invalid @enderror"
                                                placeholder="0.00" value="{{ old('price') }}" required>
                                        </div>
                                        @error('price')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- Quantity / Unit -->
                                    <div class="col-md-6">
                                        <label for="unit" class="form-label fw-semibold text-secondary">
                                            Quantity / Unit <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-layer"></i>
                                            </span>
                                            <input type="number" name="unit" id="unit"
                                                class="form-control border-start-0 @error('unit') is-invalid @enderror"
                                                placeholder="e.g. 50" value="{{ old('unit') }}" required>
                                        </div>
                                        @error('unit')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="quantity_size" class="form-label fw-semibold text-secondary">
                                            Qty/Size
                                        </label>
                                        <div class="input-group">
                                            @can('quantity-size.create')
                                            <button type="button"
                                                class="input-group-text bg-light text-secondary border-end-0"
                                                data-bs-toggle="modal" data-bs-target="#quantitySizeModal"
                                                aria-label="Create Qty/Size option" title="Create Qty/Size option">
                                                <i class="bx bx-ruler"></i>
                                            </button>
                                            @else
                                                <span class="input-group-text bg-light text-secondary border-end-0" aria-hidden="true">
                                                    <i class="bx bx-ruler"></i>
                                                </span>
                                            @endcan
                                            {{-- Brand-এর মতো catalog-এর Qty/Size option dropdown-এ দেখানো হয়। --}}
                                            <select name="quantity_size" id="quantity_size"
                                                class="form-select border-start-0 @error('quantity_size') is-invalid @enderror">
                                                <option value="">Select Qty/Size</option>
                                                @foreach ($quantitySizes as $quantitySize)
                                                    <option value="{{ $quantitySize }}"
                                                        {{ old('quantity_size') === $quantitySize ? 'selected' : '' }}>
                                                        {{ $quantitySize }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('quantity_size')
                                            <div class="mt-1 text-danger small">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates & Lifespan Card -->
                        <div class="mb-4 border-0 shadow-sm card radius-10">
                            <div class="py-3 bg-transparent card-header border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                        <i class="bx bx-calendar-event"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Timeline & Expiry Dates</h5>
                                        <p class="mb-0 text-muted small">Record manufacturing and shelf-life details</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 card-body">
                                <div class="row g-3">
                                    <!-- Purchase Date -->
                                    <div class="col-md-4">
                                        <label for="purchase_date" class="form-label fw-semibold text-secondary">
                                            Purchase Date
                                        </label>
                                        <input type="date" name="purchase_date" id="purchase_date"
                                            class="form-control" value="{{ old('purchase_date') }}">
                                    </div>

                                    <!-- Manufacturing Date -->
                                    <div class="col-md-4">
                                        <label for="menufecher_date" class="form-label fw-semibold text-secondary">
                                            Manufacturing Date
                                        </label>
                                        <input type="date" id="menufecher_date" name="menufecher_date"
                                            class="form-control" value="{{ old('menufecher_date') }}">
                                    </div>

                                    <!-- Expire Date -->
                                    <div class="col-md-4">
                                        <label for="expire_date" class="form-label fw-semibold text-secondary">
                                            Expire Date
                                        </label>
                                        <input type="date" id="expire_date" name="expire_date" class="form-control"
                                            value="{{ old('expire_date') }}">
                                    </div>

                                    <!-- Total Duration Output -->
                                    <div class="col-12">
                                        <label for="total_duration" class="form-label fw-semibold text-secondary">
                                            Shelf Life / Total Duration
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">
                                                <i class="bx bx-timer"></i>
                                            </span>
                                            <input type="text" id="total_duration" name="total_duration"
                                                class="form-control border-start-0 bg-light" readonly
                                                placeholder="Calculated automatically from Mfg & Expire date"
                                                value="{{ old('total_duration') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Card -->
                        <div class="mb-4 border-0 shadow-sm card radius-10">
                            <div class="py-3 bg-transparent card-header border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-secondary text-secondary me-3">
                                        <i class="bx bx-align-left"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Product Description</h5>
                                        <p class="mb-0 text-muted small">Optional details and notes about this item</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 card-body">
                                <div class="mb-0">
                                    <textarea name="description" id="description" rows="4" class="form-control"
                                        placeholder="Enter detailed specifications, warranty notes, or product highlights...">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Product Image & Submit -->
                    <div class="col-lg-4">
                        <!-- Image Upload Card -->
                        <div class="mb-4 border-0 shadow-sm card radius-10">
                            <div class="py-3 bg-transparent card-header border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                        <i class="bx bx-image-add"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Product Image</h5>
                                        <p class="mb-0 text-muted small">Upload a clear photo of the product</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 text-center card-body">
                                <div class="p-3 mb-3 text-center border border-2 border-dashed rounded bg-light"
                                    style="min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <img id="preview" src="" alt="Preview"
                                        class="mb-2 rounded shadow-sm img-fluid d-none"
                                        style="max-height: 180px; object-fit: contain;" />
                                    <div id="uploadPlaceholder" class="text-muted">
                                        <i class="mb-1 bx bx-cloud-upload fs-1 text-primary d-block"></i>
                                        <span class="fw-semibold">Click below to choose photo</span>
                                        <p class="mb-0 small text-muted">JPG, PNG, GIF up to 2MB</p>
                                    </div>
                                </div>

                                <div class="text-start">
                                    <label for="imageInput" class="form-label fw-semibold text-secondary small">Select
                                        File</label>
                                    <input type="file" name="imageName" id="imageInput"
                                        class="form-control @error('imageName') is-invalid @enderror" accept="image/*">
                                    @error('imageName')
                                        <div class="mt-1 text-danger small">
                                            <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Publish Card -->
                        <div class="border-0 shadow-sm card radius-10">
                            <div class="p-4 card-body">
                                <h6 class="mb-3 fw-bold text-dark">Ready to Publish?</h6>
                                <p class="mb-4 text-muted small">Make sure all mandatory fields marked with (<span
                                        class="text-danger">*</span>) are filled correctly.</p>

                                <div class="gap-2 d-grid">
                                    <button type="submit" class="py-2 shadow-sm btn btn-primary fw-semibold">
                                        <i class="bx bx-check-circle me-1"></i> Save Product
                                    </button>
                                    <a href="{{ route('product.index') }}" class="py-2 btn btn-light text-secondary">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @can('quantity-size.create')
                <div class="modal fade" id="quantitySizeModal" tabindex="-1"
                    aria-labelledby="quantitySizeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form id="quantitySizeForm" action="{{ route('quantity-size.store') }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="quantitySizeModalLabel">Create Qty/Size Option</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-danger d-none" id="quantitySizeModalError" role="alert"></div>
                                    <label for="quantitySizeModalInput" class="form-label">Qty/Size</label>
                                    <input type="text" name="name" id="quantitySizeModalInput" class="form-control"
                                        maxlength="50" placeholder="e.g. RIM, pcs" required>
                                    <div class="form-text">This option will also be available on other product forms.</div>
                                </div>
                                <div class="modal-footer">
                                    @can('quantity-size.index')
                                        <a href="{{ route('quantity-size.index') }}" class="btn btn-outline-secondary">Manage options</a>
                                    @endcan
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create &amp; Use</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('product.category.create')
                <div class="modal fade" id="categoryCreateModal" tabindex="-1"
                    aria-labelledby="categoryCreateModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="categoryCreateModalLabel">Create Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="categoryCreateModalBody">
                                <div class="py-4 text-center text-muted">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Loading category form...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            @can('supplier.create')
                <div class="modal fade" id="supplierCreateModal" tabindex="-1"
                    aria-labelledby="supplierCreateModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="supplierCreateModalLabel">Create Supplier</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="supplierCreateModalBody">
                                <div class="py-4 text-center text-muted">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Loading supplier form...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            @can('brand.create')
                <div class="modal fade" id="brandCreateModal" tabindex="-1"
                    aria-labelledby="brandCreateModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="brandCreateModalLabel" name="brand">Create Brand</h5>
                                @error('brand')
                                    <div class="mt-1 text-danger small">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="brandCreateModalBody">
                                <div class="py-4 text-center text-muted">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Loading brand form...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

        </div>
    </div>

    <!-- Scripts for image preview and date calculations -->
    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            const preview = document.getElementById('preview');
            const placeholder = document.getElementById('uploadPlaceholder');
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const mfgInput = document.getElementById('menufecher_date');
            const expInput = document.getElementById('expire_date');
            const totalDurationInput = document.getElementById('total_duration');
            const quantitySizeInput = document.getElementById('quantity_size');
            const quantitySizeForm = document.getElementById('quantitySizeForm');
            if (quantitySizeForm) {
                quantitySizeForm.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const errorBox = document.getElementById('quantitySizeModalError');
                    const submitButton = quantitySizeForm.querySelector('[type="submit"]');
                    errorBox.classList.add('d-none');
                    errorBox.textContent = '';
                    const enteredName = document.getElementById('quantitySizeModalInput').value.trim();
                    const existingOption = [...quantitySizeInput.options].some((option) =>
                        option.value.trim().toLocaleLowerCase() === enteredName.toLocaleLowerCase()
                    );

                    // আগে থেকেই থাকা মান server-এ পাঠানোর আগেই নির্দিষ্ট বার্তা দেখানো হয়।
                    if (existingOption) {
                        errorBox.textContent = 'This Qty/Size is existing';
                        errorBox.classList.remove('d-none');
                        return;
                    }

                    submitButton.disabled = true;

                    try {
                        const response = await fetch(quantitySizeForm.action, {
                            method: 'POST',
                            body: new FormData(quantitySizeForm),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const responseText = await response.text();
                        let result = {};
                        try {
                            result = responseText ? JSON.parse(responseText) : {};
                        } catch {
                            throw new Error(response.ok ?
                                'The server returned an invalid response.' :
                                `Unable to create Qty/Size (HTTP ${response.status}).`);
                        }

                        if (response.status === 422 && result.errors) {
                            const validationMessages = Object.values(result.errors).flat();
                            const duplicateMessage = validationMessages.find((message) =>
                                message.toLowerCase().includes('qty/size') &&
                                message.toLowerCase().includes('exist')
                            );
                            // Server-এ আগে থেকে থাকা option-এর জন্যও একই নির্দিষ্ট বার্তা দেখানো হয়।
                            throw new Error(duplicateMessage
                                ? 'This Qty/Size is existing'
                                : validationMessages.join(' ')
                                || result.message
                                || 'Please check the Qty/Size value and try again.');
                        }
                        if (!response.ok || !result.quantity_size) {
                            throw new Error(result.message || `Unable to create Qty/Size (HTTP ${response.status}).`);
                        }

                        // Modal-এ তৈরি option dropdown-এ যোগ করে নতুন product-এ নির্বাচন করা হয়।
                        if (![...quantitySizeInput.options].some((option) => option.value === result.quantity_size.name)) {
                            quantitySizeInput.add(new Option(result.quantity_size.name, result.quantity_size.name));
                        }
                        // option যোগ করার পর value বসালে form submit-এ Qty/Size পাঠানো নিশ্চিত হয়।
                        quantitySizeInput.value = result.quantity_size.name;
                        quantitySizeInput.dispatchEvent(new Event('change', { bubbles: true }));
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('quantitySizeModal')).hide();
                    } catch (error) {
                        errorBox.textContent = error.message;
                        errorBox.classList.remove('d-none');
                    } finally {
                        submitButton.disabled = false;
                    }
                });
            }

            function calculateDuration() {
                if (!mfgInput.value || !expInput.value) {
                    totalDurationInput.value = '';
                    return;
                }

                let start = new Date(mfgInput.value);
                let end = new Date(expInput.value);

                if (end < start) {
                    totalDurationInput.value = 'Invalid Date Range (Expire before Mfg)';
                    return;
                }

                let years = end.getFullYear() - start.getFullYear();
                let months = end.getMonth() - start.getMonth();
                let days = end.getDate() - start.getDate();

                if (days < 0) {
                    months--;
                    const prevMonth = new Date(end.getFullYear(), end.getMonth(), 0);
                    days += prevMonth.getDate();
                }

                if (months < 0) {
                    years--;
                    months += 12;
                }

                const formatUnit = (val, singular) => `${val} ${singular}${val === 1 ? '' : 's'}`;

                const parts = [];
                if (years > 0) parts.push(formatUnit(years, 'year'));
                if (months > 0) parts.push(formatUnit(months, 'month'));
                if (days > 0 || parts.length === 0) parts.push(formatUnit(days, 'day'));

                totalDurationInput.value = parts.join(' ');
            }

            if (mfgInput && expInput) {
                mfgInput.addEventListener('change', calculateDuration);
                expInput.addEventListener('change', calculateDuration);
                if (mfgInput.value && expInput.value) {
                    calculateDuration();
                }
            }
        });
    </script>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function bindQuickCreateModal(config) {
                const modal = document.getElementById(config.modalId);
                const modalBody = document.getElementById(config.bodyId);
                const select = document.getElementById(config.selectId);

                modal.addEventListener('show.bs.modal', async function() {
                    modalBody.innerHTML = `
                        <div class="py-4 text-center text-muted">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Loading ${config.label} form...
                        </div>`;

                    try {
                        const response = await fetch(config.createUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Unable to load ${config.label} form (HTTP ${response.status}).`);
                        }

                        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                        const form = page.getElementById(config.formId);

                        if (!form) {
                            throw new Error(`The ${config.label} form was not found in the response.`);
                        }

                        form.querySelectorAll('a[href]').forEach((link) => link.remove());
                        modalBody.replaceChildren(form);
                        form.querySelector('input:not([type="hidden"])')?.focus();
                    } catch (error) {
                        modalBody.innerHTML = '<div class="mb-0 alert alert-danger" role="alert"></div>';
                        modalBody.querySelector('.alert').textContent = error.message;
                    }
                });

                modalBody.addEventListener('submit', async function(event) {
                    const form = event.target.closest(`#${config.formId}`);
                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
                    form.querySelectorAll('.quick-create-validation-error').forEach((error) => error.remove());
                    form.querySelector('.quick-create-submit-error')?.remove();
                    form.querySelector('.quick-create-validation-summary')?.remove();
                    const submitButton = form.querySelector('[type="submit"]');
                    submitButton.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const responseText = await response.text();
                        let result = {};
                        try {
                            result = responseText ? JSON.parse(responseText) : {};
                        } catch {
                            throw new Error(response.ok ?
                                'The server returned an invalid response.' :
                                `Unable to save ${config.label} (HTTP ${response.status}).`);
                        }

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

                        const createdRecord = result[config.responseKey];
                        if (!response.ok || !createdRecord) {
                            throw new Error(result.message ||
                                `Unable to save ${config.label} (HTTP ${response.status}).`);
                        }

                        select.add(new Option(createdRecord[config.nameKey], createdRecord.id, true, true));
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        bootstrap.Modal.getOrCreateInstance(modal).hide();
                    } catch (error) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger quick-create-submit-error';
                        alert.textContent = error.message;
                        form.prepend(alert);
                    } finally {
                        submitButton.disabled = false;
                    }
                });

                if (config.logoPreview) {
                    modalBody.addEventListener('change', function(event) {
                        if (event.target.id !== 'supplierLogoInput') {
                            return;
                        }

                        const [file] = event.target.files;
                        const preview = modalBody.querySelector('#supplierLogoPreview');
                        const previewContainer = modalBody.querySelector('#supplierLogoPreviewContainer');
                        if (file) {
                            preview.src = URL.createObjectURL(file);
                            previewContainer.style.display = 'block';
                        } else {
                            previewContainer.style.display = 'none';
                        }
                    });
                }
            }

            @can('product.category.create')
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
            @endcan

            @can('supplier.create')
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
            @endcan

            @can('brand.create')
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
            @endcan
        });
    </script>
@endsection
