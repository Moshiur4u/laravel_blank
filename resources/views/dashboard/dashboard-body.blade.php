@extends('dashboard.dashboard')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- আগের dashboard card layout-এ Profit Card যুক্ত করে মোট ৫টি KPI প্রদর্শিত হয়। --}}
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3">
                @php
                    $cards = [
                        'purchase' => [
                            'title' => 'Total Purchase',
                            'icon' => 'bx-cart',
                            'color' => 'info',
                            'gradient' => 'bg-gradient-blues',
                        ],
                        'revenue' => [
                            'title' => 'Total Revenue',
                            'icon' => 'bx-wallet',
                            'color' => 'danger',
                            'gradient' => 'bg-gradient-burning',
                        ],
                        'due' => [
                            'title' => 'Total Due',
                            'icon' => 'bx-time-five',
                            'color' => 'warning',
                            'gradient' => 'bg-gradient-orange',
                        ],
                        'expenses' => [
                            'title' => 'Total Expense',
                            'icon' => 'bx-money',
                            'color' => 'secondary',
                            'gradient' => 'bg-gradient-orange',
                        ],
                        'profit' => [
                            'title' => 'Net Profit',
                            'icon' => 'bx-trending-up',
                            'color' => 'success',
                            'gradient' => 'bg-gradient-ohhappiness',
                        ],
                    ];
                @endphp

                @foreach ($cards as $metricKey => $card)
                    <div class="col d-flex">
                        <div
                            class="border-4 card radius-10 border-start border-{{ $card['color'] }} dashboard-metric-card w-100">
                            <div class="text-center card-body d-flex flex-column justify-content-between">
                                {{-- ড্যাশবোর্ড কার্ডের আইকন সেন্টারে স্থাপন করা হয়েছে --}}
                                <div class="text-white widgets-icons-2 rounded-circle {{ $card['gradient'] }} mx-auto mb-2">
                                    <i class="bx {{ $card['icon'] }}"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-secondary fw-semibold">{{ $card['title'] }}</p>
                                    @if ($metricKey === 'profit')
                                        {{-- Profit Card: বড় ফন্টে বাৎসরিক লাভ এবং ছোট ফন্টে দৈনিক, সাপ্তাহিক ও মাসিক লাভ (রাউন্ডেড অ্যামাউন্ট) --}}
                                        <h4 class="my-1 text-{{ $card['color'] }} fw-bold">
                                            ৳{{ number_format(round($metrics['yearly']['profit'])) }}
                                        </h4>
                                        <p class="mb-0 font-13 text-muted">
                                            Daily ৳{{ number_format(round($metrics['daily']['profit'])) }}
                                            <span class="mx-1">|</span>
                                            Weekly ৳{{ number_format(round($metrics['weekly']['profit'])) }}
                                            {{-- <span class="mx-1">|</span>
                                            Monthly ৳{{ number_format(round($metrics['monthly']['profit'])) }} --}}
                                        </p>
                                    @else
                                        {{-- অন্যান্য কার্ডে বড় ফন্টে মাসিক মোট এবং ছোট ফন্টে দৈনিক ও সাপ্তাহিক মান (রাউন্ডেড অ্যামাউন্ট) --}}
                                        <h4 class="my-1 text-{{ $card['color'] }} fw-bold">
                                            ৳{{ number_format(round($metrics['monthly'][$metricKey])) }}
                                        </h4>
                                        <p class="mb-0 font-13 text-muted">
                                            Daily ৳{{ number_format(round($metrics['daily'][$metricKey])) }}
                                            <span class="mx-1">|</span>
                                            Weekly ৳{{ number_format(round($metrics['weekly'][$metricKey])) }}
                                            {{-- <span class="mx-1">|</span>
                                            Monthly ৳{{ number_format(round($metrics['monthly'][$metricKey])) }} --}}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 card radius-10">
                <div class="flex-wrap gap-2 card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0">Profit Trend</h6>
                        <small class="text-muted">Revenue columns and profit line</small>
                    </div>
                    <select id="profit-chart-period" class="form-select form-select-sm" aria-label="Profit chart period"
                        style="width: auto">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div class="card-body">
                    <div style="height: 320px">
                        <canvas id="profit-trend-chart" aria-label="Revenue and profit chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            {{-- দুইটি আলাদা টেবিলে সর্বাধিক বিক্রিত পণ্য ও সর্বাধিক বিক্রয় করা কর্মী দেখানো হয়। --}}
            <div class="mt-1 row g-3">
                <div class="col-12 col-xl-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Top Selling Products <small class="text-muted">(All time by quantity)</small>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th class="text-end">Qty Sold</th>
                                            <th class="text-end">Sales Amount</th>
                                            <th class="text-end">Profit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($topProducts as $product)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="gap-2 d-flex align-items-center">
                                                    @if ($product->img_url)
                                                        <img src="{{ asset('uploads/products/' . $product->img_url) }}"
                                                            alt="{{ $product->product_name }}" class="product-img-2">
                                                    @endif
                                                    <span>{{ $product->product_name }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format((int) $product->quantity_sold) }}
                                                </td>
                                                <td class="text-end">
                                                    ৳{{ number_format((float) $product->sales_amount, 2) }}</td>
                                                <td class="text-end fw-semibold text-success">
                                                    ৳{{ number_format(round((float) $product->profit)) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-muted">No sales recorded
                                                    yet.</td>
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
                            <h6 class="mb-0">Top Sales Employees <small class="text-muted">(All time by sales
                                    amount)</small></h6>
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
                                                <td class="text-end">{{ number_format((int) $employee->invoice_count) }}
                                                </td>
                                                <td class="text-end">
                                                    ৳{{ number_format((float) $employee->sales_amount, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="py-4 text-center text-muted">No sales recorded
                                                    yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="mt-3 text-muted small">
                Expense = Employee Voucher + Debit Voucher − Credit Voucher. Profit = Revenue − Purchase − Expense. Revenue excludes previous customer due.
            </div> --}}
        </div>
    </div>

    <style>
        /* প্রতিটি KPI card-কে একই ন্যূনতম উচ্চতা দিয়ে বিভিন্ন স্ক্রিনে সমান রাখা হয়। */
        .dashboard-metric-card {
            min-height: 190px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($profitChart);
            const periodSelector = document.getElementById('profit-chart-period');
            const chartContext = document.getElementById('profit-trend-chart');

            if (!periodSelector || !chartContext || typeof Chart === 'undefined') {
                return;
            }

            const chart = new Chart(chartContext, {
                type: 'bar',
                data: {
                    labels: chartData.daily.labels,
                    datasets: [{
                        type: 'bar',
                        label: 'Revenue',
                        data: chartData.daily.revenue,
                        backgroundColor: 'rgba(54, 162, 235, 0.45)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1,
                    }, {
                        type: 'line',
                        label: 'Profit',
                        data: chartData.daily.profit,
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgb(40, 167, 69)',
                        tension: 0.3,
                        pointRadius: 3,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '৳' + Number(value).toLocaleString(),
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: context => context.dataset.label + ': ৳' + Number(context.raw)
                                    .toLocaleString(),
                            },
                        },
                    },
                },
            });

            periodSelector.addEventListener('change', function() {
                const periodData = chartData[this.value];
                chart.data.labels = periodData.labels;
                chart.data.datasets[0].data = periodData.revenue;
                chart.data.datasets[1].data = periodData.profit;
                chart.update();
            });
        });
    </script>
@endsection
