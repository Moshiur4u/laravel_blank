@extends('dashboard.dashboard')
@section('title', 'Create Sale')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Sales</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i
                                        class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Sale</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @can('sales.create')
                <form id="saleForm" action="{{ route('sales.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="save_customer" id="save_customer" value="{{ old('save_customer', 'no') }}">
                    <div class="row g-3">
                        <div class="col-12 col-lg-7 col-xl-8">


                            <div class="mt-4 border-0 shadow-sm card radius-10">
                                <div class="py-3 bg-transparent card-header border-bottom">
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-user text-primary me-2"></i>Customer</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="customer_phone" class="form-label fw-semibold">Phone Number <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="tel" name="customer_phone" id="customer_phone"
                                                    class="form-control" value="{{ old('customer_phone') }}" required>
                                                <button type="button" id="lookupCustomer"
                                                    class="btn btn-outline-primary">Find</button>
                                            </div>
                                            <div id="customerLookupMessage" class="form-text">Enter a phone number to look up
                                                an existing customer.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="customerDisplayName" class="form-label fw-semibold">Customer
                                                Name</label>
                                            <input type="text" id="customerDisplayName" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <div id="customerLedgerPanel" class="mt-3 d-none">
                                        <div class="mb-2 d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0 fw-bold">Customer Ledger</h6>
                                            <span class="badge bg-light-danger text-danger">Outstanding: <span
                                                    id="customerDue">৳0.00</span></span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table mb-2 table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Type / Item</th>
                                                        <th>Sale</th>
                                                        <th>Paid</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="customerLedgerRows"></tbody>
                                            </table>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_previous_due"
                                                id="include_previous_due" value="1"
                                                {{ old('include_previous_due') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="include_previous_due">Add outstanding due to
                                                this invoice</label>
                                        </div>
                                    </div>

                                    <div id="newCustomerDetails" class="mt-2 row g-3 d-none">
                                        <div class="col-12">
                                            <div class="py-2 mb-0 alert alert-info">This phone number is not registered. You
                                                can print the invoice without saving a customer record, or choose Yes at
                                                checkout and enter their details.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="customer_name" class="form-label">Name (needed only if saving)</label>
                                            <input type="text" name="customer_name" id="customer_name" class="form-control"
                                                value="{{ old('customer_name') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="customer_email" class="form-label">Email (optional)</label>
                                            <input type="email" name="customer_email" id="customer_email" class="form-control"
                                                value="{{ old('customer_email') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="customer_address" class="form-label">Address (optional)</label>
                                            <input type="text" name="customer_address" id="customer_address"
                                                class="form-control" value="{{ old('customer_address') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border-0 shadow-sm card radius-10">
                                <div class="py-3 bg-transparent card-header border-bottom">
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-cart text-primary me-2"></i>Sale Items
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="lowStockAlert" class="alert alert-warning d-none" role="alert"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="min-width: 230px;">Product</th>
                                                    <th style="width: 110px;">Stock Left</th>
                                                    <th style="width: 110px;">Quantity</th>
                                                    <th style="width: 150px;">Unit Price</th>
                                                    <th style="width: 140px;">Line Total</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="saleItems">
                                                @foreach (old('items', [['product_id' => '', 'quantity' => 1]]) as $index => $oldItem)
                                                    @php
                                                        $oldProduct = $products->firstWhere(
                                                            'id',
                                                            $oldItem['product_id'] ?? null,
                                                        );
                                                    @endphp
                                                    <tr class="sale-item-row">
                                                        <td>
                                                            <input type="search" class="form-control product-search"
                                                                list="saleProductOptions"
                                                                placeholder="Search product name or code"
                                                                aria-label="Search product by name or code" autocomplete="off"
                                                                value="{{ $oldProduct ? $oldProduct->productName . ' — PRD-' . str_pad($oldProduct->id, 5, '0', STR_PAD_LEFT) : '' }}"
                                                                required>
                                                            <select name="items[{{ $index }}][product_id]"
                                                                class="product-select d-none" tabindex="-1"
                                                                aria-hidden="true">
                                                                <option value="">Select product</option>
                                                                @foreach ($products as $product)
                                                                    @php $productSku = 'PRD-' . str_pad($product->id, 5, '0', STR_PAD_LEFT); @endphp
                                                                    <option value="{{ $product->id }}"
                                                                        data-price="{{ $product->price }}"
                                                                        data-stock="{{ $product->unit }}"
                                                                        data-name="{{ strtolower($product->productName) }}"
                                                                        data-code="{{ strtolower($productSku) }}"
                                                                        data-label="{{ strtolower($product->productName . ' — ' . $productSku) }}"
                                                                        {{ (string) ($oldItem['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                                                        {{ $product->productName }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="stock-cell text-muted">—</td>
                                                        <td><input type="number" name="items[{{ $index }}][quantity]"
                                                                class="form-control quantity-input" min="1"
                                                                value="{{ $oldItem['quantity'] ?? 1 }}" required></td>
                                                        <td>
                                                            <input type="number"
                                                                name="items[{{ $index }}][unit_price]"
                                                                class="form-control unit-price-input" min="0"
                                                                max="9999999999999.99" step="0.01"
                                                                value="{{ old("items.$index.unit_price", $oldProduct?->price ?? 0) }}"
                                                                required>
                                                        </td>
                                                        <td class="line-total-cell fw-semibold">৳0.00</td>
                                                        <td><button type="button"
                                                                class="btn btn-sm btn-outline-danger remove-item"
                                                                title="Remove item"><i class="bx bx-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <datalist id="saleProductOptions">
                                        @foreach ($products as $product)
                                            <option
                                                value="{{ $product->productName }} — PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}">
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <button type="button" id="addItem" class="btn btn-outline-primary btn-sm">
                                        <i class="bx bx-plus me-1"></i>Add Product
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-5 col-xl-4">
                            <div class="border-0 shadow-sm card radius-10">
                                <div class="py-3 bg-transparent card-header border-bottom">
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-receipt text-primary me-2"></i>Invoice
                                        & Payment</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label for="discount_type" class="form-label fw-semibold text-nowrap">Discount
                                                Type</label>
                                            <select name="discount_type" id="discount_type" class="form-select">
                                                <option value="amount"
                                                    {{ old('discount_type', 'amount') === 'amount' ? 'selected' : '' }}>Amount
                                                </option>
                                                <option value="percentage"
                                                    {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percent
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label for="discount_value" id="discount_label"
                                                class="form-label fw-semibold text-nowrap">Discount (৳)</label>
                                            <div class="input-group">
                                                <input type="number" name="discount_value" id="discount_value"
                                                    class="form-control" min="0" step="0.01"
                                                    value="{{ old('discount_value', old('discount_amount', 0)) }}"
                                                    placeholder="0.00">
                                                <span class="input-group-text px-2" id="discount_symbol">৳</span>
                                            </div>
                                            <input type="hidden" name="discount_amount" id="discount_amount"
                                                value="{{ old('discount_amount', 0) }}">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            {{-- VAT-এর হার শতাংশে নেওয়া হয়; হিসাব করা টাকার অঙ্ক hidden ইনপুটে পাঠানো হয়। --}}
                                            <label for="vat_percentage" class="form-label fw-semibold text-nowrap">VAT
                                                (%)</label>
                                            <div class="input-group">
                                                <input type="number" name="vat_percentage" id="vat_percentage"
                                                    class="form-control" min="0" max="100" step="0.01"
                                                    value="{{ old('vat_percentage', 5) }}">
                                                <span class="input-group-text px-2">%</span>
                                            </div>
                                            <input type="hidden" name="vat_amount" id="vat_amount"
                                                value="{{ old('vat_amount', 0) }}">
                                        </div>
                                        <div class="col-6">
                                            <label for="ait_percentage" class="form-label fw-semibold text-nowrap">AIT
                                                (%)</label>
                                            <div class="input-group">
                                                <input type="number" name="ait_percentage" id="ait_percentage"
                                                    class="form-control" min="0" max="100" step="0.01"
                                                    value="{{ old('ait_percentage', 0) }}">
                                                <span class="input-group-text px-2">%</span>
                                            </div>
                                            <input type="hidden" name="ait_amount" id="ait_amount"
                                                value="{{ old('ait_amount', 0) }}">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label for="amount_paid"
                                                class="form-label fw-semibold text-nowrap">Amount_Received</label>
                                            <div class="input-group">
                                                <input type="number" name="amount_paid" id="amount_paid"
                                                    class="form-control" min="0" step="0.01"
                                                    value="{{ old('amount_paid', 0) }}">
                                                <span class="input-group-text px-2">৳</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                                            <select name="payment_method" id="payment_method" class="form-select">
                                                <option value="">Select method</option>
                                                @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'nagad' => 'Nagad', 'bkash' => 'bKash', 'rocket' => 'Rocket', 'upay' => 'Upay'] as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('payment_method') === $value ? 'selected' : '' }}>
                                                        {{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="terms_conditions" class="form-label fw-semibold">Terms & Conditions</label>
                                        <textarea name="terms_conditions" id="terms_conditions" rows="5"
                                            class="form-control @error('terms_conditions') is-invalid @enderror"
                                            placeholder="Input terms and conditions to be displayed on this invoice...">{{ old('terms_conditions', $defaultTerms) }}</textarea>
                                        @error('terms_conditions')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <hr>
                                    <div class="mb-2 d-flex justify-content-between"><span>Subtotal</span><strong
                                            id="subtotalText">৳0.00</strong></div>
                                    {{-- সাবটোটালের পর ডিসকাউন্ট ও শতাংশ থেকে হিসাব করা VAT দেখানো হয়। --}}
                                    <div class="mb-2 d-flex justify-content-between"><span
                                            id="discountSummaryLabel">Discount</span><strong id="discountText">৳0.00</strong>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between"><span>VAT (<span
                                                id="vatPercentageText">5%</span>)</span><strong id="vatText">৳0.00</strong>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between"><span>AIT (<span
                                                id="aitPercentageText">0%</span>)</span><strong id="aitText">৳0.00</strong>
                                    </div>
                                    <div class="mb-2 d-flex justify-content-between"><span>Previous Due</span><strong
                                            id="previousDueText">৳0.00</strong></div>
                                    <div class="mb-2 d-flex justify-content-between"><span>Invoice Total</span><strong
                                            id="invoiceTotalText">৳0.00</strong></div>
                                    <div class="mb-3 d-flex justify-content-between text-danger"><span>Balance
                                            Due</span><strong id="balanceDueText">৳0.00</strong></div>
                                    <button type="submit" id="checkoutButton" class="btn btn-primary w-100">
                                        <i class="bx bx-printer me-1"></i>Checkout & Print Invoice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endcan

            @can('sales.view')
                <div class="mt-4 border-0 shadow-sm card radius-10">
                    <div class="py-3 bg-transparent card-header border-bottom">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-history text-primary me-2"></i>Recent Invoices</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table mb-0 align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Customer Phone</th>
                                    <th>Employee</th>
                                    <th>Total</th>
                                    <th>Due</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ $invoice->invoice_date->format('d M Y, h:i A') }}</td>
                                        <td>{{ $invoice->customer_phone }}</td>
                                        <td>{{ $invoice->employee->name ?? 'N/A' }}</td>
                                        <td>৳{{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>৳{{ number_format($invoice->due_amount, 2) }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('sales.invoice', $invoice->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="View Invoice"
                                                aria-label="View invoice {{ $invoice->invoice_no }}">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="{{ route('sales.delivery-challan', $invoice->id) }}"
                                                class="btn btn-sm btn-outline-success" title="Delivery Challan">
                                                <i class="bx bx-package me-1"></i>Delivery Challan
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-muted">No invoices have been created
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('saleForm');
            const itemsBody = document.getElementById('saleItems');
            const customerPhone = document.getElementById('customer_phone');
            const customerName = document.getElementById('customerDisplayName');
            const lookupMessage = document.getElementById('customerLookupMessage');
            const ledgerPanel = document.getElementById('customerLedgerPanel');
            const newCustomerDetails = document.getElementById('newCustomerDetails');
            const saveCustomer = document.getElementById('save_customer');
            let customerFound = false;
            let calculatedInvoiceTotal = 0;
            let lookupTimer;
            let lastLookedUpPhone = '';
            let rowIndex = Array.from(itemsBody.querySelectorAll('.product-select')).reduce((nextIndex, select) => {
                const match = select.name.match(/^items\[(\d+)\]/);
                return match ? Math.max(nextIndex, Number(match[1]) + 1) : nextIndex;
            }, 0);

            function money(amount) {
                return '৳' + Number(amount || 0).toFixed(2);
            }

            function updateTotals() {
                let subtotal = 0;
                const rows = Array.from(itemsBody.querySelectorAll('.sale-item-row'));
                const requestedByProduct = new Map();
                rows.forEach((row) => {
                    const select = row.querySelector('.product-select');
                    const option = select.options[select.selectedIndex];
                    const quantityInput = row.querySelector('.quantity-input');
                    const quantity = Number(quantityInput.value || 0);
                    if (option && option.value) {
                        requestedByProduct.set(option.value, (requestedByProduct.get(option.value) || 0) +
                            quantity);
                    }
                });

                const lowStockItems = [];
                rows.forEach((row) => {
                    const select = row.querySelector('.product-select');
                    const option = select.options[select.selectedIndex];
                    const quantityInput = row.querySelector('.quantity-input');
                    const quantity = Number(quantityInput.value || 0);
                    const selected = option && option.value;
                    const unitPriceInput = row.querySelector('.unit-price-input');
                    const price = selected ? Number(unitPriceInput.value || 0) : 0;
                    const stock = selected ? Number(option.dataset.stock || 0) : 0;
                    const totalRequested = selected ? requestedByProduct.get(option.value) : 0;
                    const requestedByOtherRows = selected ? totalRequested - quantity : 0;
                    const quantityAvailableForThisRow = Math.max(stock - requestedByOtherRows, 0);
                    const stockRemaining = Math.max(stock - totalRequested, 0);

                    row.querySelector('.stock-cell').textContent = selected ?
                        `${stockRemaining} / ${stock}` :
                        '—';
                    const lineTotal = price * quantity;
                    row.querySelector('.line-total-cell').textContent = money(lineTotal);
                    if (selected) {
                        quantityInput.max = String(quantityAvailableForThisRow);
                        const exceedsStock = quantity > quantityAvailableForThisRow;
                        quantityInput.classList.toggle('is-invalid', exceedsStock);
                        if (exceedsStock) {
                            lowStockItems.push(
                                `${option.textContent.trim()}: only ${quantityAvailableForThisRow} remaining`
                            );
                        }
                    } else {
                        quantityInput.removeAttribute('max');
                        quantityInput.classList.remove('is-invalid');
                    }
                    subtotal += lineTotal;
                });

                const stockAlert = document.getElementById('lowStockAlert');
                stockAlert.classList.toggle('d-none', lowStockItems.length === 0);
                stockAlert.classList.toggle('alert-danger', lowStockItems.some((item) => item.includes('only ')));
                stockAlert.classList.toggle('alert-warning', !lowStockItems.some((item) => item.includes('only ')));
                if (lowStockItems.length > 0) {
                    stockAlert.textContent = lowStockItems.join(' • ');
                }

                // ডিসকাউন্ট টাইপ অনুযায়ী হিসাব: Direct Amount হলে সরাসরি টাকা, Percentage হলে সাবটোটালের শতকরা হার
                const discountType = document.getElementById('discount_type').value;
                const discountVal = Number(document.getElementById('discount_value').value || 0);
                const discountLabel = document.getElementById('discount_label');
                const discountSymbol = document.getElementById('discount_symbol');
                const discountSummaryLabel = document.getElementById('discountSummaryLabel');

                let discount = 0;
                if (discountType === 'percentage') {
                    if (discountLabel) discountLabel.textContent = 'Discount Percentage (%)';
                    if (discountSymbol) discountSymbol.textContent = '%';
                    document.getElementById('discount_value').placeholder = '0%';
                    discount = Math.round((subtotal * discountVal / 100 + Number.EPSILON) * 100) / 100;
                    if (discountSummaryLabel) discountSummaryLabel.textContent = `Discount (${discountVal}%)`;
                } else {
                    if (discountLabel) discountLabel.textContent = 'Discount Amount (৳)';
                    if (discountSymbol) discountSymbol.textContent = '৳';
                    document.getElementById('discount_value').placeholder = '0.00';
                    discount = Math.round((discountVal + Number.EPSILON) * 100) / 100;
                    if (discountSummaryLabel) discountSummaryLabel.textContent = 'Discount';
                }

                // ডিসকাউন্ট সাবটোটালের চেয়ে বেশি হতে পারবে না
                discount = Math.min(discount, subtotal);
                document.getElementById('discount_amount').value = discount.toFixed(2);

                // ডিসকাউন্ট বাদ দেওয়ার পরের সাবটোটালের ওপর VAT শতাংশ হিসাব করা হয়।
                const vatPercentage = Number(document.getElementById('vat_percentage').value || 0);
                const vatBase = Math.max(0, subtotal - discount);
                const vat = Math.round((vatBase * vatPercentage / 100 + Number.EPSILON) * 100) / 100;
                document.getElementById('vat_amount').value = vat.toFixed(2);
                const aitPercentage = Number(document.getElementById('ait_percentage').value || 0);
                const ait = Math.round((vatBase * aitPercentage / 100 + Number.EPSILON) * 100) / 100;
                document.getElementById('ait_amount').value = ait.toFixed(2);
                const previousDue = document.getElementById('include_previous_due').checked ?
                    Number(document.getElementById('customerDue').dataset.value || 0) :
                    0;
                const total = vatBase + vat + ait + previousDue;
                const paid = Number(document.getElementById('amount_paid').value || 0);
                calculatedInvoiceTotal = Math.round((total + Number.EPSILON) * 100) / 100;
                document.getElementById('subtotalText').textContent = money(subtotal);
                document.getElementById('discountText').textContent = money(discount);
                document.getElementById('vatPercentageText').textContent = `${vatPercentage}%`;
                document.getElementById('vatText').textContent = money(vat);
                document.getElementById('aitPercentageText').textContent = `${aitPercentage}%`;
                document.getElementById('aitText').textContent = money(ait);
                document.getElementById('previousDueText').textContent = money(previousDue);
                document.getElementById('invoiceTotalText').textContent = money(total);
                document.getElementById('balanceDueText').textContent = money(Math.max(total - paid, 0));
            }

            function syncProductSearch(row) {
                const searchInput = row.querySelector('.product-search');
                const select = row.querySelector('.product-select');
                const search = searchInput.value.trim().toLowerCase();
                const previousProductId = select.value;
                const option = Array.from(select.options).find((item) => item.value && (
                    item.dataset.label === search ||
                    item.dataset.name === search ||
                    item.dataset.code === search
                ));
                select.value = option ? option.value : '';
                if (option && option.value !== previousProductId) {
                    row.querySelector('.unit-price-input').value = Number(option.dataset.price || 0).toFixed(2);
                } else if (!option) {
                    row.querySelector('.unit-price-input').value = '';
                }
                searchInput.setCustomValidity(option || !search ? '' :
                    'Choose a product from the suggested name and code.');
            }

            function addRow() {
                const first = itemsBody.querySelector('.sale-item-row');
                const row = first.cloneNode(true);
                row.querySelector('.product-search').value = '';
                row.querySelector('.product-select').name = `items[${rowIndex}][product_id]`;
                row.querySelector('.product-select').value = '';
                row.querySelector('.stock-cell').textContent = '—';
                row.querySelector('.line-total-cell').textContent = money(0);
                row.querySelector('.unit-price-input').name = `items[${rowIndex}][unit_price]`;
                row.querySelector('.unit-price-input').value = '0.00';
                row.querySelector('.quantity-input').name = `items[${rowIndex}][quantity]`;
                row.querySelector('.quantity-input').value = '1';
                row.querySelector('.quantity-input').classList.remove('is-invalid');
                itemsBody.appendChild(row);
                rowIndex++;
                updateTotals();
            }

            async function lookupCustomer() {
                const phone = customerPhone.value.trim();
                if (!phone) {
                    lookupMessage.textContent = 'Enter a phone number first.';
                    return false;
                }
                lookupMessage.textContent = 'Searching customer...';
                try {
                    const url = new URL(@json(route('sales.customer.lookup')), window.location.origin);
                    url.searchParams.set('phone', phone);
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!response.ok) {
                        throw new Error('Customer lookup failed. Please try again.');
                    }
                    const result = await response.json();
                    lastLookedUpPhone = phone;
                    customerFound = result.found;
                    ledgerPanel.classList.toggle('d-none', !customerFound);
                    newCustomerDetails.classList.toggle('d-none', customerFound);
                    document.getElementById('include_previous_due').checked = false;
                    const rows = document.getElementById('customerLedgerRows');
                    rows.replaceChildren();
                    if (customerFound) {
                        customerName.value = result.customer.name;
                        lookupMessage.textContent = 'Existing customer found. Name and ledger loaded.';
                        const dueLabel = document.getElementById('customerDue');
                        dueLabel.dataset.value = result.customer.due;
                        dueLabel.textContent = money(result.customer.due);
                        result.customer.ledger.forEach((entry) => {
                            const row = document.createElement('tr');
                            const values = [
                                entry.date || '',
                                entry.type === 'payment' ? 'Payment received' : (entry.item ||
                                    'Sale'),
                                money(entry.amount),
                                money(entry.payment),
                            ];
                            values.forEach((value) => {
                                const cell = document.createElement('td');
                                cell.textContent = value;
                                row.appendChild(cell);
                            });
                            rows.appendChild(row);
                        });
                        if (result.customer.ledger.length === 0) {
                            const row = document.createElement('tr');
                            const cell = document.createElement('td');
                            cell.colSpan = 4;
                            cell.textContent = 'No previous ledger entries.';
                            row.appendChild(cell);
                            rows.appendChild(row);
                        }
                    } else {
                        customerName.value = '';
                        document.getElementById('customerDue').dataset.value = '0';
                        lookupMessage.textContent = 'No saved customer found for this phone.';
                    }
                    updateTotals();
                    return true;
                } catch (error) {
                    lookupMessage.textContent = error.message;
                    return false;
                }
            }

            document.getElementById('lookupCustomer').addEventListener('click', lookupCustomer);
            customerPhone.addEventListener('change', lookupCustomer);
            customerPhone.addEventListener('input', function() {
                customerFound = false;
                ledgerPanel.classList.add('d-none');
                newCustomerDetails.classList.add('d-none');
                window.clearTimeout(lookupTimer);
                lookupTimer = window.setTimeout(lookupCustomer, 350);
            });
            customerPhone.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    lookupCustomer();
                }
            });
            document.getElementById('addItem').addEventListener('click', addRow);
            itemsBody.addEventListener('change', (event) => {
                if (event.target.matches('.product-search')) {
                    syncProductSearch(event.target.closest('.sale-item-row'));
                }
                updateTotals();
            });
            itemsBody.addEventListener('input', updateTotals);
            itemsBody.addEventListener('input', (event) => {
                if (event.target.matches('.product-search')) {
                    syncProductSearch(event.target.closest('.sale-item-row'));
                    updateTotals();
                }
            });
            itemsBody.addEventListener('click', (event) => {
                const button = event.target.closest('.remove-item');
                if (!button) return;
                if (itemsBody.querySelectorAll('.sale-item-row').length === 1) {
                    const row = button.closest('.sale-item-row');
                    row.querySelector('.product-select').value = '';
                    row.querySelector('.product-search').value = '';
                    row.querySelector('.unit-price-input').value = '';
                    row.querySelector('.quantity-input').value = '1';
                } else {
                    button.closest('.sale-item-row').remove();
                }
                updateTotals();
            });
            ['discount_type', 'discount_value', 'vat_percentage', 'ait_percentage', 'amount_paid',
                'include_previous_due'
            ].forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateTotals);
                    el.addEventListener('change', updateTotals);
                }
            });

            form.addEventListener('submit', async function(event) {
                if (saveCustomer.dataset.confirmed === 'yes') return;
                event.preventDefault();
                updateTotals();
                const incompleteRow = Array.from(itemsBody.querySelectorAll('.sale-item-row')).find((
                    row) => !row.querySelector('.product-select').value);
                if (incompleteRow) {
                    const searchInput = incompleteRow.querySelector('.product-search');
                    searchInput.setCustomValidity('Choose a product from the suggested name and code.');
                    searchInput.reportValidity();
                    searchInput.focus();
                    return;
                }
                const invalidQuantity = itemsBody.querySelector('.quantity-input.is-invalid');
                if (invalidQuantity) {
                    invalidQuantity.focus();
                    invalidQuantity.reportValidity();
                    return;
                }
                window.clearTimeout(lookupTimer);
                if (lastLookedUpPhone !== customerPhone.value.trim()) {
                    const lookupSucceeded = await lookupCustomer();
                    if (!lookupSucceeded) return;
                }
                if (customerFound) {
                    saveCustomer.value = 'no';
                    saveCustomer.dataset.confirmed = 'yes';
                    form.requestSubmit();
                    return;
                }

                const customerChoice = await window.Swal.fire({
                    title: 'Customer not found',
                    text: 'Save this customer’s details, or continue as a walk-in customer? Walk-in sales require full payment.',
                    icon: 'question',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, save customer',
                    denyButtonText: 'No, walk-in',
                    cancelButtonText: 'Cancel sale',
                    reverseButtons: true,
                });
                if (customerChoice.isConfirmed) {
                    const name = document.getElementById('customer_name').value.trim();
                    if (!name) {
                        await window.Swal.fire({
                            title: 'Customer name required',
                            text: 'Enter the customer name before saving their details.',
                            icon: 'warning',
                        });
                        document.getElementById('customer_name').focus();
                        return;
                    }
                    saveCustomer.value = 'yes';
                } else if (customerChoice.isDenied) {
                    saveCustomer.value = 'no';
                    document.getElementById('amount_paid').value = calculatedInvoiceTotal.toFixed(2);
                    updateTotals();
                } else {
                    return;
                }

                if (Number(document.getElementById('amount_paid').value || 0) > 0 &&
                    !document.getElementById('payment_method').value) {
                    await window.Swal.fire({
                        title: 'Payment method required',
                        text: 'Select how the payment was received before checkout.',
                        icon: 'warning',
                    });
                    document.getElementById('payment_method').focus();
                    return;
                }

                saveCustomer.dataset.confirmed = 'yes';
                form.requestSubmit();
            });

            @if ($errors->any())
                window.Swal.fire({
                    title: 'Unable to create sale',
                    text: @json($errors->all()).join('\n'),
                    icon: 'error',
                });
            @endif

            updateTotals();
            if (customerPhone.value) lookupCustomer();
        });
    </script>
@endsection
