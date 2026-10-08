@extends('dashboard.dashboard')
@section('title')
    Supplier Ledger - {{ $supplier->name }}
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Supplier</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier.index') }}">Supplier List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $supplier->name }} - Ledger</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('supplier.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i>
                            Back To Supplier List</a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            {{-- ============== Supplier Details Card ============== --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    @if ($supplier->logo)
                                        <img src="{{ asset('uploads/suppliers/' . $supplier->logo) }}"
                                            alt="{{ $supplier->name }}"
                                            style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd;" />
                                    @else
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
                                            $colorIndex = $supplier->id % count($colors);
                                        @endphp
                                        <div
                                            style="width: 80px; height: 80px; border-radius: 50%; background: {{ $colors[$colorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 28px; text-transform: uppercase; margin: 0 auto;">
                                            {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <h4 class="mb-1 text-primary fw-bold">{{ $supplier->name }}</h4>
                                    <p class="mb-1"><i class="bx bx-envelope me-1 text-muted"></i>
                                        {{ $supplier->email }}</p>
                                    <p class="mb-1"><i class="bx bx-phone me-1 text-muted"></i>
                                        {{ $supplier->phone }}</p>
                                    <p class="mb-0"><i class="bx bx-map me-1 text-muted"></i>
                                        {{ $supplier->address }}</p>
                                </div>
                                <div class="col-md-6">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="card bg-light-primary border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-primary mb-1">Total Amount</h6>
                                                    <h4 class="mb-0 fw-bold text-primary">
                                                        ৳{{ number_format($totalAmount, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-success border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-success mb-1">Total Payment</h6>
                                                    <h4 class="mb-0 fw-bold text-success">
                                                        ৳{{ number_format($totalPayment, 2) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-danger border-0 mb-0">
                                                <div class="card-body py-3">
                                                    <h6 class="text-danger mb-1">Total Due</h6>
                                                    <h4 class="mb-0 fw-bold text-danger">৳{{ number_format($totalDue, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============== Add/Edit Ledger Entry Form ============== --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-plus-medical me-1"></i>
                                {{ isset($ledger) ? 'Edit Ledger Entry' : 'Add New Ledger Entry' }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @if (isset($ledger))
                                <form action="{{ route('supplier.ledger.update', $ledger->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                @else
                                    <form action="{{ route('supplier.ledger.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                            @endif
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label for="invoice_id" class="form-label fw-semibold">Invoice No.</label>
                                    @error('invoice_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="text" name="invoice_id" class="form-control" placeholder="INV-001"
                                        value="{{ isset($ledger) ? $ledger->invoice_id : old('invoice_id') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="product_id" class="form-label fw-semibold">Product</label>
                                    @error('product_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <select name="product_id" class="form-select">
                                        <option value="">-- Select Product --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ isset($ledger) && $ledger->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $product->productName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label for="quantity" class="form-label fw-semibold">Quantity</label>
                                    @error('quantity')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="number" name="quantity" id="ledger_quantity" class="form-control"
                                        placeholder="0" min="0"
                                        value="{{ isset($ledger) ? $ledger->quantity : old('quantity') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="price" class="form-label fw-semibold">Unit Price</label>
                                    @error('price')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="number" name="price" id="ledger_price" class="form-control"
                                        placeholder="0.00" min="0" step="0.01"
                                        value="{{ isset($ledger) ? $ledger->price : old('price') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="total" class="form-label fw-semibold">Total</label>
                                    @error('total')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="number" name="total" id="ledger_total" class="form-control"
                                        placeholder="0.00" min="0" step="0.01"
                                        value="{{ isset($ledger) ? $ledger->total : old('total') }}" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label for="payment" class="form-label fw-semibold">Payment</label>
                                    @error('payment')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="number" name="payment" id="ledger_payment" class="form-control"
                                        placeholder="0.00" min="0" step="0.01"
                                        value="{{ isset($ledger) ? $ledger->payment : old('payment') }}">
                                </div>
                                <div class="col-md-1">
                                    <label for="due" class="form-label fw-semibold">Due</label>
                                    @error('due')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <input type="number" name="due" id="ledger_due" class="form-control"
                                        placeholder="0.00" min="0" step="0.01"
                                        value="{{ isset($ledger) ? $ledger->due : old('due') }}" readonly>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="bx {{ isset($ledger) ? 'bx-check' : 'bx-plus' }}"></i>
                                        {{ isset($ledger) ? 'Update' : 'Add' }}
                                    </button>
                                </div>
                            </div>
                            </form>
                            @if (isset($ledger))
                                <div class="mt-2">
                                    <a href="{{ route('supplier.ledger', $supplier->id) }}"
                                        class="btn btn-sm btn-secondary">
                                        <i class="bx bx-x"></i> Cancel Edit
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- সাকসেস মেসেজ দেখানো হবে --}}
            @if (session('success'))
                <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
                    <div class="d-flex align-items-center">
                        <div class="font-35 text-white"><i class="bx bxs-check-circle"></i></div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-white">{{ session('success') }}</h6>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- ============== Ledger Table ============== --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-list-ul me-1"></i> Ledger History -
                                <span class="text-primary">{{ $supplier->name }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped table-bordered display">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>Invoice No.</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Due</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ledgers as $key => $entry)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $entry->created_at->format('d M, Y') }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-primary">{{ $entry->invoice_id ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    @if ($entry->product)
                                                        {{ $entry->product->productName }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>{{ $entry->quantity ?? 0 }}</td>
                                                <td>৳{{ number_format($entry->price, 2) }}</td>
                                                <td><strong>৳{{ number_format($entry->total, 2) }}</strong></td>
                                                <td class="text-success fw-bold">৳{{ number_format($entry->payment, 2) }}
                                                </td>
                                                <td class="text-danger fw-bold">৳{{ number_format($entry->due, 2) }}</td>
                                                <td>
                                                    <a href="{{ route('supplier.ledger.edit', $entry->id) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    <a href="{{ route('supplier.ledger.destroy', $entry->id) }}"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this ledger entry?')">
                                                        <i class="bx bx-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                                            <td class="fw-bold">৳{{ number_format($totalAmount, 2) }}</td>
                                            <td class="fw-bold text-success">৳{{ number_format($totalPayment, 2) }}</td>
                                            <td class="fw-bold text-danger">৳{{ number_format($totalDue, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ============== JavaScript for auto-calculation ============== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('ledger_quantity');
            const priceInput = document.getElementById('ledger_price');
            const totalInput = document.getElementById('ledger_total');
            const paymentInput = document.getElementById('ledger_payment');
            const dueInput = document.getElementById('ledger_due');

            // quantity × price = total হিসাব করবে
            function calculateTotal() {
                const qty = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = qty * price;
                totalInput.value = total.toFixed(2);
                calculateDue();
            }

            // total - payment = due হিসাব করবে
            function calculateDue() {
                const total = parseFloat(totalInput.value) || 0;
                const payment = parseFloat(paymentInput.value) || 0;
                const due = total - payment;
                dueInput.value = due.toFixed(2);
            }

            quantityInput.addEventListener('input', calculateTotal);
            priceInput.addEventListener('input', calculateTotal);
            paymentInput.addEventListener('input', calculateDue);
        });
    </script>
@endsection
