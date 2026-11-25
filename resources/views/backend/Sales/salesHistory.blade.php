@extends('dashboard.dashboard')
@section('title', isset($invoice) ? 'Update Sale ' . $invoice->invoice_no : 'Create Sale')

@section('content')
    @php
        $editingInvoice = isset($invoice) ? $invoice : null;
        $saleItems = old(
            'items',
            $editingInvoice
                ? $editingInvoice->items
                    ->map(
                        fn($item) => [
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'warranty_cover' => $item->warranty_cover,
                            'warranty_year' => $item->warranty_year,
                            'warranty_months' => $item->warranty_months,
                            'serial_mac' => $item->serial_mac,
                        ],
                    )
                    ->all()
                : [['product_id' => '', 'quantity' => 0]],
        );
        $discountInput = old(
            'discount_value',
            $editingInvoice
                ? ($editingInvoice->discount_type === 'percentage'
                    ? $editingInvoice->discount_value
                    : $editingInvoice->discount_amount)
                : old('discount_amount', 0),
        );
        $receivedAmount = $editingInvoice ? $editingInvoice->amount_paid : old('amount_paid', 0);
    @endphp
    <div class="page-wrapper">
        <div class="page-content">
            @canany(['sales.view', 'Voide_today_Invoice', 'Voide_anyday_Invoice', 'Update_today_Invoice',
                'Update_anyday_Invoice'])
                <div class="mt-4 border-0 shadow-sm card radius-10">
                    <div
                        class="flex-wrap gap-2 py-3 bg-transparent card-header border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-history text-primary me-2"></i>Organization
                            Total SalesHistory</h5>
                        <div style="width: 100%; max-width: 360px">
                            <label for="recentInvoiceSearch" class="visually-hidden">Searching SalesHistory</label>
                            <input type="search" id="recentInvoiceSearch" class="form-control"
                                placeholder="Search invoice, phone, employee..." autocomplete="off"
                                aria-controls="recent-invoices-table">
                        </div>
                    </div>
                    <div class="card-body table-responsive">
                        <table id="recent-invoices-table" class="table mb-0 align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Customer Phone</th>
                                    <th>Employee</th>
                                    <th>Total</th>
                                    <th>Due</th>
                                    <th>View/Print</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr data-recent-invoice-row>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                        <td>{{ $invoice->customer_name }}</td>
                                        <td>{{ $invoice->employee->name ?? 'N/A' }}</td>
                                        <td>৳{{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>৳{{ number_format($invoice->due_amount, 2) }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('sales.invoice', $invoice->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="View Invoice"
                                                aria-label="View invoice {{ $invoice->invoice_no }}">
                                                <i class="bx bx-show me-1"></i>Invoice & Challan
                                            </a>
                                            <!---Only Chalan Can view using this button -->
                                            {{-- <a href="{{ route('sales.delivery-challan', $invoice->id) }}"
                                                class="btn btn-sm btn-outline-success" title="Delivery Challan">
                                                <i class="bx bx-package me-1"></i>Challan
                                            </a> --}}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-muted">No invoices have been created
                                            yet.</td>
                                    </tr>
                                @endforelse
                                <tr id="recentInvoicesNoResults" class="d-none">
                                    <td colspan="7" class="py-4 text-center text-muted">No matching invoices found.</td>
                                </tr>
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
            let suggestionTimer;
            let customerSearchSequence = 0;
            let lastLookedUpPhone = '';
            let rowIndex = Array.from(itemsBody.querySelectorAll('.product-select')).reduce((nextIndex, select) => {
                const match = select.name.match(/^items\[(\d+)\]/);
                return match ? Math.max(nextIndex, Number(match[1]) + 1) : nextIndex;
            }, 0);

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
                    row.querySelector('.quantity-input').value = '1';
                } else if (!option) {
                    row.querySelector('.unit-price-input').value = '';
                }
                row.querySelector('.unit-cell').textContent = option ?
                    (option.dataset.unit || 'Unit') :
                    '—';
                searchInput.setCustomValidity(option || !search ? '' :
                    'Choose a product from the suggested name and code.');
            }
            const lookupButton = document.getElementById('lookupCustomer');
            if (lookupButton) {
                lookupButton.addEventListener('click', function() {
                    window.clearTimeout(suggestionTimer);
                    if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                        suggestCustomers(true);
                    } else {
                        lookupCustomer();
                    }
                });
            }
            customerPhone.addEventListener('change', function() {
                window.clearTimeout(suggestionTimer);
                if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                    suggestCustomers(true);
                } else {
                    lookupCustomer();
                }
            });
            customerPhone.addEventListener('input', function() {
                customerFound = false;
                ledgerPanel.classList.add('d-none');
                newCustomerDetails.classList.add('d-none');
                customerName.value = '';
                window.clearTimeout(suggestionTimer);
                suggestionTimer = window.setTimeout(suggestCustomers, 250);
            });
            customerPhone.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(suggestionTimer);
                    if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                        suggestCustomers(true);
                    } else {
                        lookupCustomer();
                    }
                }
            });
            document.getElementById('addItem').addEventListener('click', addRow);
            itemsBody.addEventListener('change', (event) => {
                if (event.target.matches('.product-search')) {
                    syncProductSearch(event.target.closest('.sale-item-row'));
                }
                if (event.target.matches('.warranty-cover-toggle')) {
                    refreshWarrantyRows();
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
                    row.querySelector('.quantity-input').value = '0';
                    const warrantyRow = row.nextElementSibling;
                    const warrantyToggle = warrantyRow.querySelector('.warranty-cover-toggle');
                    warrantyToggle.checked = false;
                    warrantyRow.querySelector('.warranty-fields').classList.add('d-none');
                    warrantyRow.querySelectorAll('.warranty-fields input').forEach((input) => {
                        input.value = '';
                        input.disabled = true;
                        input.required = false;
                    });
                } else {
                    const row = button.closest('.sale-item-row');
                    row.nextElementSibling.remove();
                    row.remove();
                }
                refreshWarrantyRows();
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
                @if ($editingInvoice)
                    saveCustomer.dataset.confirmed = 'yes';
                    form.requestSubmit();
                    return;
                @endif
                if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                    await suggestCustomers(true);
                    if (!/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                        await window.Swal.fire({
                            title: 'Select a customer',
                            text: 'Choose a customer from the suggestions, or enter a phone number.',
                            icon: 'warning',
                        });
                        customerPhone.focus();
                        return;
                    }
                }
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
                    title: @json($editingInvoice ? 'Unable to update invoice' : 'Unable to create sale'),
                    text: @json($errors->all()).join('\n'),
                    icon: 'error',
                });
            @endif

            updateTotals();
            @unless ($editingInvoice)
                if (customerPhone.value) {
                    if (/^[+\d\s().-]+$/.test(customerPhone.value.trim())) {
                        lookupCustomer();
                    } else {
                        suggestCustomers();
                    }
                }
            @endunless
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('recentInvoiceSearch');

            if (!searchInput) {
                return;
            }

            const invoiceRows = document.querySelectorAll('[data-recent-invoice-row]');
            const noResultsRow = document.getElementById('recentInvoicesNoResults');

            // সার্চ বক্সের লেখা দিয়ে শুধু Recent Invoices টেবিলের row-গুলো তাৎক্ষণিকভাবে filter করে।
            searchInput.addEventListener('input', function() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                let matchingRows = 0;

                invoiceRows.forEach(function(row) {
                    const matches = row.textContent.toLowerCase().includes(searchTerm);
                    row.classList.toggle('d-none', !matches);
                    matchingRows += matches ? 1 : 0;
                });

                if (noResultsRow) {
                    noResultsRow.classList.toggle('d-none', !searchTerm || matchingRows > 0);
                }
            });
        });
    </script>
@endsection
