@extends('dashboard.dashboard')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- আগের dashboard card layout অক্ষুণ্ণ রেখে চারটি KPI-র period value দেখানো হয়। --}}
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
                @php
                    $cards = [
                        'revenue' => ['title' => 'Total Revenue', 'icon' => 'bx-wallet', 'color' => 'danger', 'gradient' => 'bg-gradient-burning'],
                        'due' => ['title' => 'Total Due', 'icon' => 'bx-time-five', 'color' => 'warning', 'gradient' => 'bg-gradient-orange'],
                        'profit' => ['title' => 'Estimated Profit', 'icon' => 'bx-bar-chart-alt-2', 'color' => 'success', 'gradient' => 'bg-gradient-ohhappiness'],
                        'purchase' => ['title' => 'Purchase Order Amount', 'icon' => 'bx-cart', 'color' => 'info', 'gradient' => 'bg-gradient-blues'],
                    ];
                @endphp

                @foreach ($cards as $metricKey => $card)
                    <div class="col d-flex">
                        <div class="border-4 card radius-10 border-start border-{{ $card['color'] }} dashboard-metric-card w-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">{{ $card['title'] }}</p>
                                        {{-- আগের বড় value স্থানে monthly total; নিচে daily ও weekly value-ও রাখা হয়। --}}
                                        <h4 class="my-1 text-{{ $card['color'] }}">
                                            @if ($metricKey === 'profit' && $metrics['monthly']['profit'] === null)
                                                Cost missing
                                            @else
                                                ৳{{ number_format($metrics['monthly'][$metricKey], 2) }}
                                            @endif
                                        </h4>
                                        <p class="mb-0 font-13 text-muted">
                                            Daily
                                            @if ($metricKey === 'profit' && $metrics['daily']['profit'] === null)
                                                Cost missing
                                            @else
                                                ৳{{ number_format($metrics['daily'][$metricKey], 2) }}
                                            @endif
                                            <span class="mx-1">|</span>
                                            Weekly
                                            @if ($metricKey === 'profit' && $metrics['weekly']['profit'] === null)
                                                Cost missing
                                            @else
                                                ৳{{ number_format($metrics['weekly'][$metricKey], 2) }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-white widgets-icons-2 rounded-circle {{ $card['gradient'] }} ms-auto">
                                        <i class="bx {{ $card['icon'] }}"></i>
                                    </div>
                                </div>
                                @if ($metricKey === 'profit')
                                    <small class="mt-2 text-muted d-block">Estimated using current purchase prices</small>
                                @else
                                    {{-- সব card-এর footer-এর জায়গা একই রাখলে card-এর উচ্চতাও এক থাকে। --}}
                                    <small class="mt-2 d-block" aria-hidden="true">&nbsp;</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- দুইটি আলাদা টেবিলে সর্বাধিক বিক্রিত পণ্য ও সর্বাধিক বিক্রয় করা কর্মী দেখানো হয়। --}}
            <div class="row g-3 mt-1">
                <div class="col-12 col-xl-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Top Selling Products <small class="text-muted">(All time by quantity)</small></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th class="text-end">Quantity Sold</th>
                                            <th class="text-end">Sales Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topProducts as $product)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="d-flex align-items-center gap-2">
                                                    @if ($product->img_url)
                                                        <img src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                            alt="{{ $product->product_name }}"
                                                            class="product-img-2">
                                                    @endif
                                                    <span>{{ $product->product_name }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format((int) $product->quantity_sold) }}</td>
                                                <td class="text-end">৳{{ number_format((float) $product->sales_amount, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-muted">No sales recorded yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Top Sales Employees <small class="text-muted">(All time by sales amount)</small></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th class="text-end">Invoices</th>
                                            <th class="text-end">Sales Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topEmployees as $employee)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $employee->employee_name }}</td>
                                                <td class="text-end">{{ number_format((int) $employee->invoice_count) }}</td>
                                                <td class="text-end">৳{{ number_format((float) $employee->sales_amount, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-muted">No sales recorded yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-muted small">
                Revenue and employee sales exclude previous customer due. Profit excludes products without a recorded purchase price and does not allocate invoice-level discounts or VAT.
            </div>
        </div>
    </div>

    <style>
        /* প্রতিটি KPI card-কে একই ন্যূনতম উচ্চতা দিয়ে বিভিন্ন স্ক্রিনে সমান রাখা হয়। */
        .dashboard-metric-card {
            min-height: 190px;
        }
    </style>
@endsection
