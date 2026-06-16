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
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <!-- Theme Style CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />

    <!--Datatable -->
    <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.2.2/r-3.0.3/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.2.2/r-3.0.3/datatables.min.js"></script>
    <title>PMS</title>
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <div>
                    <img src="{{ asset('assets/images/logo-icon.png') }}" class="logo-icon" alt="logo icon">
                </div>
                <div>
                    <h4 class="logo-text">TechTuner Software</h4>
                </div>
                <div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i>
                </div>
            </div>
            <!--navigation-->
            <ul class="metismenu" id="menu">
                <li>
                    <a href="javascript:;" class="">
                        <div class="parent-icon"><i class='bx bx-home-alt'></i>
                        </div>
                        <div class="menu-title">Dashboard</div>
                    </a>

                </li>
                <li class="menu-label">Regural Operation</li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bx-home-alt'></i></div>
                        <div class="menu-title">Sales</div>
                    </a>
                    <ul>
                        <li>
                            <a href="javascript:;"><i class='bx bx-plus-medical'></i>Create Sale</a>
                        </li>

                    </ul>
                </li>
                <li class="menu-label">Managerial Operations</li>

                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bx bx-customize "></i></div>
                        <div class="menu-title">Category</div>
                    </a>
                    <ul>
                        <li>
                            <a href="javascript:;"><i class='bx bx-food-menu'></i>All Category</a>
                        </li>

                        <li>
                            <a href="javascript:;"><i class=' bx bx-plus-medical'></i>Create Category</a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bxl-product-hunt '></i>
                        </div>
                        <div class="menu-title">Product</div>
                    </a>
                    <ul>
                        <li>
                            <a href="javascript:;"><i class='bx bx-food-menu'></i>All Product</a>
                        </li>

                        <li>
                            <a href="javascript:;"><i class='bx bx-plus-medical'></i>Create Product</a>
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
                            <a href="javascript:;"><i class=' bx bx-id-card'></i>All Customer</a>
                        </li>

                        <li>
                            <a href="javascript:;"><i class='bx bx-user-plus '></i>Create Customer</a>
                        </li>
                        <li>
                            <a href="javascript:;"><i class='bx bx-list-ul'></i>Customer Ledger</a>
                        </li>

                    </ul>
                </li>
                <li class="menu-label">Administration operations</li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class="bx bxs-user-pin "></i>
                        </div>
                        <div class="menu-title">Empolyee</div>
                    </a>
                    <ul>

                        <li>
                            <a href="#"><i class=' bx bx-id-card'></i>All Empolyee</a>
                        </li>

                        <li>
                            <a href="#"><i class='bx bx-user-plus '></i>Create Empolyee</a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class='bx bx-cart'></i>
                        </div>
                        <div class="menu-title">Role and Permission</div>
                    </a>
                    <ul>
                        <li>
                            <a href="javascript:;"><i class='bx bx-radio-circle'></i>All Roles</a>
                        </li>

                        <li>
                            <a href="javascript:;"><i class='bx bx-radio-circle'></i>Create Role</a>
                        </li>
                    </ul>
                </li>
                <li class="menu-label">Reports</li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class='bx bx-message-square-edit'></i>
                        </div>
                        <div class="menu-title">Forms</div>
                    </a>
                    <ul>
                        <li> <a href="form-elements.html"><i class='bx bx-radio-circle'></i>Form Elements</a>
                        </li>
                        <li> <a href="form-input-group.html"><i class='bx bx-radio-circle'></i>Input Groups</a>
                        </li>
                        <li> <a href="form-radios-and-checkboxes.html"><i class='bx bx-radio-circle'></i>Radios &
                                Checkboxes</a>
                        </li>
                        <li> <a href="form-layouts.html"><i class='bx bx-radio-circle'></i>Forms Layouts</a>
                        </li>
                        <li> <a href="form-validations.html"><i class='bx bx-radio-circle'></i>Form Validation</a>
                        </li>
                        <li> <a href="form-wizard.html"><i class='bx bx-radio-circle'></i>Form Wizard</a>
                        </li>
                        <li> <a href="form-text-editor.html"><i class='bx bx-radio-circle'></i>Text Editor</a>
                        </li>
                        <li> <a href="form-file-upload.html"><i class='bx bx-radio-circle'></i>File Upload</a>
                        </li>
                        <li> <a href="form-date-time-pickes.html"><i class='bx bx-radio-circle'></i>Date Pickers</a>
                        </li>
                        <li> <a href="form-select2.html"><i class='bx bx-radio-circle'></i>Select2</a>
                        </li>
                        <li> <a href="form-repeater.html"><i class='bx bx-radio-circle'></i>Form Repeater</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="javascript:;">
                        <div class="parent-icon"><i class="bx bx-grid-alt"></i>
                        </div>
                        <div class="menu-title">Tables</div>
                    </a>
                    <ul>
                        <li> <a href="table-basic-table.html"><i class='bx bx-radio-circle'></i>Basic Table</a>
                        </li>
                        <li> <a href="table-datatable.html"><i class='bx bx-radio-circle'></i>Data Table</a>
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

                    <div class="position-relative search-bar d-lg-block d-none" data-bs-toggle="modal"
                        data-bs-target="#SearchModal">
                        <input class="px-5 form-control" disabled type="search" placeholder="Search">
                        <span class="position-absolute top-50 search-show ms-3 translate-middle-y start-0 fs-5"><i
                                class='bx bx-search'></i></span>
                    </div>


                    <div class="top-menu ms-auto">
                        <ul class="gap-1 navbar-nav align-items-center">

                            <li class="nav-item mobile-search-icon d-flex d-lg-none" data-bs-toggle="modal"
                                data-bs-target="#SearchModal">
                                <a class="nav-link" href="avascript:;"><i class='bx bx-search'></i>
                                </a>
                            </li>


                            <li class="nav-item dark-mode d-none d-sm-flex">
                                <a class="nav-link dark-mode-icon" href="javascript:;"><i class='bx bx-moon'></i>
                                </a>
                            </li>


                            <li class="nav-item dropdown dropdown-large">
                                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative"
                                    href="#" data-bs-toggle="dropdown"><span class="alert-count">7</span>
                                    <i class='bx bx-bell'></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:;">
                                        <div class="msg-header">
                                            <p class="msg-header-title">Notifications</p>
                                            <p class="msg-header-badge">8 New</p>
                                        </div>
                                    </a>
                                    <div class="header-notifications-list">
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="user-online">
                                                    <img src="{{ asset('assets/images/avatars/avatar-1.png') }}"
                                                        class="msg-avatar" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Daisy Anderson<span
                                                            class="msg-time float-end">5 sec
                                                            ago</span></h6>
                                                    <p class="msg-info">The standard chunk of lorem</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="notify bg-light-danger text-danger">dc
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">New Orders <span class="msg-time float-end">2
                                                            min
                                                            ago</span></h6>
                                                    <p class="msg-info">You have recived new orders</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="user-online">
                                                    <img src="{{ asset('assets/images/avatars/avatar-2.png') }}"
                                                        class="msg-avatar" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Althea Cabardo <span
                                                            class="msg-time float-end">14
                                                            sec ago</span></h6>
                                                    <p class="msg-info">Many desktop publishing packages</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="notify bg-light-success text-success">
                                                    <img src="{{ asset('assets/images/app/outlook.png') }}"
                                                        width="25" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Account Created<span
                                                            class="msg-time float-end">28 min
                                                            ago</span></h6>
                                                    <p class="msg-info">Successfully created new email</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="notify bg-light-info text-info">Ss
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">New Product Approved <span
                                                            class="msg-time float-end">2 hrs ago</span></h6>
                                                    <p class="msg-info">Your new product has approved</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="user-online">
                                                    <img src="{{ asset('assets/images/avatars/avatar-4.png') }}"
                                                        class="msg-avatar" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Katherine Pechon <span
                                                            class="msg-time float-end">15
                                                            min ago</span></h6>
                                                    <p class="msg-info">Making this the first true generator</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="notify bg-light-success text-success"><i
                                                        class='bx bx-check-square'></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Your item is shipped <span
                                                            class="msg-time float-end">5 hrs
                                                            ago</span></h6>
                                                    <p class="msg-info">Successfully shipped your item</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="notify bg-light-primary">
                                                    <img src="{{ asset('assets/images/app/github.png') }}"
                                                        width="25" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">New 24 authors<span
                                                            class="msg-time float-end">1 day
                                                            ago</span></h6>
                                                    <p class="msg-info">24 new authors joined last week</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="d-flex align-items-center">
                                                <div class="user-online">
                                                    <img src="{{ asset('assets/images/avatars/avatar-8.png') }}"
                                                        class="msg-avatar" alt="user avatar">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="msg-name">Peter Costanzo <span
                                                            class="msg-time float-end">6 hrs
                                                            ago</span></h6>
                                                    <p class="msg-info">It was popularised in the 1960s</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <a href="javascript:;">
                                        <div class="text-center msg-footer">
                                            <button class="btn btn-primary w-100">View All Notifications</button>
                                        </div>
                                    </a>
                                </div>
                            </li>

                            <li class="nav-item dropdown dropdown-large">
                                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative"
                                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="alert-count">8</span>
                                    <i class='bx bx-shopping-bag'></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:;">
                                        <div class="msg-header">
                                            <p class="msg-header-title">My Cart</p>
                                            <p class="msg-header-badge">10 Items</p>
                                        </div>
                                    </a>
                                    <div class="header-message-list">
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/11.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/02.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/03.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/04.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/05.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/06.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/07.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/08.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <a class="dropdown-item" href="javascript:;">
                                            <div class="gap-3 d-flex align-items-center">
                                                <div class="position-relative">
                                                    <div class="cart-product rounded-circle bg-light">
                                                        <img src="{{ asset('assets/images/products/09.png') }}"
                                                            class="" alt="product image">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 cart-product-title">Men White T-Shirt</h6>
                                                    <p class="mb-0 cart-product-price">1 X $29.00</p>
                                                </div>
                                                <div class="">
                                                    <p class="mb-0 cart-price">$250</p>
                                                </div>
                                                <div class="cart-product-cancel"><i class="bx bx-x"></i>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <a href="javascript:;">
                                        <div class="text-center msg-footer">
                                            <div class="mb-3 d-flex align-items-center justify-content-between">
                                                <h5 class="mb-0">Total</h5>
                                                <h5 class="mb-0 ms-auto">$489.00</h5>
                                            </div>
                                            <button class="btn btn-primary w-100">Checkout</button>
                                        </div>
                                    </a>
                                </div>
                            </li>

                        </ul>
                    </div>
                    <div class="px-3 user-box dropdown">

                        <a class="gap-3 d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('assets/images/avatars/avatar-2.png') }}" class="user-img"
                                alt="user avatar">
                            <div class="user-info">
                                <p class="mb-0 user-name">Moshiur Rahman</p>
                                <p class="mb-0 designattion">moshiur.it@gmail.com</p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item d-flex align-items-center" href="javascript:;"><i
                                        class="bx bx-user fs-5"></i><span>Profile</span></a>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center" href="javascript:;"><i
                                        class="bx bx-cog fs-5"></i><span>Settings</span></a>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center" href="javascript:;"><i
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
        <div class="page-wrapper">
            <div class="page-content">
                <!-- start-content -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
                    <div class="col">
                        <div class="border-4 card radius-10 border-start border-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Total Orders</p>
                                        <h4 class="my-1 text-info">4805</h4>
                                        <p class="mb-0 font-13">+2.5% from last week</p>
                                    </div>
                                    <div class="text-white widgets-icons-2 rounded-circle bg-gradient-blues ms-auto"><i
                                            class='bx bxs-cart'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="border-4 card radius-10 border-start border-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Total Revenue</p>
                                        <h4 class="my-1 text-danger">$84,245</h4>
                                        <p class="mb-0 font-13">+5.4% from last week</p>
                                    </div>
                                    <div class="text-white widgets-icons-2 rounded-circle bg-gradient-burning ms-auto">
                                        <i class='bx bxs-wallet'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="border-4 card radius-10 border-start border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Bounce Rate</p>
                                        <h4 class="my-1 text-success">34.6%</h4>
                                        <p class="mb-0 font-13">-4.5% from last week</p>
                                    </div>
                                    <div
                                        class="text-white widgets-icons-2 rounded-circle bg-gradient-ohhappiness ms-auto">
                                        <i class='bx bxs-bar-chart-alt-2'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="border-4 card radius-10 border-start border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Total Customers</p>
                                        <h4 class="my-1 text-warning">8.4K</h4>
                                        <p class="mb-0 font-13">+8.4% from last week</p>
                                    </div>
                                    <div class="text-white widgets-icons-2 rounded-circle bg-gradient-orange ms-auto">
                                        <i class='bx bxs-group'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!--end row-->

                <div class="card radius-10">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div>
                                <h6 class="mb-0">Top Saling Product</h6>
                            </div>
                            <div class="dropdown ms-auto">
                                <a class="dropdown-toggle dropdown-toggle-nocaret" href="#"
                                    data-bs-toggle="dropdown"><i
                                        class='bx bx-dots-horizontal-rounded font-22 text-option'></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:;">Action</a>
                                    </li>
                                    <li><a class="dropdown-item" href="javascript:;">Another action</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="javascript:;">Something else here</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Photo</th>
                                        <th>Product ID</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Iphone 5</td>
                                        <td><img src="{{ asset('assets/images/products/01.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#9405822</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-quepal w-100">Paid</span>
                                        </td>
                                        <td>$1250.00</td>
                                    </tr>

                                    <tr>
                                        <td>Earphone GL</td>
                                        <td><img src="{{ asset('assets/images/products/02.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#8304620</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-blooker w-100">Pending</span>
                                        </td>
                                        <td>$1500.00</td>
                                    </tr>

                                    <tr>
                                        <td>HD Hand Camera</td>
                                        <td><img src="{{ asset('assets/images/products/03.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#4736890</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-bloody w-100">Failed</span>
                                        </td>
                                        <td>$1400.00</td>
                                    </tr>

                                    <tr>
                                        <td>Clasic Shoes</td>
                                        <td><img src="{{ asset('assets/images/products/04.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#8543765</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-quepal w-100">Paid</span>
                                        </td>
                                        <td>$1200.00</td>
                                    </tr>
                                    <tr>
                                        <td>Sitting Chair</td>
                                        <td><img src="{{ asset('assets/images/products/06.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#9629240</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-blooker w-100">Pending</span>
                                        </td>
                                        <td>$1500.00</td>
                                    </tr>
                                    <tr>
                                        <td>Hand Watch</td>
                                        <td><img src="{{ asset('assets/images/products/05.png') }}"
                                                class="product-img-2" alt="product img"></td>
                                        <td>#8506790</td>
                                        <td><span
                                                class="text-white shadow-sm badge bg-gradient-bloody w-100">Failed</span>
                                        </td>
                                        <td>$1800.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- end-content -->
            </div>
        </div>
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
</body>

</html>
