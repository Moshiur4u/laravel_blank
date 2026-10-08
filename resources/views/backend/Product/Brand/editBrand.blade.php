@extends('dashboard.dashboard')
@section('title')
    Edit Brand
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
                                <a href="{{ route('brand.index') }}">Brands</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Brand</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('brand.index') }}" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="card radius-10 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <div class="d-flex align-items-center">
                                <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                    <i class="bx bx-edit-alt"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-dark fw-bold">Update Brand</h5>
                                    <p class="text-muted small mb-0">Modify brand title and details for #BR-{{ str_pad($brand->id, 4, '0', STR_PAD_LEFT) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <form action="{{ route('brand.update', $brand->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="name" class="form-label fw-semibold text-secondary mb-0">
                                            Brand Name <span class="text-danger">*</span>
                                        </label>
                                        <span class="badge bg-light-primary text-primary">ID: #{{ $brand->id }}</span>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary border-end-0">
                                            <i class="bx bx-tag"></i>
                                        </span>
                                        <input type="text"
                                            name="name"
                                            id="name"
                                            class="form-control border-start-0 @error('name') is-invalid @enderror"
                                            value="{{ old('name', $brand->name) }}"
                                            required
                                            autofocus>
                                    </div>
                                    @error('name')
                                        <div class="text-danger small mt-1">
                                            <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    <div class="form-text text-muted">
                                        Original name: <span class="fw-medium text-dark">{{ $brand->name }}</span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                                    <a href="{{ route('brand.index') }}" class="btn btn-light px-4">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bx bx-save me-1"></i>Update Brand
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
