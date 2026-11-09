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
                    @can('product.create')
                        {{-- product তৈরির নতুন permission। --}}
                        <a href="{{ route('product.create') }}" class="px-3 shadow-sm btn btn-primary">
                            <i class="bx bx-plus-circle me-1"></i> Add New Product
                        </a>
                    @endcan
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            @php
                $totalProductsCount = $Products->count();
                $totalStockUnits = $Products->sum('unit');
                $totalInventoryVal = $Products->sum(function ($p) {
                    return (float) $p->price * (float) $p->unit;
                });
                $avgPrice = $totalProductsCount > 0 ? $Products->avg('price') : 0;
                $expiringSoonProducts = $Products->filter(fn($p) => $p->isExpiringSoon());
                $expiredProducts = $Products->filter(fn($p) => $p->isExpired());
            @endphp
            <div class="mb-4 row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">In-stock Products</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $totalProductsCount }}</h4>
                                    <p class="mb-0 font-13 text-muted">Products with available stock</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-package"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-info">
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
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-success">
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
                    <div class="mb-0 border-0 shadow-sm card radius-10 border-start border-3 border-warning">
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

            @if ($expiredProducts->isNotEmpty() || $expiringSoonProducts->isNotEmpty())
                @if ($expiredProducts->isNotEmpty())
                    <div class="border-0 shadow-sm alert alert-danger d-flex align-items-start" role="alert">
                        <i class="bx bx-error-circle fs-4 me-2"></i>
                        <div>
                            <strong>Expired products:</strong>
                            {{ $expiredProducts->pluck('display_name')->join(', ') }}
                        </div>
                    </div>
                @endif
                @if ($expiringSoonProducts->isNotEmpty())
                    <div class="border-0 shadow-sm alert alert-warning d-flex align-items-start" role="alert">
                        <i class="bx bx-time-five fs-4 me-2"></i>
                        <div>
                            <strong>Expiring within one month:</strong>
                            {{ $expiringSoonProducts->map(fn($p) => $p->display_name . ' (' . $p->expiry_date->format('Y-m-d') . ')')->join(', ') }}
                        </div>
                    </div>
                @endif
            @endif

            <!-- Product Table Card -->
            <div class="border-0 shadow-sm card radius-10">
                <div
                    class="py-3 bg-transparent card-header border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-box text-primary me-2"></i>Available Product Directory
                        </h5>
                        <p class="mb-0 text-muted small">Detailed view of products, categories, stock, and pricing</p>
                    </div>
                    @can('product.create')
                        {{-- product তৈরির নতুন permission। --}}
                        <a href="{{ route('product.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-plus me-1"></i>Add Product
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table mb-0 align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px">SL</th>
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
                                                            alt="{{ $product->display_name }}"
                                                            class="border rounded shadow-sm"
                                                            style="width: 48px; height: 48px; object-fit: cover;" />
                                                    @else
                                                        <div class="rounded shadow-sm bg-light-primary text-primary d-flex align-items-center justify-content-center"
                                                            style="width: 48px; height: 48px; font-weight: 700; font-size: 16px;">
                                                            {{ strtoupper(substr($product->productName, 0, 2)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">
                                                        {{ $product->display_name }}
                                                        @if ($product->isExpired())
                                                            <span class="badge bg-danger ms-1">Expired</span>
                                                        @elseif ($product->isExpiringSoon())
                                                            <span class="badge bg-warning text-dark ms-1">Expires
                                                                soon</span>
                                                        @endif
                                                    </h6>
                                                    <span class="text-muted small">SKU:
                                                        #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($product->productCategory)
                                                <span class="px-2 py-1 rounded badge bg-light-danger text-danger">
                                                    <i
                                                        class="bx bx-category me-1"></i>{{ $product->productCategory->category_name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->brand)
                                                <span class="px-2 py-1 rounded badge bg-light-primary text-primary">
                                                    <i class="bx bx-tag me-1"></i>{{ $product->brand->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="fw-bold text-dark font-15">৳{{ number_format($product->price, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ((int) $product->unit > 10)
                                                <span class="px-3 py-1 badge bg-light-success text-success rounded-pill">
                                                    {{ $product->unit }} Units
                                                </span>
                                            @elseif ((int) $product->unit > 0)
                                                <span class="px-3 py-1 badge bg-light-warning text-warning rounded-pill">
                                                    {{ $product->unit }} Low Stock
                                                </span>
                                            @else
                                                <span class="px-3 py-1 badge bg-light-danger text-danger rounded-pill">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="gap-2 d-inline-flex">
                                                <!-- View Product Details Modal Button -->
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#productDetailModal{{ $product->id }}"
                                                    title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                <!-- Edit Button -->
                                                @can('product.edit')
                                                    {{-- product সম্পাদনার নতুন permission। --}}
                                                    <a href="{{ route('product.edit', $product->id) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Edit Product">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </a>
                                                @endcan

                                                <!-- Delete Button -->
                                                @can('product.delete')
                                                    {{-- product মুছতে নতুন permission। --}}
                                                    <a href="{{ route('product.destroy', $product->id) }}"
                                                        class="btn btn-sm btn-outline-danger" data-swal-delete
                                                        data-delete-message="Product: {{ $product->display_name }}"
                                                        title="Delete Product">
                                                        <i class="bx bx-trash"></i>
                                                    </a>
                                                @endcan
                                            </div>

                                            <!-- Product Details Modal -->
                                            <div class="modal fade" id="productDetailModal{{ $product->id }}"
                                                tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <div class="d-flex align-items-center">
                                                                <div
                                                                    class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-2">
                                                                    <i class="bx bx-box"></i>
                                                                </div>
                                                                <div>
                                                                    <h5 class="mb-0 modal-title fw-bold text-dark">Product
                                                                        Details</h5>
                                                                    <span class="text-muted small">SKU:
                                                                        #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="p-4 modal-body">
                                                            <div class="row g-4 align-items-center">
                                                                <div class="text-center col-md-5">
                                                                    @if ($product->img_url && file_exists(public_path('uploads/products/' . $product->img_url)))
                                                                        <img src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                                            alt="{{ $product->display_name }}"
                                                                            class="mb-2 border rounded shadow-sm img-fluid"
                                                                            style="max-height: 240px; width: 100%; object-fit: contain;" />
                                                                    @else
                                                                        <div class="p-5 mb-2 rounded bg-light-secondary text-secondary d-flex align-items-center justify-content-center"
                                                                            style="height: 200px;">
                                                                            <div>
                                                                                <i
                                                                                    class="mb-1 bx bx-image fs-1 d-block"></i>
                                                                                <span class="small">No Image
                                                                                    Uploaded</span>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    <h5 class="mt-2 mb-1 fw-bold text-dark">
                                                                        {{ $product->display_name }}</h5>
                                                                    <div class="mb-0 h4 text-primary fw-bold">
                                                                        ৳{{ number_format($product->price, 2) }}</div>
                                                                </div>

                                                                <div class="col-md-7">
                                                                    <div class="table-responsive">
                                                                        <table
                                                                            class="table mb-0 table-sm table-borderless">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted"
                                                                                        style="width: 140px;">
                                                                                        <i
                                                                                            class="bx bx-category me-1"></i>Category:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ $product->productCategory->category_name ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-tag me-1"></i>Brand:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ $product->brand->name ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-cuboid me-1"></i>Stock
                                                                                        / Units:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ $product->unit }} Units
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-calendar me-1"></i>Purchase
                                                                                        Date:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ optional($product->purchase_date)->format('Y-m-d') ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-calendar-check me-1"></i>Mfg
                                                                                        Date:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ optional($product->mfg_date)->format('Y-m-d') ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-calendar-x me-1"></i>Expire
                                                                                        Date:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ optional($product->expiry_date)->format('Y-m-d') ?? 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="py-2 text-muted">
                                                                                        <i
                                                                                            class="bx bx-time me-1"></i>Shelf
                                                                                        Duration:
                                                                                    </td>
                                                                                    <td class="py-2 fw-semibold text-dark">
                                                                                        {{ $product->shelfDurationDays() !== null ? $product->shelfDurationDays() . ' days' : 'N/A' }}
                                                                                    </td>
                                                                                </tr>
                                                                                @if ($product->isExpired())
                                                                                    <tr>
                                                                                        <td colspan="2" class="pt-2">
                                                                                            <div class="mb-0 alert alert-danger"
                                                                                                role="alert">
                                                                                                <i
                                                                                                    class="bx bx-error-circle me-1"></i>
                                                                                                This product expired on
                                                                                                {{ $product->expiry_date->format('Y-m-d') }}.
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @elseif ($product->isExpiringSoon())
                                                                                    <tr>
                                                                                        <td colspan="2" class="pt-2">
                                                                                            <div class="mb-0 alert alert-warning"
                                                                                                role="alert">
                                                                                                <i
                                                                                                    class="bx bx-error-circle me-1"></i>
                                                                                                This product expires within
                                                                                                one month
                                                                                                ({{ $product->expiry_date->format('Y-m-d') }})
                                                                                                .
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                                @if ($product->description)
                                                                                    <tr>
                                                                                        <td class="py-2 text-muted"
                                                                                            colspan="2">
                                                                                            <div
                                                                                                class="pt-2 mt-1 border-top">
                                                                                                <strong
                                                                                                    class="mb-1 d-block text-secondary">
                                                                                                    <i
                                                                                                        class="bx bx-align-left me-1"></i>Description:
                                                                                                </strong>
                                                                                                <p
                                                                                                    class="mb-0 small text-muted">
                                                                                                    {{ $product->description }}
                                                                                                </p>
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
                                                            @can('product.edit')
                                                                {{-- product সম্পাদনার নতুন permission। --}}
                                                                <a href="{{ route('product.edit', $product->id) }}"
                                                                    class="btn btn-primary btn-sm">
                                                                    <i class="bx bx-edit me-1"></i>Edit Product
                                                                </a>
                                                            @endcan
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Product Details Modal -->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-muted">
                                            <i class="mb-2 bx bx-info-circle fs-3 d-block"></i>
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
