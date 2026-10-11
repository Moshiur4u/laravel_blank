@extends('dashboard.dashboard')
@section('title')
    @if (isset($customer))
        Edit Customer
    @else
        Add Customer
    @endif
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Customer</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">Customer List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ isset($customer) ? 'Edit Customer' : 'Add Customer' }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('customer.index') }}" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card radius-10 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom py-3">
                            <div class="d-flex align-items-center">
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                    <i class="bx bx-user-plus"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-dark fw-bold">{{ isset($customer) ? 'Update Customer' : 'Create New Customer' }}</h5>
                                    <p class="text-muted small mb-0">Temporary and permanent customer records with payment tracking</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <form action="{{ isset($customer) ? route('customer.update', $customer->id) : route('customer.store') }}" method="POST">
                                @csrf
                                @if (isset($customer))
                                    @method('PUT')
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-semibold text-secondary">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="customer_type" class="form-label fw-semibold text-secondary">Customer Type <span class="text-danger">*</span></label>
                                        <select name="customer_type" id="customer_type" class="form-select" required>
                                            <option value="temporary" {{ old('customer_type', $customer->customer_type ?? '') == 'temporary' ? 'selected' : '' }}>Temporary Customer</option>
                                            <option value="permanent" {{ old('customer_type', $customer->customer_type ?? '') == 'permanent' ? 'selected' : '' }}>Permanent Customer</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold text-secondary">Phone</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold text-secondary">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="shop_name" class="form-label fw-semibold text-secondary">Shop Name</label>
                                        <input type="text" name="shop_name" id="shop_name" class="form-control" value="{{ old('shop_name', $customer->shop_name ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="customer_code" class="form-label fw-semibold text-secondary">Customer Code</label>
                                        <input type="text" name="customer_code" id="customer_code" class="form-control" value="{{ old('customer_code', $customer->customer_code ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="employee_id" class="form-label fw-semibold text-secondary">Permanent Employee / Reference</label>
                                        <select name="employee_id" id="employee_id" class="form-select">
                                            <option value="">Select employee</option>
                                            @foreach ($employees ?? [] as $employee)
                                                <option value="{{ $employee->id }}" {{ old('employee_id', $customer->employee_id ?? '') == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="opening_due" class="form-label fw-semibold text-secondary">Opening Due</label>
                                        <input type="number" step="0.01" name="opening_due" id="opening_due" class="form-control" value="{{ old('opening_due', $customer->opening_due ?? 0) }}">
                                    </div>

                                    <div class="col-12">
                                        <label for="address" class="form-label fw-semibold text-secondary">Address</label>
                                        <textarea name="address" id="address" rows="3" class="form-control">{{ old('address', $customer->address ?? '') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label for="notes" class="form-label fw-semibold text-secondary">Notes</label>
                                        <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes', $customer->notes ?? '') }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 mt-3 border-top">
                                    <a href="{{ route('customer.index') }}" class="btn btn-light px-4">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bx bx-check-circle me-1"></i>{{ isset($customer) ? 'Update Customer' : 'Save Customer' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const customerType = document.getElementById('customer_type');
            const employee = document.getElementById('employee_id');

            function updateEmployeeRequirement() {
                employee.required = customerType.value === 'permanent';
            }

            customerType.addEventListener('change', updateEmployeeRequirement);
            updateEmployeeRequirement();
        });
    </script>
@endsection
