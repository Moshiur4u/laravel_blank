@extends('dashboard.dashboard')
@section('title')
    Add New Supplier
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Suppliers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier.index') }}">Supplier List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Add New Supplier</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('supplier.index') }}" class="btn btn-outline-primary">
                            <i class="bx bx-arrow-back me-1"></i> Back To List
                        </a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card border-0 shadow-sm radius-10">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bx bxs-school fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-primary fw-bold">Add Supplier Info</h5>
                                    <small class="text-muted">Fill in the details below to register a new supplier</small>
                                </div>
                            </div>
                            <a href="{{ route('supplier.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-list-ul me-1"></i> Supplier List
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('supplier.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                {{-- Supplier Name --}}
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">
                                        Supplier Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bx bx-user"></i></span>
                                        <input type="text" name="name" id="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="Enter supplier name"
                                            value="{{ old('name') }}" required>
                                    </div>
                                    @error('name')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Supplier Email & Phone side-by-side --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label fw-semibold">
                                            Supplier Email <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-envelope"></i></span>
                                            <input type="email" name="email" id="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                placeholder="supplier@example.com"
                                                value="{{ old('email') }}" required>
                                        </div>
                                        @error('email')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label fw-semibold">
                                            Supplier Phone <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-phone"></i></span>
                                            <input type="text" name="phone" id="phone"
                                                class="form-control @error('phone') is-invalid @enderror"
                                                placeholder="01XXXXXXXXX"
                                                value="{{ old('phone') }}" required>
                                        </div>
                                        @error('phone')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Supplier Address --}}
                                <div class="mb-3">
                                    <label for="address" class="form-label fw-semibold">
                                        Supplier Address <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bx bx-map"></i></span>
                                        <input type="text" name="address" id="address"
                                            class="form-control @error('address') is-invalid @enderror"
                                            placeholder="Enter full address"
                                            value="{{ old('address') }}" required>
                                    </div>
                                    @error('address')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Supplier Logo --}}
                                <div class="mb-4">
                                    <label for="imageInput" class="form-label fw-semibold">
                                        Supplier Logo
                                    </label>
                                    <input type="file" id="imageInput" name="logo"
                                        class="form-control @error('logo') is-invalid @enderror"
                                        accept="image/*">
                                    <small class="text-muted d-block mt-1">Supported formats: JPEG, PNG, JPG, GIF (Max: 1MB)</small>
                                    @error('logo')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror

                                    {{-- Image Preview Container --}}
                                    <div class="mt-3 text-center p-3 border rounded bg-light" id="previewContainer" style="display: none;">
                                        <p class="small text-muted mb-2">Logo Preview</p>
                                        <img id="preview" alt="Supplier Logo Preview"
                                            style="max-width: 140px; max-height: 140px; border-radius: 12px; object-fit: cover; border: 2px solid #dee2e6; box-shadow: 0 4px 6px rgba(0,0,0,0.05);" />
                                    </div>
                                </div>

                                {{-- Form Action Buttons --}}
                                <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="bx bx-plus me-1"></i> Add Supplier
                                    </button>
                                    <a href="{{ route('supplier.index') }}" class="btn btn-outline-secondary px-3">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('previewContainer');
            if (file) {
                preview.src = URL.createObjectURL(file);
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        };
    </script>
@endsection
