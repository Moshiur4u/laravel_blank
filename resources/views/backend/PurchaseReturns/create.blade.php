@extends('dashboard.dashboard')

@section('title', 'Purchase Return')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="breadcrumb-title pe-3">Product</div>
                    <h4 class="mt-2 mb-0">Purchase Return</h4>
                </div>
                @canany(['purchase.return.report', 'reports.all_access'])
                    <a href="{{ route('purchase-returns.report') }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul me-1"></i>Purchase Return Report
                    </a>
                @endcan
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <div class="border-0 shadow-sm card">
                <div class="card-header">
                    <h5 class="mb-0">Find Supplier or Purchase Invoice</h5>
                    <p class="mb-0 mt-1 text-muted">Search by supplier name, phone number, or purchase invoice number.</p>
                </div>
                <div class="card-body">
                    <label for="purchase-return-search" class="form-label">Supplier / Invoice</label>
                    <input type="search" id="purchase-return-search" class="form-control"
                        placeholder="Type at least 2 characters..." autocomplete="off">
                    <div id="purchase-return-results" class="list-group mt-2" role="listbox"></div>
                    <div id="purchase-return-error" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>
            </div>

            <div id="supplier-purchases" class="mt-4 border-0 shadow-sm card d-none">
                <div class="card-header">
                    <h5 class="mb-0">Purchase Products</h5>
                    <div id="selected-supplier" class="small text-muted"></div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Purchased</th>
                                <th class="text-center">Returned</th>
                                <th class="text-center">Remaining</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="supplier-purchase-rows"></tbody>
                    </table>
                </div>
            </div>

            <div id="purchase-return-form-card" class="mt-4 border-0 shadow-sm card d-none">
                <div class="card-header">
                    <h5 class="mb-0">Record One Purchase Product Return</h5>
                    <p class="mb-0 mt-1 text-muted">
                        Select one purchase product to return. Return value is applied to supplier due first; any amount
                        beyond due is recorded as a cash refund.
                    </p>
                </div>
                <div class="card-body">
                    <form id="purchase-return-form" action="{{ route('purchase-returns.store') }}" method="POST" class="row g-3">
                        @csrf
                        <input type="hidden" name="supplierledger_id" id="supplierledger_id">
                        <div class="col-12">
                            <div id="purchase-return-product-label" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase-return-quantity" class="form-label">Quantity to return</label>
                            <input type="number" name="quantity" id="purchase-return-quantity" class="form-control"
                                min="0.001" step="0.001" value="{{ old('quantity') }}" required>
                            <div id="purchase-return-quantity-help" class="form-text"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase-return-date" class="form-label">Return date</label>
                            <input type="date" name="return_date" id="purchase-return-date" class="form-control"
                                value="{{ old('return_date', today()->toDateString()) }}"
                                max="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase-return-reason" class="form-label">Reason (optional)</label>
                            <input type="text" name="reason" id="purchase-return-reason" class="form-control"
                                value="{{ old('reason') }}" maxlength="2000">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger">
                                <i class="bx bx-undo me-1"></i>Confirm Purchase Product Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('purchase-return-search');
            const searchResults = document.getElementById('purchase-return-results');
            const historyCard = document.getElementById('supplier-purchases');
            const purchaseRows = document.getElementById('supplier-purchase-rows');
            const returnCard = document.getElementById('purchase-return-form-card');
            const returnForm = document.getElementById('purchase-return-form');
            const errorContainer = document.getElementById('purchase-return-error');
            const searchUrl = @json(route('purchase-returns.search'));
            const historyUrl = @json(route('purchase-returns.supplier-history'));
            const purchaseUrl = @json(route('purchase-returns.purchase', ['id' => '__PURCHASE_ID__']));
            let searchTimer;

            function showError(message) {
                errorContainer.textContent = message;
                errorContainer.classList.remove('d-none');
            }

            function clearError() {
                errorContainer.textContent = '';
                errorContainer.classList.add('d-none');
            }

            async function getJson(url) {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    let message = `Could not load purchase information (HTTP ${response.status}).`;
                    try {
                        const body = await response.json();
                        const validationMessage = body.errors
                            ? Object.values(body.errors).flat().join(' ')
                            : '';
                        message = validationMessage || body.message || message;
                    } catch (error) {
                        // Keep the HTTP status message when the server does not return JSON.
                    }
                    throw new Error(message);
                }
                return response.json();
            }

            function addSearchResult(title, detail, action, values) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.dataset.action = action;
                Object.entries(values).forEach(([key, value]) => {
                    button.dataset[key] = value ?? '';
                });
                const heading = document.createElement('div');
                heading.className = 'fw-semibold';
                heading.textContent = title;
                const subtitle = document.createElement('small');
                subtitle.className = 'text-muted';
                subtitle.textContent = detail;
                button.append(heading, subtitle);
                searchResults.append(button);
            }

            function openReturnForm(purchase, requestedQuantity = null) {
                const maximum = Math.min(purchase.remaining_quantity, purchase.stock_quantity);
                if (!purchase.returnable || maximum <= 0) {
                    showError('This purchase line has no remaining quantity in stock to return.');
                    return;
                }

                document.getElementById('supplierledger_id').value = purchase.id;
                document.getElementById('purchase-return-product-label').textContent =
                    `${purchase.product_name} — Invoice ${purchase.invoice_no}`;
                const quantityInput = document.getElementById('purchase-return-quantity');
                quantityInput.max = maximum;
                quantityInput.value = requestedQuantity !== null && Number(requestedQuantity) <= maximum
                    ? requestedQuantity
                    : Math.min(1, maximum);
                document.getElementById('purchase-return-quantity-help').textContent =
                    `Maximum return quantity: ${maximum} (stock available: ${purchase.stock_quantity})`;
                returnCard.classList.remove('d-none');
                returnCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }

            async function loadHistory(supplierId, supplierName, invoiceNo = '') {
                clearError();
                const url = new URL(historyUrl, window.location.origin);
                url.searchParams.set('supplier_id', supplierId);
                if (invoiceNo) {
                    url.searchParams.set('invoice_id', invoiceNo);
                }
                const data = await getJson(url);
                historyCard.classList.remove('d-none');
                returnCard.classList.add('d-none');
                document.getElementById('selected-supplier').textContent =
                    `${supplierName || data.supplier_name}${invoiceNo ? ` — Invoice ${invoiceNo}` : ''}`;
                purchaseRows.replaceChildren();

                if (!data.purchases.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 8;
                    cell.className = 'py-4 text-center text-muted';
                    cell.textContent = 'No purchase records found for this supplier.';
                    row.append(cell);
                    purchaseRows.append(row);
                    return;
                }

                data.purchases.forEach(function(purchase) {
                    const row = document.createElement('tr');
                    [
                        purchase.invoice_no,
                        purchase.purchase_date || '—',
                        purchase.product_name,
                    ].forEach(function(value) {
                        const cell = document.createElement('td');
                        cell.textContent = value;
                        row.append(cell);
                    });
                    const priceCell = document.createElement('td');
                    priceCell.className = 'text-end';
                    priceCell.textContent = `৳${Number(purchase.unit_price).toFixed(2)}`;
                    row.append(priceCell);
                    [purchase.purchased_quantity, purchase.returned_quantity, purchase.remaining_quantity]
                        .forEach(function(value) {
                            const cell = document.createElement('td');
                            cell.className = 'text-center';
                            cell.textContent = Number(value).toFixed(3).replace(/\.?0+$/, '');
                            row.append(cell);
                        });
                    const actionCell = document.createElement('td');
                    if (purchase.returnable && purchase.stock_quantity > 0) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-outline-danger';
                        button.textContent = 'Select Product';
                        button.addEventListener('click', function() {
                            openReturnForm(purchase);
                        });
                        actionCell.append(button);
                    } else {
                        actionCell.textContent = purchase.remaining_quantity <= 0
                            ? 'Fully returned'
                            : 'No stock available';
                    }
                    row.append(actionCell);
                    purchaseRows.append(row);
                });
            }

            returnForm.addEventListener('submit', function(event) {
                if (!window.confirm('Record this purchase return and update supplier balance and product stock?')) {
                    event.preventDefault();
                }
            });

            searchInput.addEventListener('input', function() {
                window.clearTimeout(searchTimer);
                const term = searchInput.value.trim();
                searchResults.replaceChildren();
                historyCard.classList.add('d-none');
                returnCard.classList.add('d-none');
                clearError();
                if (term.length < 2) return;

                searchTimer = window.setTimeout(async function() {
                    try {
                        const url = new URL(searchUrl, window.location.origin);
                        url.searchParams.set('q', term);
                        const data = await getJson(url);
                        if (!data.suppliers.length && !data.purchases.length) {
                            const empty = document.createElement('div');
                            empty.className = 'list-group-item text-muted';
                            empty.textContent = 'No matching supplier or purchase invoice found.';
                            searchResults.append(empty);
                            return;
                        }
                        data.suppliers.forEach(function(supplier) {
                            addSearchResult(supplier.name, `${supplier.phone || 'No phone'} — View purchase history`,
                                'supplier', {
                                    supplierId: supplier.id,
                                    supplierName: supplier.name
                                });
                        });
                        data.purchases.forEach(function(purchase) {
                            addSearchResult(`${purchase.invoice_no} — ${purchase.product_name}`,
                                `${purchase.supplier_name} — View invoice purchase lines`, 'invoice', {
                                    supplierId: purchase.supplier_id,
                                    supplierName: purchase.supplier_name,
                                    invoiceNo: purchase.invoice_no
                                });
                        });
                    } catch (error) {
                        showError(error.message);
                    }
                }, 250);
            });

            searchResults.addEventListener('click', function(event) {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                searchResults.replaceChildren();
                loadHistory(button.dataset.supplierId, button.dataset.supplierName,
                    button.dataset.invoiceNo || '').catch(error => showError(error.message));
            });

            const initialSupplierId = @json($initialSupplierId);
            const oldPurchaseId = @json(old('supplierledger_id'));
            if (oldPurchaseId) {
                const url = purchaseUrl.replace('__PURCHASE_ID__', encodeURIComponent(oldPurchaseId));
                getJson(url).then(async function(purchase) {
                    await loadHistory(purchase.supplier_id, purchase.supplier_name);
                    openReturnForm(purchase, @json(old('quantity')));
                }).catch(error => showError(error.message));
            } else if (initialSupplierId) {
                loadHistory(initialSupplierId, '').catch(error => showError(error.message));
            }
        });
    </script>
@endsection
