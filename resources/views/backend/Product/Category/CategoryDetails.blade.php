@extends('dashboard.dashboard')
@section('title')
    Category Details
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
                                <a href="{{ route('category.index') }}">Categories</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Category Details</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('category.index') }}" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to Categories
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            @php
                $cat = $category ?? $CategoryEdit ?? ($ProductCategories->first() ?? null);
            @endphp

            @if ($cat)
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger me-3">
                                            <i class="bx bx-category"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-dark fw-bold">Category Overview</h5>
                                            <p class="text-muted small mb-0">Detailed specifications and linked catalog items</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('category.edit', $cat->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-edit me-1"></i>Edit Category
                                    </a>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <div class="rounded-circle bg-light-danger text-danger d-inline-flex align-items-center justify-content-center mb-2"
                                        style="width: 76px; height: 76px; font-weight: 700; font-size: 26px;">
                                        {{ strtoupper(substr($cat->category_name, 0, 2)) }}
                                    </div>
                                    <h3 class="fw-bold mb-1">{{ $cat->category_name }}</h3>
                                    <span class="badge bg-danger px-3 py-2 font-13">ID: #CAT-{{ str_pad($cat->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-sm-6">
                                        <div class="p-3 bg-light rounded text-center">
                                            <span class="text-muted small d-block">Linked Products</span>
                                            <h4 class="fw-bold text-primary mb-0">
                                                {{ $cat->Products ? $cat->Products->count() : 0 }}
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-3 bg-light rounded text-center">
                                            <span class="text-muted small d-block">Creation Date</span>
                                            <h5 class="fw-bold text-dark mb-0">
                                                {{ $cat->created_at ? $cat->created_at->format('d M, Y') : 'N/A' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>

                                @if ($cat->Products && $cat->Products->count() > 0)
                                    <h6 class="fw-bold text-dark mb-3">
                                        <i class="bx bx-package me-1 text-primary"></i>Products in this Category
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Product Name</th>
                                                    <th>Price</th>
                                                    <th>Stock</th>
                                                    <th class="text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($cat->Products as $product)
                                                    <tr>
                                                        <td class="fw-medium text-dark">{{ $product->productName }}</td>
                                                        <td>৳{{ number_format($product->price, 2) }}</td>
                                                        <td><span class="badge bg-light-success text-success">{{ $product->unit }} units</span></td>
                                                        <td class="text-end">
                                                            <a href="{{ route('product.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="bx bx-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
