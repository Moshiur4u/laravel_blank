@extends('dashboard.dashboard')
@section('title', 'Quotation - ' . $quotation->quotation_no)

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Action Toolbar (hidden on print) -->
            {{-- ছোট পর্দায় বাটনগুলো গুটিয়ে রাখে এবং অনুমতিপ্রাপ্ত ব্যবহারকারীকে ওয়াটারমার্ক চালু করতে দেয়। --}}
            <div class="flex-wrap gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('quotation.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> All Quotations
                </a>
                <a href="{{ route('quotation.create') }}" class="btn btn-outline-primary">
                    <i class="bx bx-plus me-1"></i> New Quotation
                </a>
                @can('sales.document.watermark')
                    @if ($organization?->logo)
                        <label class="mb-0 btn btn-outline-secondary">
                            <input type="checkbox" id="watermark-toggle" class="form-check-input me-1">
                            Print logo watermark
                        </label>
                    @endif
                @endcan
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Print Quotation
                </button>
            </div>

            <div id="print-document" class="border-0 shadow-sm card print-document quotation-document"
                @if ($organization?->logo) style="--invoice-watermark: url('{{ asset('uploads/organizations/' . $organization->logo) }}');" @endif>
                <div class="p-3 card-body p-md-5">
                    {{-- প্রতিষ্ঠানের পরিচিতি আলাদা হেডারে রাখলে প্রিন্টে লোগো ও তথ্য স্পষ্ট থাকে। --}}
                    <div class="gap-3 pb-3 mb-4 d-flex align-items-center border-bottom quotation-organization">
                        @if ($organization?->logo)
                            <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                alt="{{ $organization->name ?: 'Organization Logo' }}"
                                style="max-width: 90px; max-height: 65px; object-fit: contain;">
                        @else
                            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo"
                                style="max-width: 60px; max-height: 60px; object-fit: contain;">
                        @endif
                        <div>
                            <h4 class="mb-0 fw-bold text-dark">{{ $organization?->name ?: 'My-POS Enterprise' }}</h4>
                            <div class="small text-muted">Official Quotation</div>
                            @if ($organization?->address)
                                <div class="small text-muted">{{ $organization->address }}</div>
                            @endif
                            @if ($organization?->phone)
                                <div class="small text-muted">Phone: {{ $organization->phone }}</div>
                            @endif
                            @if ($organization?->email)
                                <div class="small text-muted">Email: {{ $organization->email }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- উদ্ধৃতির পরিচিতি ও গ্রাহকের তথ্য পাশাপাশি রেখে ছোট পর্দায় একটির নিচে আরেকটি দেখানো হয়। --}}
                    <div class="mb-4 row align-items-start quotation-header">
                        <div class="col-12 col-md-7">
                            <h2 class="mb-1 text-primary fw-bold">Quotation</h2>
                            <div class="text-muted">Ref No: <strong>{{ $quotation->quotation_no }}</strong></div>
                            <div class="text-muted">Date: {{ $quotation->quotation_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-12 col-md-5 text-md-end">
                            <h5 class="mb-1 fw-bold">Customer</h5>
                            <div><i class="bx bx-user me-1" aria-hidden="true"></i>Name: {{ $quotation->customer_name }}
                            </div>
                            <div><i class="bx bx-phone me-1" aria-hidden="true"></i>Phone: {{ $quotation->customer_phone }}
                            </div>
                            @if ($quotation->customer_address)
                                <div><i class="bx bx-map me-1" aria-hidden="true"></i>Address:
                                    {{ $quotation->customer_address }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Products Table (Customer Invoice Format) -->
                    <div class="mb-4 table-responsive">
                        <table class="table mb-0 align-middle table-bordered quotation-items">
                            {{-- স্থির কলাম প্রস্থ পণ্যের বর্ণনাকে মূল্য ও পরিমাণের কলাম থেকে আলাদা রাখে। --}}
                            <colgroup>
                                <col style="width: 6%;">
                                <col style="width: 32%;">
                                <col style="width: 18%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 20%;">
                            </colgroup>
                            {{-- চালানের প্রিন্ট ডিজাইনের সঙ্গে মিল রাখতে টেবিলের শিরোনামে একই নীল রঙ ব্যবহার করা হয়। --}}
                            <thead>
                                <tr>
                                    <th class="text-center text-white" style="background-color: #14ADE0;">#</th>
                                    <th class="text-white" style="background-color: #14ADE0;">Product Description</th>
                                    <th class="text-white text-end" style="background-color: #14ADE0;">Unit Price</th>
                                    <th class="text-center text-white" style="background-color: #14ADE0;">Quantity</th>
                                    <th class="text-center text-white" style="background-color: #14ADE0;">Unit</th>
                                    <th class="text-white text-end" style="background-color: #14ADE0;">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quotation->items as $index => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td class="quotation-product-name">
                                            <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                            {{-- পণ্যের নামের জন্য বেশি জায়গা রাখতে Item Code কোটেশনের প্রিন্টে দেখানো হয় না। --}}
                                            {{-- @if ($item->product_id)
                                                <small class="text-muted">Item Code: PRD-{{ str_pad($item->product_id, 5, '0', STR_PAD_LEFT) }}</small>
                                            @endif --}}
                                        </td>
                                        <td class="text-end">৳{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-center">{{ $item->quantity_size ?: ($item->product?->quantity_size ?: ($item->product?->stock_unit ?: 'Unit')) }}</td> {{-- quotation-এ সংরক্ষিত unit আগে দেখায়, পুরোনো item-এ product unit দিয়ে fallback করে। --}}
                                        <td class="text-end fw-bold">৳{{ number_format((float) $item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Section (Customer Invoice Format) -->
                    <div class="mb-4 row justify-content-end">
                        <div class="col-md-6 col-lg-5">
                            <div class="py-1 d-flex justify-content-between">
                                <span class="text-secondary">Subtotal</span>
                                <span class="fw-semibold">৳{{ number_format((float) $quotation->subtotal, 2) }}</span>
                            </div>

                            @if ((float) $quotation->discount_amount > 0)
                                <div class="py-1 d-flex justify-content-between text-danger">
                                    <span>
                                        Discount
                                        @if ($quotation->discount_type === 'percentage')
                                            ({{ rtrim(rtrim(number_format((float) $quotation->discount_value, 2), '0'), '.') }}%)
                                        @endif
                                    </span>
                                    <span>- ৳{{ number_format((float) $quotation->discount_amount, 2) }}</span>
                                </div>
                            @endif

                            @if ((float) $quotation->vat_amount > 0)
                                <div class="py-1 d-flex justify-content-between text-secondary">
                                    <span>VAT
                                        ({{ rtrim(rtrim(number_format((float) $quotation->vat_percentage, 2), '0'), '.') }}%)</span>
                                    <span>+ ৳{{ number_format((float) $quotation->vat_amount, 2) }}</span>
                                </div>
                            @endif

                            @if ((float) $quotation->ait_amount > 0)
                                <div class="py-1 d-flex justify-content-between text-secondary">
                                    <span>AIT
                                        ({{ rtrim(rtrim(number_format((float) $quotation->ait_percentage, 2), '0'), '.') }}%)</span>
                                    <span>+ ৳{{ number_format((float) $quotation->ait_amount, 2) }}</span>
                                </div>
                            @endif

                            <hr class="my-2">

                            {{-- মোট মূল্যকে হালকা ব্যাকগ্রাউন্ডে আলাদা করে সহজে চোখে পড়ার ব্যবস্থা করা হয়েছে। --}}
                            <div class="px-3 py-2 rounded d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5 text-primary">Total Price</span>
                                <span
                                    class="fw-bold fs-4 text-primary">৳{{ number_format((float) $quotation->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions Section -->
                    @if ($quotation->terms_conditions)
                        <div class="p-3 mt-4 border rounded">
                            <h6 class="mb-2 fw-bold text-dark">
                                <i class="bx bx-check-shield text-primary me-1"></i> Terms & Conditions
                            </h6>
                            <div class="small text-secondary" style="white-space: pre-line; line-height: 1.6;">
                                {{ $quotation->terms_conditions }}
                            </div>
                        </div>
                    @endif

                    @if ($quotation->notes)
                        <div class="p-3 mt-3 border rounded bg-light">
                            <h6 class="mb-1 fw-bold text-dark">
                                <i class="bx bx-note text-secondary me-1"></i> Special Notes
                            </h6>
                            <div class="small text-muted">{{ $quotation->notes }}</div>
                        </div>
                    @endif

                    <!-- Signatures Block -->
                    <div class="pt-5 mt-4 row">
                        <div class="col-6">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Customer Acceptance</div>
                                <div class="small text-muted">Signature & Date</div>
                            </div>
                        </div>
                        <div class="col-6 text-end d-flex justify-content-end">
                            <div class="pt-2 text-center border-top w-75">
                                <div class="fw-semibold text-dark">Authorized Signature</div>
                                <div class="small text-muted">Prepared by: {{ $quotation->user->name ?? 'Sales Officer' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 mb-0 text-center text-muted small">
                        This is a computer-generated quotation document. Thank you for your business.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Specific Stylesheet -->
    <style>
        .quotation-document {
            min-width: 0;
        }

        .quotation-organization,
        .quotation-header {
            overflow-wrap: anywhere;
        }

        /* স্থির টেবিল লেআউট ও লেখা ভাঙা দীর্ঘ পণ্যের নামকে পাশের কলামে যাওয়া থেকে আটকায়। */
        .quotation-items {
            width: 100%;
            table-layout: fixed;
        }

        .quotation-product-name {
            min-width: 0;
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .quotation-product-name>* {
            min-width: 0;
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* মোবাইলে টেবিলের লেখা ও padding কমিয়ে দৃশ্যমান জায়গার সঙ্গে মানানো হয়। */
        @media (max-width: 575.98px) {
            .quotation-items {
                font-size: .8rem;
            }

            .quotation-items th,
            .quotation-items td {
                padding: .4rem;
            }
        }

        /* প্রিন্টে নেভিগেশন বাদ দিয়ে উদ্ধৃতির বিষয়বস্তু ও ঐচ্ছিক লোগো ওয়াটারমার্ক রাখা হয়। */
        @media print {

            header,
            .topbar,
            .sidebar-wrapper,
            .no-print,
            .page-footer,
            .footer,
            .back-to-top {
                display: none !important;
            }

            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
                border: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .quotation-items {
                width: 100% !important;
                table-layout: fixed;
                font-size: 9pt;
            }

            .quotation-items th,
            .quotation-items td {
                padding: .35rem;
            }

            .quotation-document {
                position: relative;
                isolation: isolate;
            }

            .quotation-header>div {
                flex: 0 0 58.333333%;
                width: 58.333333%;
            }

            .quotation-header>div:last-child {
                flex: 0 0 41.666667%;
                width: 41.666667%;
            }

            .quotation-document.watermark-enabled::before {
                content: '';
                position: fixed;
                inset: 0;
                z-index: 0;
                background-image: var(--invoice-watermark);
                background-position: center;
                background-repeat: no-repeat;
                background-size: min(65vw, 600px);
                opacity: .09;
                pointer-events: none;
            }

            .quotation-document>.card-body {
                position: relative;
                z-index: 1;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
    @can('sales.document.watermark')
        @if ($organization?->logo)
            {{-- checkbox বদলালে শুধু প্রিন্ট ডকুমেন্টে ওয়াটারমার্কের class যোগ বা সরানো হয়। --}}
            <script>
                document.getElementById('watermark-toggle')?.addEventListener('change', function() {
                    document.getElementById('print-document')?.classList.toggle('watermark-enabled', this.checked);
                });
            </script>
        @endif
    @endcan
@endsection
