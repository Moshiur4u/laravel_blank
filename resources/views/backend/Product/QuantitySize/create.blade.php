@extends('dashboard.dashboard')

@section('title', 'Create Qty/Size')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center">
                <div class="breadcrumb-title pe-3">Product Manager</div>
                <div class="ms-auto">
                    @can('quantity-size.index')
                        <a href="{{ route('quantity-size.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to List
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="card radius-10 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <h5 class="mb-0 fw-bold"><i class="bx bx-ruler text-primary me-2"></i>Create Qty/Size</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('quantity-size.store') }}" method="POST" id="quantity-size-create-form">
                                @csrf
                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold">Qty/Size <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        maxlength="50" value="{{ old('name') }}" placeholder="e.g. RIM, pcs" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    @can('quantity-size.index')
                                        <a href="{{ route('quantity-size.index') }}" class="btn btn-light">Cancel</a>
                                    @endcan
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Save Qty/Size
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
