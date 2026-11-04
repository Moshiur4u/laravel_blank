@extends('dashboard.dashboard')

@section('title', 'Product Return')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="breadcrumb-title pe-3">Sales</div>
                    <h4 class="mt-2 mb-0">Product Return</h4>
                </div>
                @canany(['product.return.report', 'reports.all_access'])
                    <a href="{{ route('product-returns.report') }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul me-1"></i>Return Report
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
                    <h5 class="mb-0">Find Customer or Invoice</h5>
                    <p class="mb-0 mt-1 text-muted">Search by customer name, phone number, or invoice number.</p>
                </div>
                <div class="card-body">
                    <label for="return-search" class="form-label">Customer / Invoice</label>
                    <input type="search" id="return-search" class="form-control"
                        placeholder="Type at least 2 characters..." autocomplete="off">
                    <div id="search-results" class="list-group mt-2" role="listbox"></div>
                </div>
            </div>

            <div id="customer-history" class="mt-4 border-0 shadow-sm card d-none">
                <div class="card-header">
                    <h5 class="mb-0">Previous Purchased Invoices</h5>
                    <div id="selected-customer" class="small text-muted"></div>
                </div>
                <div id="history-invoices" class="card-body"></div>
            </div>

            <div id="invoice-products" class="mt-4 border-0 shadow-sm card d-none">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Products</h5>
                    <div id="invoice-customer" class="small text-muted"></div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Purchased</th>
                                <th class="text-center">Already Returned</th>
                                <th class="text-center">Returnable</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="invoice-product-rows"></tbody>
                    </table>
                </div>
            </div>

            <div id="return-form-card" class="mt-4 border-0 shadow-sm card d-none">
                <div class="card-header">
                    <h5 class="mb-0">Record One Product Return</h5>
                    <p class="mb-0 mt-1 text-muted">The return value is calculated from invoice discount, VAT, and AIT.</p>
                </div>
                <div class="card-body">
                    <form id="product-return-form" action="{{ route('product-returns.store') }}" method="POST" class="row g-3">
                        @csrf
                        <input type="hidden" name="sale_item_id" id="sale_item_id">
                        <div class="col-12">
                            <div id="return-product-label" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="return-quantity" class="form-label">Quantity to return</label>
                            <input type="number" name="quantity" id="return-quantity" class="form-control"
                                min="1" required>
                            <div id="return-quantity-help" class="form-text"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="return-date" class="form-label">Return date</label>
                            <input type="date" name="return_date" id="return-date" class="form-control"
                                value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="return-reason" class="form-label">Reason (optional)</label>
                            <input type="text" name="reason" id="return-reason" class="form-control" maxlength="2000">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger">
                                <i class="bx bx-undo me-1"></i>Confirm Product Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('return-search');
            const searchResults = document.getElementById('search-results');
            const historyCard = document.getElementById('customer-history');
            const historyInvoices = document.getElementById('history-invoices');
            const invoiceCard = document.getElementById('invoice-products');
            const invoiceRows = document.getElementById('invoice-product-rows');
            const returnCard = document.getElementById('return-form-card');
            const returnForm = document.getElementById('product-return-form');
            const errorContainer = document.createElement('div');
            errorContainer.className = 'alert alert-danger mt-3 d-none';
            errorContainer.setAttribute('role', 'alert');
            searchResults.after(errorContainer);

            const searchUrl = @json(route('product-returns.search'));
            const historyUrl = @json(route('product-returns.customer-history'));
            const invoiceUrl = @json(route('product-returns.invoice', ['id' => '__INVOICE_ID__']));
            let searchTimer;
            let selectedInvoiceIsWalkIn = false;

            returnForm.addEventListener('submit', function(event) {
                const confirmationMessage = selectedInvoiceIsWalkIn
                    ? 'This is a Walk-in Customer invoice. Are you sure you want to return this product?'
                    : 'Record this product return and update stock and account balances?';
                if (!window.confirm(confirmationMessage)) {
                    event.preventDefault();
                }
            });

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
                    throw new Error('Could not load the requested customer or invoice data.');
                }
                return response.json();
            }

            function addSuggestion(label, detail, action, data) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.dataset.action = action;
                Object.entries(data).forEach(([key, value]) => {
                    button.dataset[key] = value ?? '';
                });
                const title = document.createElement('strong');
                title.textContent = label;
                const description = document.createElement('span');
                description.className = 'ms-2 text-muted';
                description.textContent = detail;
                button.append(title, description);
                searchResults.append(button);
            }

            async function loadInvoice(invoiceId) {
                clearError();
                returnCard.classList.add('d-none');
                invoiceRows.replaceChildren();
                const url = invoiceUrl.replace('__INVOICE_ID__', encodeURIComponent(invoiceId));
                const invoice = await getJson(url);
                renderInvoice(invoice);
            }

            function renderInvoice(invoice) {
                selectedInvoiceIsWalkIn = invoice.customer_id === null;
                invoiceCard.classList.remove('d-none');
                document.getElementById('invoice-customer').textContent =
                    `${invoice.invoice_no} | ${invoice.invoice_date} | ${invoice.customer_name} | ${invoice.customer_phone || 'No phone'}`;
                invoiceRows.replaceChildren();
                invoice.items.forEach(function(item) {
                    const row = document.createElement('tr');
                    [
                        item.product_name,
                        `৳${Number(item.unit_price).toFixed(2)}`,
                        item.quantity,
                        item.returned_quantity,
                        item.remaining_quantity
                    ].forEach(function(value, index) {
                        const cell = document.createElement('td');
                        cell.textContent = value;
                        if (index === 1) cell.className = 'text-end';
                        if (index > 1) cell.className = 'text-center';
                        row.append(cell);
                    });
                    const actionCell = document.createElement('td');
                    if (item.returnable) {
                        const selectButton = document.createElement('button');
                        selectButton.type = 'button';
                        selectButton.className = 'btn btn-sm btn-outline-danger';
                        selectButton.textContent = 'Select Product';
                        selectButton.addEventListener('click', function() {
                            document.getElementById('sale_item_id').value = item.id;
                            document.getElementById('return-product-label').textContent =
                                `${item.product_name} — Invoice ${invoice.invoice_no}`;
                            document.getElementById('return-quantity').max = item.remaining_quantity;
                            document.getElementById('return-quantity').value = 1;
                            document.getElementById('return-quantity-help').textContent =
                                `Maximum available return quantity: ${item.remaining_quantity}`;
                            returnCard.classList.remove('d-none');
                            returnCard.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        });
                        actionCell.append(selectButton);
                    } else {
                        actionCell.textContent = item.product_id === null ? 'Product unavailable' : 'Fully returned';
                    }
                    row.append(actionCell);
                    invoiceRows.append(row);
                });
            }

            searchInput.addEventListener('input', function() {
                window.clearTimeout(searchTimer);
                const term = searchInput.value.trim();
                searchResults.replaceChildren();
                historyCard.classList.add('d-none');
                invoiceCard.classList.add('d-none');
                returnCard.classList.add('d-none');
                clearError();
                if (term.length < 2) return;

                searchTimer = window.setTimeout(async function() {
                    try {
                        const url = new URL(searchUrl, window.location.origin);
                        url.searchParams.set('q', term);
                        const data = await getJson(url);
                        if (!data.customers.length && !data.invoices.length) {
                            const empty = document.createElement('div');
                            empty.className = 'list-group-item text-muted';
                            empty.textContent = 'No matching customer or invoice found.';
                            searchResults.append(empty);
                            return;
                        }
                        data.customers.forEach(function(customer) {
                            addSuggestion(
                                customer.name,
                                `${customer.phone || 'No phone'} — View purchase history`,
                                'customer', {
                                    customerId: customer.customer_id,
                                    phone: customer.phone,
                                    name: customer.name
                                }
                            );
                        });
                        data.invoices.forEach(function(invoice) {
                            addSuggestion(
                                invoice.invoice_no,
                                `${invoice.customer_name} — ${invoice.invoice_date}`,
                                'invoice', {
                                    invoiceId: invoice.id
                                }
                            );
                        });
                    } catch (error) {
                        showError(error.message);
                    }
                }, 250);
            });

            searchResults.addEventListener('click', async function(event) {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                searchResults.replaceChildren();
                try {
                    if (button.dataset.action === 'customer') {
                        clearError();
                        const url = new URL(historyUrl, window.location.origin);
                        if (button.dataset.customerId) {
                            url.searchParams.set('customer_id', button.dataset.customerId);
                        } else if (button.dataset.phone) {
                            url.searchParams.set('phone', button.dataset.phone);
                            url.searchParams.set('name', button.dataset.name);
                        } else {
                            url.searchParams.set('name', button.dataset.name);
                        }
                        const data = await getJson(url);
                        historyCard.classList.remove('d-none');
                        document.getElementById('selected-customer').textContent =
                            `${button.dataset.name} — ${button.dataset.phone || 'No phone'}`;
                        historyInvoices.replaceChildren();
                        if (!data.invoices.length) {
                            historyInvoices.textContent = 'No previous invoices found.';
                        } else {
                            data.invoices.forEach(function(invoice) {
                                const invoiceButton = document.createElement('button');
                                invoiceButton.type = 'button';
                                invoiceButton.className = 'btn btn-outline-primary me-2 mb-2';
                                invoiceButton.textContent =
                                    `${invoice.invoice_no} — ${invoice.invoice_date} — ${invoice.items.length} product(s)`;
                                invoiceButton.addEventListener('click', function() {
                                    loadInvoice(invoice.id).catch(error => showError(error.message));
                                });
                                historyInvoices.append(invoiceButton);
                            });
                        }
                    } else {
                        await loadInvoice(button.dataset.invoiceId);
                    }
                } catch (error) {
                    showError(error.message);
                }
            });

            const initialInvoiceId = @json($initialInvoiceId);
            if (initialInvoiceId) {
                loadInvoice(initialInvoiceId).catch(error => showError(error.message));
            }
        });
    </script>
@endsection
