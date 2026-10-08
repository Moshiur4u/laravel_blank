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
                    <!-- Left Column: Form Details -->
                    <div class="col-lg-8">
                        <!-- Basic Info Card -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                            <i class="bx bx-edit-alt"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-dark fw-bold">Update Product Information</h5>
                                            <p class="text-muted small mb-0">Modify details for SKU #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill font-13">
                                        ID: #{{ $product->id }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body p-4">
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
                                            <input type="text"
                                                name="productName"
                                                id="productName"
                                                class="form-control border-start-0 @error('productName') is-invalid @enderror"
                                                value="{{ old('productName', $product->productName) }}"
                                                required>
                                        </div>
                                        @error('productName')
                                            <div class="text-danger small mt-1">
                                                <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <!-- Category -->
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label fw-semibold text-secondary">
                                            Select Category <span class="text-danger">*</span>
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

                                    <!-- Brand -->
                                    <div class="col-md-6">
                                        <label for="brand_id" class="form-label fw-semibold text-secondary">
                                            Select Brand <span class="text-danger">*</span>
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

                                    <!-- Price -->
                                    <div class="col-md-6">
                                        <label for="price" class="form-label fw-semibold text-secondary">
                                            Unit Price (৳) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0">৳</span>
                                            <input type="number"
                                                step="0.01"
                                                name="price"
                                                id="price"
                                                class="form-control border-start-0 @error('price') is-invalid @enderror"
                                                value="{{ old('price', $product->price) }}"
                                                required>
                                        </div>
                                        @error('price')
                                            <div class="text-danger small mt-1">
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
                                            <input type="number"
                                                name="unit"
                                                id="unit"
                                                class="form-control border-start-0 @error('unit') is-invalid @enderror"
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

                        <!-- Dates & Lifespan Card -->
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
                                    <!-- Purchase Date -->
                                    <div class="col-md-4">
                                        <label for="purchase_date" class="form-label fw-semibold text-secondary">
                                            Purchase Date
                                        </label>
                                        <input type="date"
                                            name="purchase_date"
                                            id="purchase_date"
                                            class="form-control"
                                            value="{{ old('purchase_date', $product->purchase_date) }}">
                                    </div>

                                    <!-- Manufacturing Date -->
                                    <div class="col-md-4">
                                        <label for="menufecher_date" class="form-label fw-semibold text-secondary">
                                            Manufacturing Date
                                        </label>
                                        <input type="date"
                                            id="menufecher_date"
                                            name="menufecher_date"
                                            class="form-control"
                                            value="{{ old('menufecher_date', $product->mfg_date ?? $product->menufecher_date) }}">
                                    </div>

                                    <!-- Expire Date -->
                                    <div class="col-md-4">
                                        <label for="expire_date" class="form-label fw-semibold text-secondary">
                                            Expire Date
                                        </label>
                                        <input type="date"
                                            id="expire_date"
                                            name="expire_date"
                                            class="form-control"
                                            value="{{ old('expire_date', $product->expiry_date ?? $product->expire_date) }}">
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
                                            <input type="text"
                                                id="total_duration"
                                                name="total_duration"
                                                class="form-control border-start-0 bg-light"
                                                readonly
                                                placeholder="Calculated automatically from Mfg & Expire date"
                                                value="{{ old('total_duration', $product->total_duration) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Card -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center">
                                    <div class="widgets-icons-2 rounded-circle bg-light-secondary text-secondary me-3">
                                        <i class="bx bx-align-left"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold">Product Description</h5>
                                        <p class="text-muted small mb-0">Specifications, notes, or highlights</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="mb-0">
                                    <textarea name="description"
                                        id="description"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Enter detailed specifications...">{{ old('description', $product->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Product Image & Submit -->
                    <div class="col-lg-4">
                        <!-- Image Upload Card -->
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
                                        <img id="preview"
                                            src=""
                                            alt="Preview"
                                            class="img-fluid rounded mb-2 d-none shadow-sm"
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
                                    <input type="file"
                                        name="imageName"
                                        id="imageInput"
                                        class="form-control @error('imageName') is-invalid @enderror"
                                        accept="image/*">
                                    @error('imageName')
                                        <div class="text-danger small mt-1">
                                            <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Save Card -->
                        <div class="card radius-10 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3">Save Changes</h6>
                                <p class="text-muted small mb-4">Ensure all information is accurate before submitting.</p>

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

    <!-- Scripts for image preview and date calculations -->
    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            const preview = document.getElementById('preview');
            const placeholder = document.getElementById('uploadPlaceholder');
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
                if (placeholder) {
                    placeholder.innerText = 'New image selected for upload';
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const mfgInput = document.getElementById('menufecher_date');
            const expInput = document.getElementById('expire_date');
            const totalDurationInput = document.getElementById('total_duration');

            function calculateDuration() {
                if (!mfgInput.value || !expInput.value) {
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
                if (mfgInput.value && expInput.value && !totalDurationInput.value) {
                    calculateDuration();
                }
            }
        });
    </script>
@endsection
