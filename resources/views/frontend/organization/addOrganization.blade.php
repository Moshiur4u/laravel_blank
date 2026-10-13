@extends('dashboard.dashboard')

@section('title', 'Organization Information')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Organization</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Organization Information</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bx bx-buildings me-1"></i> Organization Information</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('organization.update') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <label for="logo" class="form-label fw-semibold">Organization Logo</label>
                                    <input type="file" name="logo" id="logo"
                                        class="form-control @error('logo') is-invalid @enderror"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <small class="text-muted">JPG, PNG, or WebP; maximum 2 MB.</small>
                                    @error('logo')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                    @if ($organization->logo)
                                        <div class="mt-3">
                                            <div class="small text-muted mb-2">Current Logo</div>
                                            <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                                alt="{{ $organization->name }} logo"
                                                style="max-width: 180px; max-height: 120px; object-fit: contain;">
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">
                                        Organization Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $organization->name) }}" maxlength="255" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-semibold">Phone</label>
                                    <input type="text" name="phone" id="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $organization->phone) }}" maxlength="50">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" name="email" id="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $organization->email) }}" maxlength="255">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="address" class="form-label fw-semibold">Address</label>
                                    <textarea name="address" id="address" rows="3" maxlength="2000"
                                        class="form-control @error('address') is-invalid @enderror">{{ old('address', $organization->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Organization Information
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection