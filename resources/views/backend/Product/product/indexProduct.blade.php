@extends('dashboard.dashboard')
@section('title')
    Product List
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
                                <a href="javascript:;">Product</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Product List</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('product.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-plus-circle me-1"></i> Add New Product
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            @php
                $totalProductsCount = $Products->count();
                $totalStockUnits = $Products->sum('unit');
                $totalInventoryVal = $Products->sum(function($p) {
                    return (float)$p->price * (float)$p->unit;
                });
                $avgPrice = $totalProductsCount > 0 ? $Products->avg('price') : 0;
            @endphp
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Products</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $totalProductsCount }}</h4>
                                    <p class="mb-0 font-13 text-muted">Catalog items</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-package"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Stock Units</p>
                                    <h4 class="my-1 text-info fw-bold">{{ number_format($totalStockUnits) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Items currently in stock</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                    <i class="bx bx-cuboid"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Inventory Value</p>
                                    <h4 class="my-1 text-success fw-bold">৳{{ number_format($totalInventoryVal, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Stock × Unit Price</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-warning shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Avg Product Price</p>
                                    <h4 class="my-1 text-warning fw-bold">৳{{ number_format($avgPrice, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Across all products</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto">
                                    <i class="bx bx-purchase-tag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Summary Cards -->

            <!-- Product Table Card -->
            <div class="card radius-10 shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-box text-primary me-2"></i>Product Catalog
                        </h5>
                        <p class="text-muted small mb-0">Detailed view of products, categories, stock, and pricing</p>
                    </div>
                    <a href="{{ route('product.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>Add Product
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">SL</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th class="text-center">Stock / Unit</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($Products as $key => $product)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 position-relative">
                                                    @if ($product->img_url && file_exists(public_path('uploads/products/' . $product->img_url)))
                                                        <img src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                            alt="{{ $product->productName }}"
                                                            class="rounded shadow-sm border"
                                                            style="width: 48px; height: 48px; object-fit: cover;" />
                                                    @else
                                                        <div class="rounded bg-light-primary text-primary d-flex align-items-center justify-content-center shadow-sm"
                                                            style="width: 48px; height: 48px; font-weight: 700; font-size: 16px;">
                                                            {{ strtoupper(substr($product->productName, 0, 2)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">{{ $product->productName }}</h6>
                                                    <span class="text-muted small">SKU: #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($product->productCategory)
                                                <span class="badge bg-light-danger text-danger px-2 py-1 rounded">
                                                    <i class="bx bx-category me-1"></i>{{ $product->productCategory->category_name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->brand)
                                                <span class="badge bg-light-primary text-primary px-2 py-1 rounded">
                                                    <i class="bx bx-tag me-1"></i>{{ $product->brand->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark font-15">৳{{ number_format($product->price, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ((int)$product->unit > 10)
                                                <span class="badge bg-light-success text-success px-3 py-1 rounded-pill">
                                                    {{ $product->unit }} Units
                                                </span>
                                            @elseif ((int)$product->unit > 0)
                                                <span class="badge bg-light-warning text-warning px-3 py-1 rounded-pill">
                                                    {{ $product->unit }} Low Stock
                                                </span>
                                            @else
                                                <span class="badge bg-light-danger text-danger px-3 py-1 rounded-pill">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <!-- View Product Details Modal Button -->
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" data-bs-target="#productDetailModal{{ $product->id }}"
                                                    title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                <!-- Edit Button -->
                                                <a href="{{ route('product.edit', $product->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Product">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>

                                                <!-- Delete Button -->
                                                <a href="{{ route('product.destroy', $product->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete product: {{ $product->productName }}?')"
                                                    title="Delete Product">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </div>

                                            <!-- Product Details Modal -->
                                            <div class="modal fade" id="productDetailModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <div class="d-flex align-items-center">
                                                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-2">
                                                                    <i class="bx bx-box"></i>
                                                                </div>
                                                                <div>
                                                                    <h5 class="modal-title fw-bold text-dark mb-0">Product Details</h5>
                                                                    <span class="text-muted small">SKU: #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body p-4">
                                                            <div class="row g-4 align-items-center">
                                                                <div class="col-md-5 text-center">
                                                                    @if ($product->img_url && file_exists(public_path('uploads/products/' . $product->img_url)))
                                                                        <img src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                                            alt="{{ $product->productName }}"
                                                                            class="img-fluid rounded border shadow-sm mb-2"
                                                                            style="max-height: 240px; width: 100%; object-fit: contain;" />
                                                                    @else
                                                                        <div class="rounded bg-light-secondary text-secondary d-flex align-items-center justify-content-center p-5 mb-2"
                                                                            style="height: 200px;">
                                                                            <div>
                                                                                <i class="bx bx-image fs-1 d-block mb-1"></i>
                                                                                <span class="small">No Image Uploaded</span>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    <h5 class="fw-bold text-dark mt-2 mb-1">{{ $product->productName }}</h5>
                                                                    <div class="h4 text-primary fw-bold mb-0">৳{{ number_format($product->price, 2) }}</div>
                                                                </div>

                                                                <div class="col-md-7">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-borderless mb-0">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td class="text-muted py-2" style="width: 140px;">
                                                                                        <i class="bx bx-category me-1"></i>Category:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->productCategory->category_name ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-tag me-1"></i>Brand:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->brand->name ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-cuboid me-1"></i>Stock / Units:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->unit }} Units
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-calendar me-1"></i>Purchase Date:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->purchase_date ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-calendar-check me-1"></i>Mfg Date:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->mfg_date ?? $product->menufecher_date ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-calendar-x me-1"></i>Expire Date:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->expiry_date ?? $product->expire_date ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="text-muted py-2">
                                                                                        <i class="bx bx-time me-1"></i>Shelf Duration:
                                                                                    </td>
                                                                                    <td class="fw-semibold text-dark py-2">
                                                                                        {{ $product->total_duration ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                @if ($product->description)
                                                                                    <tr>
                                                                                        <td class="text-muted py-2" colspan="2">
                                                                                            <div class="border-top pt-2 mt-1">
                                                                                                <strong class="d-block text-secondary mb-1">
                                                                                                    <i class="bx bx-align-left me-1"></i>Description:
                                                                                                </strong>
                                                                                                <p class="small text-muted mb-0">{{ $product->description }}</p>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer border-top">
                                                            <a href="{{ route('product.edit', $product->id) }}" class="btn btn-primary btn-sm">
                                                                <i class="bx bx-edit me-1"></i>Edit Product
                                                            </a>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Product Details Modal -->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bx bx-info-circle fs-3 d-block mb-2"></i>
                                            No products found. Click "Add New Product" to create your first item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Product Table Card -->

        </div>
    </div>
@endsection
