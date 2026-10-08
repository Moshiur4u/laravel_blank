@extends('dashboard.dashboard')
@section('title')
    Add Brand
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
                            <li class="breadcrumb-item active" aria-current="page">Add New Brand</li>
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
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                    <i class="bx bx-purchase-tag-alt"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-dark fw-bold">Create New Brand</h5>
                                    <p class="text-muted small mb-0">Fill in the brand name to add it to your catalog</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <form action="{{ route('brand.store') }}" method="POST">
                                @csrf

                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold text-secondary">
                                        Brand Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary border-end-0">
                                            <i class="bx bx-tag"></i>
                                        </span>
                                        <input type="text"
                                            name="name"
                                            id="name"
                                            class="form-control border-start-0 @error('name') is-invalid @enderror"
                                            placeholder="e.g. Samsung, Apple, Nike, Nestle"
                                            value="{{ old('name') }}"
                                            required
                                            autofocus>
                                    </div>
                                    @error('name')
                                        <div class="text-danger small mt-1">
                                            <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    <div class="form-text text-muted">
                                        Brand names must be unique and descriptive.
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                                    <a href="{{ route('brand.index') }}" class="btn btn-light px-4">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bx bx-check-circle me-1"></i>Save Brand
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
