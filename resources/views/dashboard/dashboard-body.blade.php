@extends('dashboard.dashboard')
@section('content')
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
                                <div class="text-white widgets-icons-2 rounded-circle bg-gradient-ohhappiness ms-auto">
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
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i
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
                                    <td><img src="{{ asset('assets/images/products/01.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#9405822</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-quepal w-100">Paid</span>
                                    </td>
                                    <td>$1250.00</td>
                                </tr>

                                <tr>
                                    <td>Earphone GL</td>
                                    <td><img src="{{ asset('assets/images/products/02.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#8304620</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-blooker w-100">Pending</span>
                                    </td>
                                    <td>$1500.00</td>
                                </tr>

                                <tr>
                                    <td>HD Hand Camera</td>
                                    <td><img src="{{ asset('assets/images/products/03.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#4736890</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-bloody w-100">Failed</span>
                                    </td>
                                    <td>$1400.00</td>
                                </tr>

                                <tr>
                                    <td>Clasic Shoes</td>
                                    <td><img src="{{ asset('assets/images/products/04.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#8543765</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-quepal w-100">Paid</span>
                                    </td>
                                    <td>$1200.00</td>
                                </tr>
                                <tr>
                                    <td>Sitting Chair</td>
                                    <td><img src="{{ asset('assets/images/products/06.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#9629240</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-blooker w-100">Pending</span>
                                    </td>
                                    <td>$1500.00</td>
                                </tr>
                                <tr>
                                    <td>Hand Watch</td>
                                    <td><img src="{{ asset('assets/images/products/05.png') }}" class="product-img-2"
                                            alt="product img"></td>
                                    <td>#8506790</td>
                                    <td><span class="text-white shadow-sm badge bg-gradient-bloody w-100">Failed</span>
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
@endsection
