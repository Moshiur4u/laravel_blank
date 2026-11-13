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
                    <div class="border-0 shadow-sm card radius-10">
                        <div class="py-3 bg-transparent card-header border-bottom">
                            <h5 class="mb-0 fw-bold"><i class="bx bx-ruler text-primary me-2"></i>Create Qty/Size</h5>
                        </div>
                        <div class="p-4 card-body">
                            <form action="{{ route('quantity-size.store') }}" method="POST" id="quantity-size-create-form">
                                @csrf
                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold">Qty/Size <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror" maxlength="50"
                                        value="{{ old('name') }}" placeholder="e.g. Pcs,Box,Ream,SET, etc..." required
                                        autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="gap-2 pt-3 d-flex justify-content-end border-top">
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
