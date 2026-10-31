@extends('dashboard.dashboard')

@section('title', 'Add Voucher Account')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-flex align-items-center justify-content-between">
                <div>
                    <div class="breadcrumb-title pe-3">Accounts</div>
                    <h4 class="mt-2 mb-0">Add Voucher Account</h4>
                </div>
                <a href="{{ route('voucher-accounts.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="border-0 shadow-sm card">
                        <div class="card-body p-4">
                            <form action="{{ route('voucher-accounts.store') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold">
                                        Account Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" maxlength="100" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('voucher-accounts.index') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Save Account</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
