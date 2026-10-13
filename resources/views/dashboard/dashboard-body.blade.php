@extends('dashboard.dashboard')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            {{-- আগের dashboard card layout অক্ষুণ্ণ রেখে চারটি KPI-র period value দেখানো হয়। --}}
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
                @php
                    $cards = [
                        'purchase' => ['title' => 'Total Purchase', 'icon' => 'bx-cart', 'color' => 'info', 'gradient' => 'bg-gradient-blues'],
                        'revenue' => ['title' => 'Total Revenue', 'icon' => 'bx-wallet', 'color' => 'danger', 'gradient' => 'bg-gradient-burning'],
                        'due' => ['title' => 'Total Due', 'icon' => 'bx-time-five', 'color' => 'warning', 'gradient' => 'bg-gradient-orange'],
                        'expenses' => ['title' => 'Total Expense', 'icon' => 'bx-money', 'color' => 'warning', 'gradient' => 'bg-gradient-orange'],
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
                                            ৳{{ number_format($metrics['monthly'][$metricKey], 2) }}
                                        </h4>
                                        <p class="mb-0 font-13 text-muted">
                                            Daily ৳{{ number_format($metrics['daily'][$metricKey], 2) }}
                                            <span class="mx-1">|</span>
                                            Weekly ৳{{ number_format($metrics['weekly'][$metricKey], 2) }}
                                        </p>
                                    </div>
                                    <div class="text-white widgets-icons-2 rounded-circle {{ $card['gradient'] }} ms-auto">
                                        <i class="bx {{ $card['icon'] }}"></i>
                                    </div>
                                </div>
                                @if ($metricKey === 'expenses')
                                    <small class="mt-2 text-muted d-block">Employee Voucher + Debit − Credit</small>
                                @else
                                    {{-- সব card-এর footer-এর জায়গা একই রাখলে card-এর উচ্চতাও এক থাকে। --}}
                                    <small class="mt-2 d-block" aria-hidden="true">&nbsp;</small>
                                @endif
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
                                                <td class="gap-2 d-flex align-items-center">
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
                Expense = Employee Voucher + Debit Voucher − Credit Voucher. Profit = Revenue − Purchase − Expense. Revenue excludes previous customer due.
            </div>
        </div>
    </div>

    <style>
        /* প্রতিটি KPI card-কে একই ন্যূনতম উচ্চতা দিয়ে বিভিন্ন স্ক্রিনে সমান রাখা হয়। */
        .dashboard-metric-card {
            min-height: 190px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                                label: context => context.dataset.label + ': ৳' + Number(context.raw).toLocaleString(),
                            },
                        },
                    },
                },
            });

            periodSelector.addEventListener('change', function () {
                const periodData = chartData[this.value];
                chart.data.labels = periodData.labels;
                chart.data.datasets[0].data = periodData.revenue;
                chart.data.datasets[1].data = periodData.profit;
                chart.update();
            });
        });
    </script>
@endsection
