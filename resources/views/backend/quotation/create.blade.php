@extends('dashboard.dashboard')
@section('title', 'Get Quotation')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Quotation</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('quotation.index') }}">Quotations</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Get Quotation</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('quotation.index') }}" class="btn btn-outline-primary px-3 shadow-sm">
                        <i class="bx bx-list-ul me-1"></i> View All Quotations
                    </a>
                </div>
            </div>

            <form id="quotationForm" action="{{ route('quotation.store') }}" method="POST">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>Quotation was not saved. Please correct the following:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Top Row: Customer Info on Left, Organization Info on Right (Invoice Layout Style) -->
                <div class="row g-3 mb-4">
                    <!-- Customer Information Section -->
                    <div class="col-12 col-lg-7">
                        <div class="card border-0 shadow-sm radius-10 h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-primary"><i class="bx bx-user-pin me-2"></i>Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-user"></i></span>
                                            <input type="text" name="customer_name" id="customer_name" class="form-control"
                                                placeholder="Enter customer or company name" value="{{ old('customer_name') }}" required>
                                        </div>
                                        @error('customer_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-phone"></i></span>
                                            <input type="text" name="customer_phone" id="customer_phone" class="form-control"
                                                placeholder="Enter mobile or contact number" value="{{ old('customer_phone') }}" required>
                                        </div>
                                        @error('customer_phone') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <small id="customerLookupStatus" class="text-muted" aria-live="polite"></small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Quotation Date <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-calendar"></i></span>
                                            <input type="date" name="quotation_date" id="quotation_date" class="form-control"
                                                value="{{ old('quotation_date', date('Y-m-d')) }}" required>
                                        </div>
                                        @error('quotation_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Quotation Reference</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-hash"></i></span>
                                            <input type="text" class="form-control bg-light" value="{{ $generatedQuotationNo }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Customer Address</label>
                                        <textarea name="customer_address" id="customer_address" class="form-control" rows="2"
                                            placeholder="Enter customer address or delivery location">{{ old('customer_address') }}</textarea>
                                        @error('customer_address') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Organization Info Displayed on the Side (Invoice Layout) -->
                    <div class="col-12 col-lg-5">
                        <div class="card border-0 shadow-sm radius-10 h-100 bg-light">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-buildings me-2 text-primary"></i>Organization Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    @if ($organization?->logo)
                                        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                            alt="{{ $organization->name ?: 'Logo' }}"
                                            class="rounded border bg-white p-1"
                                            style="max-width: 85px; max-height: 60px; object-fit: contain;">
                                    @else
                                        <img src="{{ asset('assets/images/logo-icon.png') }}"
                                            alt="Logo" class="rounded border bg-white p-1"
                                            style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                    @endif
                                    <div>
                                        <h5 class="mb-1 fw-bold text-primary">{{ $organization?->name ?: 'My-POS Enterprise' }}</h5>
                                        <span class="badge bg-light-primary text-primary">Issuer Organization</span>
                                    </div>
                                </div>

                                <ul class="list-unstyled mb-0 small text-secondary">
                                    @if ($organization?->phone)
                                        <li class="mb-2 d-flex align-items-center">
                                            <i class="bx bx-phone me-2 text-primary fs-6"></i>
                                            <strong>Phone:</strong>&nbsp;{{ $organization->phone }}
                                        </li>
                                    @endif
                                    @if ($organization?->email)
                                        <li class="mb-2 d-flex align-items-center">
                                            <i class="bx bx-envelope me-2 text-primary fs-6"></i>
                                            <strong>Email:</strong>&nbsp;{{ $organization->email }}
                                        </li>
                                    @endif
                                    @if ($organization?->address)
                                        <li class="mb-2 d-flex align-items-start">
                                            <i class="bx bx-map-pin me-2 text-primary fs-6 mt-1"></i>
                                            <span><strong>Address:</strong> {{ $organization->address }}</span>
                                        </li>
                                    @endif
                                </ul>
                                <div class="mt-3 p-2 rounded bg-white border small text-muted">
                                    <i class="bx bx-info-circle me-1 text-info"></i> This organization header will be formatted on the quotation document similar to your customer invoices.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Items Section -->
                <div class="card border-0 shadow-sm radius-10 mb-4">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-cart text-primary me-2"></i>Quotation Items</h5>
                        <button type="button" id="addItemBtn" class="btn btn-sm btn-primary px-3">
                            <i class="bx bx-plus me-1"></i> Add Product
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 280px;">Product Name <span class="text-danger">*</span></th>
                                        <th style="width: 140px;" class="text-end">Unit Price (৳) <span class="text-danger">*</span></th>
                                        <th style="width: 120px;" class="text-center">Quantity <span class="text-danger">*</span></th>
                                        <th style="width: 100px;" class="text-center">Unit</th>
                                        <th style="width: 150px;" class="text-end">Line Total (৳)</th>
                                        <th style="width: 60px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    @foreach (old('items', [['product_name' => '', 'product_id' => '', 'unit_price' => '0.00', 'quantity' => 0]]) as $index => $oldItem)
                                        @php
                                            $oldProduct = $products->firstWhere('id', $oldItem['product_id'] ?? null);
                                            $selectedQuantitySize = $oldItem['quantity_size'] ?? $oldProduct?->quantity_size ?? ''; // পুরোনো ইনপুটের unit, না থাকলে product-এর unit বেছে নেয়।
                                        @endphp
                                        <tr class="item-row">
                                            <td>
                                                {{-- পুরোনো value-তে Unit-সহ display_name আসত; Unit আলাদা কলামে দেখানোর জন্য এখন Product name ও code দেখানো হয়। --}}
                                                {{-- value="{{ $oldProduct ? $oldProduct->display_name : ($oldItem['product_name'] ?? '') }}" --}}
                                                <input type="text" class="form-control product-name-input"
                                                    name="items[{{ $index }}][product_name]" list="quotationProductOptions"
                                                    placeholder="Type or select product..." autocomplete="off" required
                                                    value="{{ $oldProduct ? $oldProduct->productName . ' — PRD-' . str_pad($oldProduct->id, 5, '0', STR_PAD_LEFT) : ($oldItem['product_name'] ?? '') }}">
                                                <input type="hidden" name="items[{{ $index }}][product_id]"
                                                    class="product-id-hidden" value="{{ $oldItem['product_id'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control text-end item-unit-price"
                                                    name="items[{{ $index }}][unit_price]" placeholder="0.00"
                                                    value="{{ $oldItem['unit_price'] ?? '0.00' }}" required>
                                            </td>
                                            <td>
                                                <input type="number" min="1" step="1"
                                                    class="form-control text-center item-quantity"
                                                    name="items[{{ $index }}][quantity]"
                                                    value="{{ $oldItem['quantity'] ?? 0 }}" required>
                                            </td>
                                            <td class="item-unit text-center"> {{-- quotation item-এর unit dropdown cell শুরু করে। --}}
                                                <select name="items[{{ $index }}][quantity_size]" class="form-select form-select-sm item-unit-select"> {{-- নির্বাচিত unit-কে form data-তে পাঠায়। --}}
                                                    <option value="">-- Select Unit --</option> {{-- unit নির্বাচন না করলে ফাঁকা মান পাঠায়। --}}
                                                    @foreach ($quantitySizes as $quantitySize) {{-- QuantitySize table থেকে আনা প্রতিটি unit option বানায়। --}}
                                                        <option value="{{ $quantitySize->name }}" {{ $selectedQuantitySize === $quantitySize->name ? 'selected' : '' }}>{{ $quantitySize->name }}</option> {{-- unit-এর নাম value ও label হিসেবে রাখে, পূর্বনির্বাচিত হলে select করে। --}}
                                                    @endforeach {{-- unit option-গুলোর loop শেষ করে। --}}
                                                </select> {{-- unit dropdown শেষ করে। --}}
                                            </td> {{-- unit dropdown cell শেষ করে। --}}
                                            <td class="text-end fw-bold">
                                                <span class="item-line-total text-dark">0.00</span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" title="Remove Item">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Datalist for fast searchable product auto-fill -->
                        <datalist id="quotationProductOptions">
                            @foreach ($products as $product)
                                {{-- পুরোনো option-এ display_name ব্যবহার হতো, ফলে Unit পণ্যের নামের পাশে দেখা যেত। --}}
                                {{-- <option value="{{ $product->display_name }}" data-id="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}"> --}}
                                {{-- এখন নামের সঙ্গে Product code রাখা হয়েছে; Unit আলাদা টেবিল কলামে দেখানো হয়। --}}
                                <option value="{{ $product->productName }} — PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}"
                                    data-unit="{{ $product->quantity_size }}"> {{-- পণ্য নির্বাচন করলে তার quantity_size dropdown-এ preselect করার জন্য রাখে। --}}
                                    {{-- data-id="{{ $product->id }}" --}}
                                    {{-- data-id="{{ $product->productName }}" --}}
                                    {{-- data-price="{{ $product->price ?? 0 }}" --}}
                                    {{-- data-unit="{{ $product->quantity_size ?: ($product->stock_unit ?: 'Unit') }}"> --}}
                                </option>
                            @endforeach
                        </datalist>
                        <template id="quotationUnitOptions"> {{-- নতুন item row-এর unit dropdown-এর option-গুলো রাখে। --}}
                            <option value="">-- Select Unit --</option> {{-- নতুন row-এ ফাঁকা unit option দেয়। --}}
                            @foreach ($quantitySizes as $quantitySize) {{-- QuantitySize table-এর প্রতিটি record template-এ যোগ করে। --}}
                                <option value="{{ $quantitySize->name }}">{{ $quantitySize->name }}</option> {{-- একই unit name value ও visible label হিসেবে ব্যবহার করে। --}}
                            @endforeach {{-- নতুন row-এর unit option loop শেষ করে। --}}
                        </template> {{-- নতুন row-এর জন্য unit options template শেষ করে। --}}
                    </div>
                </div>

                <!-- Bottom Row: Terms & Conditions on Left, Totals Calculation on Right -->
                <div class="row g-3 mb-4">
                    <!-- Terms and Conditions Input Section -->
                    <div class="col-12 col-lg-7">
                        <div class="card border-0 shadow-sm radius-10 h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-file-blank text-primary me-2"></i>Terms & Conditions</h5>
                            </div>
                            <div class="card-body">
                                <label class="form-label fw-semibold">Quotation Terms and Conditions</label>
                                <textarea name="terms_conditions" id="terms_conditions" rows="5" class="form-control"
                                    placeholder="Input terms and conditions to be displayed on this quotation...">{{ old('terms_conditions', $defaultTerms) }}</textarea>
                                <div class="form-text text-muted">
                                    These terms will be rendered clearly at the bottom of the generated Quotation document.
                                </div>

                                <div class="mt-3">
                                    <label class="form-label fw-semibold">Additional Notes (Optional)</label>
                                    <textarea name="notes" id="notes" rows="2" class="form-control"
                                        placeholder="Any additional remarks or delivery notes...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Automatic Price Calculation Box -->
                    <div class="col-12 col-lg-5">
                        <div class="card border-0 shadow-sm radius-10 h-100">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <h5 class="mb-0 fw-bold text-dark"><i class="bx bx-calculator text-primary me-2"></i>Price Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="py-2 d-flex justify-content-between align-items-center border-bottom">
                                    <span class="text-secondary fw-semibold">Subtotal:</span>
                                    <span class="fs-6 fw-bold text-dark">৳<span id="displaySubtotal">0.00</span></span>
                                </div>

                                <!-- Discount section -->
                                <div class="py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary fw-semibold">Discount:</span>
                                        <span class="text-danger fw-bold">- ৳<span id="displayDiscountAmount">0.00</span></span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <select name="discount_type" id="discount_type" class="form-select form-select-sm">
                                                <option value="flat">Fixed (৳)</option>
                                                <option value="percentage">Percentage (%)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" step="0.01" min="0" name="discount_value" id="discount_value"
                                                class="form-control form-control-sm text-end" value="0" placeholder="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- VAT section -->
                                <div class="py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary fw-semibold">VAT:</span>
                                        <span class="text-dark fw-bold">+ ৳<span id="displayVatAmount">0.00</span></span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">VAT Rate (%)</span>
                                                <input type="number" step="0.01" min="0" max="100" name="vat_percentage" id="vat_percentage"
                                                    class="form-control text-end" value="0" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- AIT section -->
                                <div class="py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary fw-semibold">AIT:</span>
                                        <span class="text-dark fw-bold">+ ৳<span id="displayAitAmount">0.00</span></span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">AIT Rate (%)</span>
                                                <input type="number" step="0.01" min="0" max="100" name="ait_percentage" id="ait_percentage"
                                                    class="form-control text-end" value="{{ old('ait_percentage', 0) }}" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grand Total -->
                                <div class="py-3 d-flex justify-content-between align-items-center bg-light-primary rounded p-3 mt-3">
                                    <span class="fs-5 fw-bold text-primary">Total Price:</span>
                                    <span class="fs-4 fw-bolder text-primary">৳<span id="displayGrandTotal">0.00</span></span>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary w-100 py-2 fs-6 shadow-sm">
                                        <i class="bx bx-check-circle me-1"></i> Generate Quotation
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.getElementById('itemsTableBody');
        let rowIndex = Array.from(tableBody.querySelectorAll('.product-name-input')).reduce((nextIndex, input) => {
            const match = input.name.match(/^items\[(\d+)\]/);
            return match ? Math.max(nextIndex, Number(match[1]) + 1) : nextIndex;
        }, 0);
        const addItemBtn = document.getElementById('addItemBtn');
        const productDatalist = document.getElementById('quotationProductOptions');
        const unitOptionsTemplate = document.getElementById('quotationUnitOptions'); // database-ভিত্তিক unit option-গুলো নতুন row-তে কপি করার template নেয়।

        const displaySubtotal = document.getElementById('displaySubtotal');
        const displayDiscountAmount = document.getElementById('displayDiscountAmount');
        const displayVatAmount = document.getElementById('displayVatAmount');
        const displayAitAmount = document.getElementById('displayAitAmount');
        const displayGrandTotal = document.getElementById('displayGrandTotal');

        const discountTypeSelect = document.getElementById('discount_type');
        const discountValueInput = document.getElementById('discount_value');
        const vatPercentageInput = document.getElementById('vat_percentage');
        const aitPercentageInput = document.getElementById('ait_percentage');
        const customerNameInput = document.getElementById('customer_name');
        const customerPhoneInput = document.getElementById('customer_phone');
        const customerAddressInput = document.getElementById('customer_address');
        const customerLookupStatus = document.getElementById('customerLookupStatus');
        let customerLookupTimer;
        let customerLookupRequest = 0;
        let autoFilledCustomer = null;

        function clearPreviousCustomerAutofill(activeInput) {
            if (!autoFilledCustomer) {
                return;
            }

            if (activeInput !== customerNameInput && customerNameInput.value === autoFilledCustomer.name) {
                customerNameInput.value = '';
            }
            if (activeInput !== customerPhoneInput && customerPhoneInput.value === autoFilledCustomer.phone) {
                customerPhoneInput.value = '';
            }
            if (activeInput !== customerAddressInput && customerAddressInput.value === autoFilledCustomer.address) {
                customerAddressInput.value = '';
            }
            autoFilledCustomer = null;
        }

        function scheduleCustomerLookup(input, field) {
            clearPreviousCustomerAutofill(input);
            customerLookupRequest++;
            const requestId = customerLookupRequest;
            const value = input.value.trim();

            window.clearTimeout(customerLookupTimer);
            customerLookupStatus.textContent = '';
            if (value.length < 2) {
                return;
            }

            customerLookupStatus.textContent = 'Searching saved customers...';
            customerLookupTimer = window.setTimeout(async function () {
                const url = new URL(@json(route('quotation.customer.lookup')), window.location.origin);
                url.searchParams.set(field, value);

                try {
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) {
                        throw new Error('Customer lookup failed with status ' + response.status);
                    }

                    const result = await response.json();
                    if (requestId !== customerLookupRequest || input.value.trim() !== value) {
                        return;
                    }

                    if (!result.found) {
                        customerLookupStatus.textContent = result.ambiguous
                            ? 'More than one customer matches. Search by phone number instead.'
                            : 'No saved customer found.';
                        return;
                    }

                    customerNameInput.value = result.customer.name || '';
                    customerPhoneInput.value = result.customer.phone || '';
                    customerAddressInput.value = result.customer.address || '';
                    autoFilledCustomer = {
                        name: customerNameInput.value,
                        phone: customerPhoneInput.value,
                        address: customerAddressInput.value
                    };
                    customerLookupStatus.textContent = 'Saved customer details filled in.';
                } catch (error) {
                    if (requestId === customerLookupRequest) {
                        customerLookupStatus.textContent = 'Could not load customer details. Please try again.';
                    }
                    console.error(error);
                }
            }, 350);
        }

        customerNameInput.addEventListener('input', function () {
            scheduleCustomerLookup(customerNameInput, 'name');
        });
        customerPhoneInput.addEventListener('input', function () {
            scheduleCustomerLookup(customerPhoneInput, 'phone');
        });

        // Product map from datalist options
        const productMap = {};
        if (productDatalist) {
            Array.from(productDatalist.options).forEach(opt => {
                productMap[opt.value.trim().toLowerCase()] = {
                    id: opt.getAttribute('data-id'),
                    price: parseFloat(opt.getAttribute('data-price')) || 0,
                    unit: opt.getAttribute('data-unit') || '' // product-এর quantity_size না থাকলে dropdown ফাঁকা রাখে।
                };
            });
        }

        // Attach event listeners to a row
        function attachRowEvents(row) {
            const nameInput = row.querySelector('.product-name-input');
            const hiddenId = row.querySelector('.product-id-hidden');
            const unitPriceInput = row.querySelector('.item-unit-price');
            const quantityInput = row.querySelector('.item-quantity');
            const unitSelect = row.querySelector('.item-unit-select'); // বর্তমান item row-এর unit dropdown ধরে।
            const removeBtn = row.querySelector('.remove-item-btn');

            nameInput.addEventListener('input', function () {
                const val = this.value.trim().toLowerCase();
                if (productMap[val]) {
                    hiddenId.value = productMap[val].id;
                    unitSelect.value = productMap[val].unit; // নির্বাচিত product-এর quantity_size থাকলে dropdown-এ সেট করে।
                    if (!parseFloat(unitPriceInput.value) || parseFloat(unitPriceInput.value) === 0) {
                        unitPriceInput.value = productMap[val].price.toFixed(2);
                    }
                } else {
                    hiddenId.value = '';
                    unitSelect.value = ''; // অজানা product লিখলে আগের unit নির্বাচন সরিয়ে দেয়।
                }
                updateTotals();
            });

            unitPriceInput.addEventListener('input', updateTotals);
            quantityInput.addEventListener('input', updateTotals);

            removeBtn.addEventListener('click', function () {
                const totalRows = tableBody.querySelectorAll('.item-row').length;
                if (totalRows > 1) {
                    row.remove();
                    updateTotals();
                } else {
                    alert('At least one item is required in the quotation.');
                }
            });
        }

        // Add new item row
        addItemBtn.addEventListener('click', function () {
            const newRow = document.createElement('tr');
            newRow.classList.add('item-row');
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control product-name-input"
                        name="items[${rowIndex}][product_name]" list="quotationProductOptions"
                        placeholder="Type or select product..." autocomplete="off" required>
                    <input type="hidden" name="items[${rowIndex}][product_id]" class="product-id-hidden" value="">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" class="form-control text-end item-unit-price"
                        name="items[${rowIndex}][unit_price]" placeholder="0.00" value="0.00" required>
                </td>
                <td>
                    <input type="number" min="1" step="1" class="form-control text-center item-quantity"
                        name="items[${rowIndex}][quantity]" value="0" required>
                </td>
                <td class="item-unit text-center"> {{-- নতুন quotation row-এর unit cell তৈরি করে। --}}
                    <select name="items[${rowIndex}][quantity_size]" class="form-select form-select-sm item-unit-select"> {{-- নতুন row-এর নির্বাচিত unit form-এ পাঠায়। --}}
                        ${unitOptionsTemplate.innerHTML} {{-- QuantitySize table থেকে render করা option-গুলো এখানে বসায়। --}}
                    </select> {{-- নতুন row-এর unit dropdown শেষ করে। --}}
                </td> {{-- নতুন row-এর unit cell শেষ করে। --}}
                <td class="text-end fw-bold">
                    <span class="item-line-total text-dark">0.00</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" title="Remove Item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(newRow);
            attachRowEvents(newRow);
            rowIndex++;
            newRow.querySelector('.product-name-input').focus();
        });

        // Initialize events on existing rows
        tableBody.querySelectorAll('.item-row').forEach(row => attachRowEvents(row));

        // Listen for discount & VAT adjustments
        discountTypeSelect.addEventListener('change', updateTotals);
        discountValueInput.addEventListener('input', updateTotals);
        vatPercentageInput.addEventListener('input', updateTotals);
        aitPercentageInput.addEventListener('input', updateTotals);

        // Core calculation function
        function updateTotals() {
            let subtotal = 0;

            tableBody.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-unit-price').value) || 0;
                const lineTotal = Math.round(qty * price * 100) / 100;
                row.querySelector('.item-line-total').textContent = lineTotal.toFixed(2);
                subtotal += lineTotal;
            });

            const discountType = discountTypeSelect.value;
            const discountVal = parseFloat(discountValueInput.value) || 0;
            let discountAmount = 0;

            if (discountType === 'percentage') {
                discountAmount = Math.round((subtotal * discountVal / 100) * 100) / 100;
            } else {
                discountAmount = Math.min(discountVal, subtotal);
            }

            const vatPercent = parseFloat(vatPercentageInput.value) || 0;
            const taxableAmount = Math.max(subtotal - discountAmount, 0);
            const vatAmount = Math.round((taxableAmount * vatPercent / 100) * 100) / 100;
            const aitPercent = parseFloat(aitPercentageInput.value) || 0;
            const aitAmount = Math.round((taxableAmount * aitPercent / 100) * 100) / 100;
            const grandTotal = Math.round((taxableAmount + vatAmount + aitAmount) * 100) / 100;

            displaySubtotal.textContent = subtotal.toFixed(2);
            displayDiscountAmount.textContent = discountAmount.toFixed(2);
            displayVatAmount.textContent = vatAmount.toFixed(2);
            displayAitAmount.textContent = aitAmount.toFixed(2);
            displayGrandTotal.textContent = grandTotal.toFixed(2);
        }

        updateTotals();
    });
</script>
@endsection
