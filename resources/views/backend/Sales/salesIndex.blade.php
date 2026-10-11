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
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Sale</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="saleForm" action="{{ route('sales.store') }}" method="POST">
                @csrf
                <input type="hidden" name="save_customer" id="save_customer" value="{{ old('save_customer', 'no') }}">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card radius-10 border-0 shadow-sm">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-cart text-primary me-2"></i>Sale Items</h5>
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
                                                <th style="width: 130px;">Unit Price</th>
                                                <th style="width: 140px;">Line Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="saleItems">
                                            @foreach (old('items', [['product_id' => '', 'quantity' => 1]]) as $index => $oldItem)
                                                @php
                                                    $oldProduct = $products->firstWhere('id', $oldItem['product_id'] ?? null);
                                                @endphp
                                                <tr class="sale-item-row">
                                                    <td>
                                                        <input type="search" class="form-control product-search"
                                                            list="saleProductOptions" placeholder="Search product name or code"
                                                            aria-label="Search product by name or code" autocomplete="off"
                                                            value="{{ $oldProduct ? $oldProduct->productName . ' — PRD-' . str_pad($oldProduct->id, 5, '0', STR_PAD_LEFT) : '' }}" required>
                                                        <select name="items[{{ $index }}][product_id]" class="product-select d-none" tabindex="-1" aria-hidden="true">
                                                            <option value="">Select product</option>
                                                            @foreach ($products as $product)
                                                                @php $productSku = 'PRD-' . str_pad($product->id, 5, '0', STR_PAD_LEFT); @endphp
                                                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->unit }}" data-name="{{ strtolower($product->productName) }}" data-code="{{ strtolower($productSku) }}" data-label="{{ strtolower($product->productName . ' — ' . $productSku) }}" {{ (string) ($oldItem['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                                                    {{ $product->productName }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="stock-cell text-muted">—</td>
                                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity-input" min="1" value="{{ $oldItem['quantity'] ?? 1 }}" required></td>
                                                    <td class="price-cell">৳0.00</td>
                                                    <td class="line-total-cell fw-semibold">৳0.00</td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove item"><i class="bx bx-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <datalist id="saleProductOptions">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->productName }} — PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}"></option>
                                    @endforeach
                                </datalist>
                                <button type="button" id="addItem" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-plus me-1"></i>Add Product
                                </button>
                            </div>
                        </div>

                        <div class="card radius-10 border-0 shadow-sm mt-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-user text-primary me-2"></i>Customer</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="customer_phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="tel" name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required>
                                            <button type="button" id="lookupCustomer" class="btn btn-outline-primary">Find</button>
                                        </div>
                                        <div id="customerLookupMessage" class="form-text">Enter a phone number to look up an existing customer.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customerDisplayName" class="form-label fw-semibold">Customer Name</label>
                                        <input type="text" id="customerDisplayName" class="form-control" readonly>
                                    </div>
                                </div>

                                <div id="customerLedgerPanel" class="mt-3 d-none">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0 fw-bold">Customer Ledger</h6>
                                        <span class="badge bg-light-danger text-danger">Outstanding: <span id="customerDue">৳0.00</span></span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-2">
                                            <thead class="table-light">
                                                <tr><th>Date</th><th>Type / Item</th><th>Sale</th><th>Paid</th></tr>
                                            </thead>
                                            <tbody id="customerLedgerRows"></tbody>
                                        </table>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="include_previous_due" id="include_previous_due" value="1" {{ old('include_previous_due') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="include_previous_due">Add outstanding due to this invoice</label>
                                    </div>
                                </div>

                                <div id="newCustomerDetails" class="row g-3 mt-2 d-none">
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 mb-0">This phone number is not registered. You can print the invoice without saving a customer record, or choose Yes at checkout and enter their details.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="customer_name" class="form-label">Name (needed only if saving)</label>
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="customer_email" class="form-label">Email (optional)</label>
                                        <input type="email" name="customer_email" id="customer_email" class="form-control" value="{{ old('customer_email') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="customer_address" class="form-label">Address (optional)</label>
                                        <input type="text" name="customer_address" id="customer_address" class="form-control" value="{{ old('customer_address') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card radius-10 border-0 shadow-sm">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-receipt text-primary me-2"></i>Invoice & Payment</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount</label>
                                    <input type="number" name="discount_amount" id="discount_amount" class="form-control" min="0" step="0.01" value="{{ old('discount_amount', 0) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="vat_amount" class="form-label">VAT Amount</label>
                                    <input type="number" name="vat_amount" id="vat_amount" class="form-control" min="0" step="0.01" value="{{ old('vat_amount', 0) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="amount_paid" class="form-label">Amount Received</label>
                                    <input type="number" name="amount_paid" id="amount_paid" class="form-control" min="0" step="0.01" value="{{ old('amount_paid', 0) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select name="payment_method" id="payment_method" class="form-select">
                                        <option value="">Select method</option>
                                        @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'nagad' => 'Nagad', 'bkash' => 'bKash', 'rocket' => 'Rocket', 'upay' => 'Upay'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="subtotalText">৳0.00</strong></div>
                                <div class="d-flex justify-content-between mb-2"><span>Previous Due</span><strong id="previousDueText">৳0.00</strong></div>
                                <div class="d-flex justify-content-between mb-2"><span>Invoice Total</span><strong id="invoiceTotalText">৳0.00</strong></div>
                                <div class="d-flex justify-content-between mb-3 text-danger"><span>Balance Due</span><strong id="balanceDueText">৳0.00</strong></div>
                                <button type="submit" id="checkoutButton" class="btn btn-primary w-100">
                                    <i class="bx bx-printer me-1"></i>Checkout & Print Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="card radius-10 border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-history text-primary me-2"></i>Recent Invoices</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Invoice</th><th>Date</th><th>Customer Phone</th><th>Employee</th><th>Total</th><th>Due</th><th></th></tr>
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
                                    <td><a href="{{ route('sales.invoice', $invoice->id) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-show"></i></a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No invoices have been created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('saleForm');
            const itemsBody = document.getElementById('saleItems');
            const customerPhone = document.getElementById('customer_phone');
            const customerName = document.getElementById('customerDisplayName');
            const lookupMessage = document.getElementById('customerLookupMessage');
            const ledgerPanel = document.getElementById('customerLedgerPanel');
            const newCustomerDetails = document.getElementById('newCustomerDetails');
            const saveCustomer = document.getElementById('save_customer');
            let customerFound = false;
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
                        requestedByProduct.set(option.value, (requestedByProduct.get(option.value) || 0) + quantity);
                    }
                });

                const lowStockItems = [];
                rows.forEach((row) => {
                    const select = row.querySelector('.product-select');
                    const option = select.options[select.selectedIndex];
                    const quantityInput = row.querySelector('.quantity-input');
                    const quantity = Number(quantityInput.value || 0);
                    const selected = option && option.value;
                    const price = selected ? Number(option.dataset.price || 0) : 0;
                    const stock = selected ? Number(option.dataset.stock || 0) : 0;
                    const totalRequested = selected ? requestedByProduct.get(option.value) : 0;
                    const requestedByOtherRows = selected ? totalRequested - quantity : 0;
                    const quantityAvailableForThisRow = Math.max(stock - requestedByOtherRows, 0);
                    const stockRemaining = Math.max(stock - totalRequested, 0);

                    row.querySelector('.stock-cell').textContent = selected
                        ? `${stockRemaining} / ${stock}`
                        : '—';
                    row.querySelector('.price-cell').textContent = money(price);
                    const lineTotal = price * quantity;
                    row.querySelector('.line-total-cell').textContent = money(lineTotal);
                    if (selected) {
                        quantityInput.max = String(quantityAvailableForThisRow);
                        const exceedsStock = quantity > quantityAvailableForThisRow;
                        quantityInput.classList.toggle('is-invalid', exceedsStock);
                        if (exceedsStock) {
                            lowStockItems.push(`${option.textContent.trim()}: only ${quantityAvailableForThisRow} remaining`);
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

                const discount = Number(document.getElementById('discount_amount').value || 0);
                const vat = Number(document.getElementById('vat_amount').value || 0);
                const previousDue = document.getElementById('include_previous_due').checked
                    ? Number(document.getElementById('customerDue').dataset.value || 0)
                    : 0;
                const total = Math.max(0, subtotal - discount) + vat + previousDue;
                const paid = Number(document.getElementById('amount_paid').value || 0);
                document.getElementById('subtotalText').textContent = money(subtotal);
                document.getElementById('previousDueText').textContent = money(previousDue);
                document.getElementById('invoiceTotalText').textContent = money(total);
                document.getElementById('balanceDueText').textContent = money(Math.max(total - paid, 0));
            }

            function syncProductSearch(row) {
                const searchInput = row.querySelector('.product-search');
                const select = row.querySelector('.product-select');
                const search = searchInput.value.trim().toLowerCase();
                const option = Array.from(select.options).find((item) => item.value && (
                    item.dataset.label === search ||
                    item.dataset.name === search ||
                    item.dataset.code === search
                ));
                select.value = option ? option.value : '';
                searchInput.setCustomValidity(option || !search ? '' : 'Choose a product from the suggested name and code.');
            }

            function addRow() {
                const first = itemsBody.querySelector('.sale-item-row');
                const row = first.cloneNode(true);
                row.querySelector('.product-search').value = '';
                row.querySelector('.product-select').name = `items[${rowIndex}][product_id]`;
                row.querySelector('.product-select').value = '';
                row.querySelector('.stock-cell').textContent = '—';
                row.querySelector('.price-cell').textContent = money(0);
                row.querySelector('.line-total-cell').textContent = money(0);
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
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
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
                                entry.type === 'payment' ? 'Payment received' : (entry.item || 'Sale'),
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
            customerPhone.addEventListener('input', function () {
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
                    row.querySelector('.quantity-input').value = '1';
                } else {
                    button.closest('.sale-item-row').remove();
                }
                updateTotals();
            });
            ['discount_amount', 'vat_amount', 'amount_paid', 'include_previous_due'].forEach((id) => {
                document.getElementById(id).addEventListener('input', updateTotals);
                document.getElementById(id).addEventListener('change', updateTotals);
            });

            form.addEventListener('submit', async function (event) {
                if (saveCustomer.dataset.confirmed === 'yes') return;
                event.preventDefault();
                updateTotals();
                const incompleteRow = Array.from(itemsBody.querySelectorAll('.sale-item-row')).find((row) => !row.querySelector('.product-select').value);
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

                const saveDetails = window.confirm('Save this customer’s details to the database before printing? Choose Cancel to print without saving customer details.');
                if (saveDetails) {
                    const name = document.getElementById('customer_name').value.trim();
                    if (!name) {
                        window.alert('Enter the customer name to save their details.');
                        document.getElementById('customer_name').focus();
                        return;
                    }
                    saveCustomer.value = 'yes';
                } else {
                    saveCustomer.value = 'no';
                }
                saveCustomer.dataset.confirmed = 'yes';
                form.requestSubmit();
            });

            updateTotals();
            if (customerPhone.value) lookupCustomer();
        });
    </script>
@endsection
