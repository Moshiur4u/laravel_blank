<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--favicon-->

    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png" />
    <!--plugins-->
    <link href="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <!-- loader-->
    <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/js/pace.min.js') }}"></script>
    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">

    <!-- Theme Style CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
    <style>
        .help-line-banner {
            border: 1px solid #f3c34f;
            background: linear-gradient(135deg, #fff8df, #fff1c2);
            box-shadow: 0 3px 12px rgba(139, 98, 0, .12);
        }

        .help-line-icon {
            width: 38px;
            height: 38px;
            color: #fff;
            background: #a86e00;
            font-size: 1.25rem;
        }

        .help-line-title {
            color: #715000;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .help-line-number {
            color: #412d00;
            font-size: 1.05rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .help-line-apps {
            color: #168b4a;
            font-size: 1.35rem;
        }

        .help-line-viber {
            color: #665cac;
        }

        @media (max-width: 575.98px) {
            .help-line-banner {
                gap: .4rem !important;
                padding: .35rem .5rem !important;
            }

            .help-line-icon {
                width: 32px;
                height: 32px;
            }

            .help-line-number {
                font-size: .9rem;
            }

            .help-line-apps {
                font-size: 1.15rem;
            }
        }
    </style>

    <!--Datatable -->
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.2.2/r-3.0.3/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.2.2/r-3.0.3/datatables.min.js"></script>
    <title>myPos- @yield('title')</title>
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <div>
                    @if ($organization?->logo)
                        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}" class="logo-icon"
                            alt="{{ $organization->name ?: 'Organization logo' }}"
                            style="max-height: 45px; object-fit: contain;">
                    @else
                        <img src="{{ asset('assets/images/logo-icon.png') }}" class="logo-icon" alt="logo icon">
                    @endif
                </div>
                <div>
                    <h4 class="logo-text">{{ $organization?->name ?: 'myPOS Software' }}</h4>
                </div>
                <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i>
                </div>
            </div>
            <!--navigation-->
            <ul class="metismenu" id="menu">
                <li>
                    <a href="{{ route('dashboardbody') }}" class="">
                        <div class="parent-icon"><i class='bx bx-home-smile'></i>
                        </div>
                        <div class="menu-title">Dashboard</div>
                    </a>

                </li>
                <li class="menu-label">Regural Operation</li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bx-cart-alt'></i></div>
                        <div class="menu-title">Sales</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('sales.index') }}"><i class='bx bx-plus-medical'></i>Create Sale</a>
                        </li>

                    </ul>
                </li>
                <li class="menu-label">Managerial Operations</li>

                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class=' bx bxl-product-hunt'></i>
                        </div>
                        <div class="menu-title">Product</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('product.index') }}"><i class='bx bx-food-menu'></i>All Product</a>
                        </li>

                        <li>
                            <a href="{{ route('product.create') }}"><i class='bx bx-plus-medical'></i>Create
                                Product</a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bx bxl-bootstrap "></i></div>
                        <div class="menu-title">Brand</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('brand.index') }}"><i class='bx bx-food-menu'></i>Brand List</a>
                        </li>

                        <li>
                            <a href="{{ route('brand.create') }}"><i class=' bx bx-plus-medical'></i>New Brand Entry
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bx bxs-school"></i></div>
                        <div class="menu-title">Supplier</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('supplier.index') }}"><i class='bx bx-food-menu'></i> Supplier List</a>
                        </li>

                        <li>
                            <a href="{{ route('supplier.create') }}"><i class=' bx bxs-school'></i>New Supplier
                                Entry
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bx bx-customize "></i></div>
                        <div class="menu-title">Categories</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('category.index') }}"><i class='bx bx-food-menu'></i>All Category</a>
                        </li>
                        <li>
                            <a href="{{ route('category.create') }}"><i class='bx bx-plus-medical'></i>Create
                                Category</a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class="bx bx-user-circle"></i>
                        </div>
                        <div class="menu-title">Customers</div>
                    </a>
                    <ul>

                        <li>
                            <a href="{{ route('customer.index') }}"><i class=' bx bx-id-card'></i>All Customer</a>
                        </li>

                        <li>
                            <a href="{{ route('customer.create') }}"><i class='bx bx-user-plus'></i>Create
                                Customer</a>
                        </li>
                    </ul>
                </li>
                <li class="menu-label">Administration operations</li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class="bx bxs-blanket"></i>
                        </div>
                        <div class="menu-title">Accounts</div>
                    </a>
                    <ul>

                        <li>
                            <a href="{{ route('voucher-entries.index') }}"><i class=' bx bx-id-card'></i>Voucher
                                Entries</a>
                        </li>

                        <li>
                            <a href="{{ route('employee-payment.index') }}"><i class='bx bx-user-plus '></i>Employee
                                Payment</a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class="bx bxs-user-pin "></i>
                        </div>
                        <div class="menu-title">Empolyee</div>
                    </a>
                    <ul>

                        <li>
                            <a href="{{ route('user.index') }}"><i class=' bx bx-id-card'></i>All Empolyee</a>
                        </li>

                        <li>
                            <a href="{{ route('user.create') }}"><i class='bx bx-user-plus '></i>Create Empolyee</a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bx-key'></i>
                        </div>
                        <div class="menu-title">Role and Permission</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('roles.index') }}"><i class='bx bxs-door-open'></i>All Roles</a>
                        </li>
                        <li>
                            <a href="{{ route('roles.create') }}"><i class='bx bx-user-plus'></i>Create Role</a>
                        </li>

                    </ul>

                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bxs-landmark'></i>
                        </div>
                        <div class="menu-title">organization</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('organization.edit') }}"><i class='bx bxs-door-open'></i>Organization
                                Information</a>
                        </li>
                    </ul>

                </li>

                <li class="menu-label">Reports</li>

                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class='bx bxs-bookmark-alt-plus'></i>
                        </div>
                        <div class="menu-title">Voucher</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('voucher-report.combined') }}">
                                <div class="parent-icon"><i class="bx bx-receipt"></i></div>
                                <div class="menu-title">Voucher</div>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('employee-payment.report') }}">
                                <div class="parent-icon"><i class="bx bx-receipt"></i></div>
                                <div class="menu-title">Employee Payment</div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('voucher-entries.report') }}">
                                <div class="parent-icon"><i class="bx bx-receipt"></i></div>
                                <div class="menu-title">Miscellaneous Voucher</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class='bx bxs-book-open'></i>
                        </div>
                        <div class="menu-title">Sale Reports</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('sales.report') }}">
                                <i class='bx bx-layer'></i>Sales Report
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class='bx bxs-spreadsheet'></i>
                        </div>
                        <div class="menu-title">Inventory Reports</div>
                    </a>
                    <ul>
                        <li>
                            <a href="{{ route('product.inventory.report') }}">
                                <i class='bx bx-calculator'></i>Product Inventory
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
            <!--end navigation-->
        </div>
        <!--end sidebar wrapper -->
        <!--start header -->
        <header>
            <div class="topbar d-flex align-items-center">
                <nav class="gap-3 navbar navbar-expand">
                    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
                    </div>

                    <div class="gap-2 px-3 py-2 help-line-banner d-flex align-items-center rounded-3">
                        <span class="help-line-icon d-flex align-items-center justify-content-center rounded-circle">
                            <i class="bx bx-headphone"></i>
                        </span>
                        <div class="lh-sm ">
                            <div class="help-line-title">Help Line</div>
                            <a class="help-line-number" href="tel:01716770085">01716770085</a>
                        </div>
                        <span class="gap-2 help-line-apps d-flex align-items-center ms-1"
                            aria-label="WhatsApp and Viber">
                            <i class="bx bxl-whatsapp" title="WhatsApp" aria-label="WhatsApp"></i>
                            <i class="bx bxs-phone-call help-line-viber" title="Viber" aria-label="Viber"></i>
                        </span>
                    </div>

                    <div class="top-menu ms-auto">
                        <ul class="gap-1 navbar-nav align-items-center">

                            <li class="nav-item dark-mode d-none d-sm-flex">
                                <a class="nav-link dark-mode-icon" href="javascript:;"><i class='bx bx-moon'></i>
                                </a>
                            </li>


                            <li class="nav-item dropdown dropdown-large">
                                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative"
                                    href="#" data-bs-toggle="dropdown">
                                    @if (($expiryNotifications ?? collect())->isNotEmpty())
                                        <span class="alert-count">{{ $expiryNotifications->count() }}</span>
                                    @endif
                                    <i class='bx bx-bell'></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('product.index') }}">
                                        <div class="msg-header">
                                            <p class="msg-header-title">Expiry Notifications</p>
                                            <p class="msg-header-badge">
                                                {{ ($expiryNotifications ?? collect())->count() }} New</p>
                                        </div>
                                    </a>
                                    <div class="header-notifications-list">
                                        @forelse (($expiryNotifications ?? collect()) as $alertProduct)
                                            <a class="dropdown-item"
                                                href="{{ route('product.edit', $alertProduct->id) }}">
                                                <div class="d-flex align-items-center">
                                                    @if ($alertProduct->isExpired())
                                                        <div class="notify bg-light-danger text-danger">
                                                            <i class='bx bx-error-circle'></i>
                                                        </div>
                                                    @else
                                                        <div class="notify bg-light-warning text-warning">
                                                            <i class='bx bx-time-five'></i>
                                                        </div>
                                                    @endif
                                                    <div class="flex-grow-1">
                                                        <h6 class="msg-name">
                                                            {{ $alertProduct->productName }}
                                                            <span class="msg-time float-end">
                                                                {{ $alertProduct->expiry_date->diffForHumans() }}
                                                            </span>
                                                        </h6>
                                                        <p class="msg-info">
                                                            @if ($alertProduct->isExpired())
                                                                Expired on
                                                                {{ $alertProduct->expiry_date->format('Y-m-d') }}
                                                            @else
                                                                Expires within one month
                                                                ({{ $alertProduct->expiry_date->format('Y-m-d') }})
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <a class="dropdown-item" href="{{ route('product.index') }}">
                                                <div class="d-flex align-items-center">
                                                    <div class="notify bg-light-success text-success">
                                                        <i class='bx bx-check-circle'></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="msg-name">No expiry alerts</h6>
                                                        <p class="msg-info">No products are expired or expiring within
                                                            one month</p>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforelse
                                    </div>
                                    <a href="{{ route('product.index') }}">
                                        <div class="text-center msg-footer">
                                            <button class="btn btn-primary w-100">View All Products</button>
                                        </div>
                                    </a>
                                </div>
                            </li>

                            <li class="nav-item dropdown dropdown-large">
                                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative"
                                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @if (($lowStockNotifications ?? collect())->isNotEmpty())
                                        <span class="alert-count">{{ $lowStockNotifications->count() }}</span>
                                    @endif
                                    <i class='bx bx-shopping-bag'></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('product.index') }}">
                                        <div class="msg-header">
                                            <p class="msg-header-title">Stock Alert</p>
                                            <p class="msg-header-badge">
                                                {{ ($lowStockNotifications ?? collect())->count() }} Low Stock</p>
                                        </div>
                                    </a>
                                    <div class="header-message-list">
                                        @forelse (($lowStockNotifications ?? collect()) as $stockProduct)
                                            <a class="dropdown-item"
                                                href="{{ route('product.edit', $stockProduct->id) }}">
                                                <div class="gap-3 d-flex align-items-center">
                                                    <div class="position-relative">
                                                        <div class="cart-product rounded-circle bg-light">
                                                            @if ($stockProduct->img_url && file_exists(public_path('uploads/products/' . $stockProduct->img_url)))
                                                                <img src="{{ asset('uploads/products/' . $stockProduct->img_url) }}"
                                                                    alt="{{ $stockProduct->productName }}">
                                                            @else
                                                                <img src="{{ asset('assets/images/products/11.png') }}"
                                                                    alt="{{ $stockProduct->productName }}">
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 cart-product-title">
                                                            {{ $stockProduct->productName }}</h6>
                                                        <p class="mb-0 cart-product-price">
                                                            @if ($stockProduct->isOutOfStock())
                                                                Out of Stock
                                                            @else
                                                                Low Stock: {{ $stockProduct->stockQuantity() }} Units
                                                            @endif
                                                        </p>
                                                    </div>
                                                    <div class="">
                                                        <p class="mb-0 cart-price">
                                                            ৳{{ number_format((float) $stockProduct->price, 2) }}</p>
                                                    </div>
                                                </div>
                                            </a>
                                        @empty
                                            <a class="dropdown-item" href="{{ route('product.index') }}">
                                                <div class="gap-3 d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 cart-product-title">No low stock alerts</h6>
                                                        <p class="mb-0 cart-product-price">All products have more than
                                                            10 units</p>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforelse
                                    </div>
                                    <a href="{{ route('product.index') }}">
                                        <div class="text-center msg-footer">
                                            <div class="mb-3 d-flex align-items-center justify-content-between">
                                                <h5 class="mb-0">Alerts</h5>
                                                <h5 class="mb-0 ms-auto">
                                                    {{ ($lowStockNotifications ?? collect())->count() }}</h5>
                                            </div>
                                            <button class="btn btn-primary w-100">View Products</button>
                                        </div>
                                    </a>
                                </div>
                            </li>

                        </ul>
                    </div>
                    <div class="px-3 user-box dropdown">

                        <a class="gap-3 d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{-- <img src="{{ asset('assets/images/avatars/avatar-2.png') }}" class="user-img"
                                alt="user avatar"> --}}
                            {{-- -- ---------------------------------------------- --}}
                            <!-- এখানে আমরা ইফ কন্ডিশন লজিক ব্যবহার করে ব্লেডে লোগো দেখাবো -->
                            @if (Auth::user()->image !== null)
                                <img src="{{ asset('uploads/users/' . Auth::user()->image) }}"
                                    alt="{{ Auth::user()->name }}" class="product-img-2"
                                    style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" />
                            @else
                                {{-- লোগো না থাকলে সাপ্লায়ার নামের প্রথম অক্ষর দিয়ে অ্যাভাটার দেখাবে --}}
                                @php
                                    $colors = [
                                        '#0d6efd',
                                        '#6f42c1',
                                        '#d63384',
                                        '#dc3545',
                                        '#fd7e14',
                                        '#198754',
                                        '#0dcaf0',
                                        '#6610f2',
                                    ];
                                    $colorIndex = Auth::user()->id % count($colors);
                                @endphp
                                <div
                                    style="width: 45px; height: 45px; border-radius: 50%; background: {{ $colors[$colorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; text-transform: uppercase;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                </div>
                            @endif

                            <div class="user-info">
                                <p class="mb-0 user-name">{{ Auth::user()->name }}</p>
                                <p class="mb-0 designattion">{{ Auth::user()->email }}</p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('user.show', Auth::user()->id) }}"><i
                                        class="bx bx-user fs-5"></i><span>Profile</span></a>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center" href="javascript:;"><i
                                        class="bx bx-cog fs-5"></i><span>Settings</span></a>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('dashboardbody') }}"><i
                                        class="bx bx-home-circle fs-5"></i><span>Dashboard</span></a>
                            </li>

                            <li>
                                <div class="mb-0 dropdown-divider"></div>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"><i
                                        class="bx bx-log-out-circle"></i><span>Logout</span></a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </header>

        <!--end header -->
        <!--start page wrapper -->
        @yield('content')
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright © 2026. All right reserved.</p>
        </footer>
    </div>
    <!--end wrapper-->

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!--plugins-->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js') }}"></script>
    <script src="{{ asset('assets/plugins/chartjs/js/chart.js') }}"></script>
    <script src="{{ asset('assets/js/index.js') }}"></script>
    <!--app JS-->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/delete-confirmation.js') }}"></script>
    <script>
        new PerfectScrollbar(".app-container");
    </script>
    <script>
        let table = new DataTable('#myTable', {
            "pageLength": 5,
            "lengthMenu": [
                [5, 10, 25, 50, -1],
                [5, 10, 25, 50, "All"]
            ]
        });
    </script>

    @section('scripts')
        <script>
            document.getElementById('imageInput').onchange = function(evt) {
                const [file] = this.files;
                if (file) {
                    document.getElementById('preview').src = URL.createObjectURL(file);
                }
            };
        </script>
    @endsection
</body>

</html>
