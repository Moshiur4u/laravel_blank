<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\VoucherEntry;
use App\Support\SaleInvoiceUpdateService;
use App\Support\SaleInvoiceVoidService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    private const PAYMENT_METHODS = ['cash', 'bank', 'nagad', 'bkash', 'rocket', 'upay'];
    private const DEFAULT_TERMS = "This is your note where you can write your Note, Terms & Condition. \n1.VAT & Taxes are not included in the above price.\n2.Read the Manual Book supplied with the vehicle with attention and follow the service instruction properly.\n3.Delivery of Registration Document is subject to clearance of all dues (if any).\n4.Tring Some text limit check.";

    public function index()
    {
        $products = Product::query()
            ->whereRaw('CAST(unit AS DOUBLE) > 0')
            ->orderBy('productName')
            ->get();
        $invoices = SaleInvoice::with('employee')->latest('invoice_date')->limit(10)->get();
        $defaultTerms = self::DEFAULT_TERMS;

        return view('backend.Sales.salesIndex', compact('products', 'invoices', 'defaultTerms'));
    }

    public function blankInvoice()
    {
        return view('backend.Sales.blankInvoice');
    }

    public function customerByPhone(Request $request)
    {
        if ($request->filled('search')) {
            $validated = $request->validate([
                'search' => 'required|string|max:100',
            ]);
            $search = trim($validated['search']);

            if ($search === '') {
                return response()->json(['suggestions' => []]);
            }

            $customers = Customer::query()
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                })
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'phone']);

            return response()->json([
                'suggestions' => $customers,
            ]);
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $customer = Customer::with(['ledgers' => function ($query) {
            $query->where('entry_type', 'sale')
                ->where('due_amount', '>', 0)
                ->with(['saleItems.product', 'saleItems.returns'])
                ->latest('sale_date')
                ->orderByDesc('id');
        }])->where('phone', trim($validated['phone']))->first();

        if (! $customer) {
            return response()->json(['found' => false]);
        }

        // গ্রাহকের বকেয়া = প্রারম্ভিক বকেয়া + বিক্রির টাকা - আদায় করা টাকা।
        $due = (float) $customer->opening_due
            + (float) $customer->ledgers()->sum('total_amount')
            - (float) $customer->ledgers()->sum('payment_amount');

        return response()->json([
            'found' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'due' => max(round($due, 2), 0),
                'ledger' => $customer->ledgers->map(fn(CustomerLedger $ledger) => [
                    'date' => $ledger->sale_date?->format('Y-m-d'),
                    'type' => $ledger->entry_type,
                    'item' => $ledger->product_name,
                    'items' => $ledger->saleItems->map(fn(SaleItem $item) => [
                        'name' => $item->display_product_name,
                        'quantity' => (int) $item->quantity,
                        'returned_quantity' => (int) $item->returns->sum('quantity'),
                    ])->values(),
                    'amount' => (float) $ledger->total_amount,
                    'payment' => (float) $ledger->payment_amount,
                    'due' => (float) $ledger->due_amount,
                ]),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_phone' => 'required|string|max:30',
            'save_customer' => 'required|in:yes,no',
            'customer_name' => 'required_if:save_customer,yes|nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:2000',
            'include_previous_due' => 'nullable|boolean',
            'discount_type' => 'nullable|in:amount,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'vat_amount' => 'nullable|numeric|min:0',
            'ait_percentage' => 'nullable|numeric|min:0|max:100',
            'ait_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:' . implode(',', self::PAYMENT_METHODS),
            'notes' => 'nullable|string|max:2000',
            'terms_conditions' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0|max:9999999999999.99',
        ]);

        $customerPhone = trim($validated['customer_phone']);
        $requestedPaymentAmount = round((float) ($validated['amount_paid'] ?? 0), 2);
        $creator = $request->user();

        $invoice = DB::transaction(function () use ($validated, $customerPhone, $requestedPaymentAmount, $creator) {
            // একই পণ্যের একাধিক কার্ট লাইনের মজুত একসাথে যাচাই করতে পণ্যগুলো ID অনুযায়ী lock করা হয়।
            $quantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn($rows) => $rows->sum(fn($row) => (int) $row['quantity']));
            $products = Product::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if (! $product || (float) $product->unit < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ($product?->display_name ?? 'A selected product') . ' does not have enough stock.',
                    ]);
                }
            }

            $customer = Customer::query()->where('phone', $customerPhone)->lockForUpdate()->first();
            if (! $customer && $validated['save_customer'] === 'yes') {
                $customer = Customer::create([
                    'name' => $validated['customer_name'],
                    'phone' => $customerPhone,
                    'email' => $validated['customer_email'] ?? null,
                    'address' => $validated['customer_address'] ?? null,
                    'customer_type' => 'temporary',
                    'status' => 1,
                ]);
            }

            $previousDue = $customer
                ? max(round((float) $customer->opening_due
                    + (float) $customer->ledgers()->sum('total_amount')
                    - (float) $customer->ledgers()->sum('payment_amount'), 2), 0)
                : 0;
            $includePreviousDue = $customer && ! empty($validated['include_previous_due']);
            $userId = Auth::id();

            // ডিসকাউন্ট, VAT, AIT ও আগের বকেয়া সার্ভারেই হিসাব হয়; ব্রাউজারের total বিশ্বাস করা হয় না।
            $subtotal = 0;
            $lineItems = [];
            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = round((float) $item['unit_price'], 2);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;
                $lineItems[] = compact('product', 'quantity', 'unitPrice', 'lineTotal');
            }

            $subtotal = round($subtotal, 2);
            $discountType = $validated['discount_type'] ?? 'amount';
            $discountValue = isset($validated['discount_value']) ? round((float) $validated['discount_value'], 2) : null;

            // যদি Percentage বাছা হয় তবে সাবটোটালের ওপর শতকরা হার অনুযায়ী ডিসকাউন্ট নির্ণয় হয়, অন্যথায় সরাসরি টাকার অঙ্ক ব্যবহৃত হয়।
            if ($discountType === 'percentage' && $discountValue !== null) {
                $discount = round(($subtotal * $discountValue) / 100, 2);
            } else {
                $discount = round((float) ($validated['discount_amount'] ?? $discountValue ?? 0), 2);
                $discountValue = $discount;
            }

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The discount cannot be greater than the subtotal.',
                ]);
            }
            $taxableAmount = round($subtotal - $discount, 2);
            $vat = array_key_exists('vat_percentage', $validated)
                ? round($taxableAmount * (float) $validated['vat_percentage'] / 100, 2)
                : round((float) ($validated['vat_amount'] ?? 0), 2);
            $ait = array_key_exists('ait_percentage', $validated)
                ? round($taxableAmount * (float) $validated['ait_percentage'] / 100, 2)
                : round((float) ($validated['ait_amount'] ?? 0), 2);
            $saleAmount = round($taxableAmount + $vat + $ait, 2);
            $invoiceTotal = round($saleAmount + ($includePreviousDue ? $previousDue : 0), 2);

            // Walk-in sales have no customer ledger for later collection, so collect the full invoice total.
            $paymentAmount = $customer ? $requestedPaymentAmount : $invoiceTotal;

            if ($paymentAmount > 0 && empty($validated['payment_method'])) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Select how the payment was received.',
                ]);
            }

            if ($paymentAmount > $invoiceTotal) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment cannot be greater than the invoice total.',
                ]);
            }

            $currentMonth = now()->format('Ym');
            $latestInvoice = SaleInvoice::query()
                ->where('invoice_no', 'like', "INV-{$currentMonth}-%")
                ->lockForUpdate()
                ->latest('id')
                ->first();
            $nextNumber = 1;
            if ($latestInvoice) {
                $parts = explode('-', $latestInvoice->invoice_no);
                if (isset($parts[2]) && is_numeric($parts[2])) {
                    $nextNumber = (int) $parts[2] + 1;
                }
            }
            $invoiceNumber = "INV-{$currentMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $invoice = SaleInvoice::create([
                'invoice_no' => $invoiceNumber,
                'customer_id' => $customer?->id,
                'customer_phone' => $customerPhone,
                'customer_name' => $customer?->name ?? $validated['customer_name'] ?? null,
                'customer_address' => $customer?->address ?? $validated['customer_address'] ?? null,
                'user_id' => $userId,
                'invoice_date' => now(),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue ?? 0,
                'discount_amount' => $discount,
                'vat_amount' => $vat,
                'ait_amount' => $ait,
                'previous_due' => $includePreviousDue ? $previousDue : 0,
                'include_previous_due' => (bool) $includePreviousDue,
                'total_amount' => $invoiceTotal,
                'amount_paid' => $paymentAmount,
                'due_amount' => round($invoiceTotal - $paymentAmount, 2),
                'payment_method' => $paymentAmount > 0 ? $validated['payment_method'] : null,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            foreach ($lineItems as $lineItem) {
                /** @var Product $product */
                $product = $lineItem['product'];
                $quantity = $lineItem['quantity'];

                // বিক্রির সময় পণ্যের ক্রয়মূল্য snapshot আকারে রাখা হয় যাতে পরে পণ্যের ক্রয়মূল্য বদলালেও রিপোর্টের লাভ অপরিবর্তিত থাকে।
                SaleItem::create([
                    'sale_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'product_name' => $product->productName,
                    'quantity' => $quantity,
                    'purchase_price' => $product->purchase_price !== null ? (float) $product->purchase_price : null,
                    'unit_price' => $lineItem['unitPrice'],
                    'line_total' => $lineItem['lineTotal'],
                ]);

                $product->unit = (string) ((float) $product->unit - $quantity);
                $product->save();
            }

            $voucherableType = SaleInvoice::class;
            $voucherableId = $invoice->id;

            if ($customer) {
                $totalQuantity = collect($lineItems)->sum('quantity');
                $productsSummary = collect($lineItems)
                    ->map(fn($lineItem) => $lineItem['product']->display_name . ' × ' . $lineItem['quantity'])
                    ->implode(', ');

                $customerLedger = CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'sale_invoice_id' => $invoice->id,
                    'employee_id' => $userId,
                    'received_by' => $paymentAmount > 0 ? $userId : null,
                    'entry_type' => 'sale',
                    'payment_method' => $paymentAmount > 0 ? $validated['payment_method'] : null,
                    'sale_date' => today(),
                    'invoice_no' => $invoice->invoice_no,
                    'product_name' => Str::limit($productsSummary, 255),
                    'quantity' => collect($lineItems)->sum('quantity'),
                    'unit_price' => $totalQuantity > 0 ? round($subtotal / $totalQuantity, 2) : 0,
                    'total_amount' => $saleAmount,
                    'payment_type' => $invoice->due_amount > 0 ? 'due' : 'full_payment',
                    'payment_amount' => $paymentAmount,
                    'due_amount' => $invoice->due_amount,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $voucherableType = CustomerLedger::class;
                $voucherableId = $customerLedger->id;
            }

            if ($paymentAmount > 0) {
                $customerName = $customer?->name ?? $validated['customer_name'] ?? 'Walk-in Customer';
                VoucherEntry::createWithCode([
                    'voucher_type' => 'credit',
                    'expense_income_type' => $customerName,
                    'voucher_date' => today(),
                    'amount' => number_format($paymentAmount, 2, '.', ''),
                    'reference' => $invoice->invoice_no,
                    'description' => 'Payment received from customer ' . $customerName,
                    'created_by' => $creator?->id,
                    'created_by_name' => $creator?->name ?? 'System',
                    'voucherable_type' => $voucherableType,
                    'voucherable_id' => $voucherableId,
                ]);
            }

            return $invoice;
        });

        $invoice->load(['items.product', 'customer', 'employee']);
        $lowStockProducts = $invoice->items
            ->filter(fn(SaleItem $item) => $item->product && (float) $item->product->unit <= 10)
            ->map(fn(SaleItem $item) => $item->display_product_name)
            ->unique()
            ->values()
            ->all();

        return redirect()->route('sales.invoice', $invoice->id)
            ->with('low_stock_products', $lowStockProducts)
            ->with('auto_print', true);
    }

    public function invoice($id)
    {
        $invoice = SaleInvoice::with([
            'items' => fn($query) => $query->with('product')->withSum('returns as returned_quantity', 'quantity'),
            'customer',
            'employee',
        ])->findOrFail($id);
        $termsConditions = $invoice->terms_conditions ?: self::DEFAULT_TERMS;
        $amountInWords = $this->amountInWords(
            (float) $invoice->due_amount === 0.0
                ? (float) $invoice->amount_paid
                : (float) $invoice->due_amount
        );

        return view('backend.Sales.salesInvoice', compact('invoice', 'termsConditions', 'amountInWords'));
    }

    public function editInvoice($id)
    {
        $invoice = SaleInvoice::with(['items.product', 'customer'])->findOrFail($id);
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('admin')
                || $user?->can('Update_anyday_Invoice')
                || ($user?->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()),
            403
        );

        $restockByProduct = $invoice->items
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn($items) => $items->sum('quantity'));
        $products = Product::query()
            ->where(function ($query) use ($restockByProduct) {
                $query->whereRaw('CAST(unit AS DOUBLE) > 0')
                    ->orWhereIn('id', $restockByProduct->keys());
            })
            ->orderBy('productName')
            ->get();
        $ledger = CustomerLedger::query()->where('sale_invoice_id', $invoice->id)->first();
        $invoices = SaleInvoice::with('employee')->latest('invoice_date')->limit(10)->get();
        $defaultTerms = self::DEFAULT_TERMS;
        $vatPercentage = (float) $invoice->subtotal > (float) $invoice->discount_amount
            ? round((float) $invoice->vat_amount / ((float) $invoice->subtotal - (float) $invoice->discount_amount) * 100, 2)
            : 0;
        $aitPercentage = (float) $invoice->subtotal > (float) $invoice->discount_amount
            ? round((float) $invoice->ait_amount / ((float) $invoice->subtotal - (float) $invoice->discount_amount) * 100, 2)
            : 0;

        return view('backend.Sales.salesIndex', compact(
            'invoice',
            'products',
            'restockByProduct',
            'ledger',
            'invoices',
            'defaultTerms',
            'vatPercentage',
            'aitPercentage'
        ));
    }

    public function updateInvoice(Request $request, $id)
    {
        $validated = $request->validate([
            'discount_type' => 'required|in:amount,percentage',
            'discount_value' => 'required|numeric|min:0',
            'vat_percentage' => 'required|numeric|min:0|max:100',
            'ait_percentage' => 'required|numeric|min:0|max:100',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:' . implode(',', self::PAYMENT_METHODS),
            'notes' => 'nullable|string|max:2000',
            'terms_conditions' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0|max:9999999999999.99',
        ]);

        $invoice = app(SaleInvoiceUpdateService::class)->update(
            (int) $id,
            $validated['items'],
            $validated
        );

        return redirect()->route('sales.invoice', $invoice->id)
            ->with('success', "Invoice {$invoice->invoice_no} was updated; stock, ledger, vouchers, and dashboard totals were synchronized.");
    }

    public function destroy($id)
    {
        $invoiceNo = app(SaleInvoiceVoidService::class)->void((int) $id);

        return redirect()->route('sales.index')
            ->with('success', "Invoice {$invoiceNo} was voided; its stock and payment entries were reversed.");
    }

    public function deliveryChallan($id)
    {
        $invoice = SaleInvoice::with([
            'items' => fn($query) => $query->with('product')->withSum('returns as returned_quantity', 'quantity'),
            'customer',
            'employee',
        ])->findOrFail($id);

        return view('backend.Sales.deliveryChallan', compact('invoice'));
    }

    private function amountInWords(float $amount): string
    {
        $totalPaisa = (int) round($amount * 100);
        $taka = intdiv($totalPaisa, 100);
        $paisa = $totalPaisa % 100;
        $words = $this->numberToWords($taka);
        //if we want to show Take first the use this
        // $words = 'Taka ' . $this->numberToWords($taka);

        if ($paisa > 0) {
            $words .= ' and ' . $this->numberToWords($paisa) . ' Paisa';
        }
        //Here we add Take befor only (Text) If don't need remove the text
        return ucfirst($words) . ' Taka Only.';
    }

    private function numberToWords(int $number): string
    {
        //first Aplbate write in Capital latter for show invoice amout will be Capital Word
        $ones = [
            'Zero',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen',
        ];
        $tens = [
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Forty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $words = $tens[intdiv($number, 10)];

            return $number % 10 ? $words . '-' . $ones[$number % 10] : $words;
        }

        if ($number < 1000) {
            $words = $ones[intdiv($number, 100)] . ' Hundred';

            return $number % 100 ? $words . ' and ' . $this->numberToWords($number % 100) : $words;
        }

        foreach (
            [
                1_000_000_000_000 => 'Trillion',
                1_000_000_000 => 'Billion',
                1_000_000 => 'Million',
                1_000 => 'Thousand',
            ] as $value => $scale
        ) {
            if ($number >= $value) {
                $whole = intdiv($number, $value);
                $remainder = $number % $value;
                $words = $this->numberToWords($whole) . ' ' . $scale;

                return $remainder ? $words . ' ' . $this->numberToWords($remainder) : $words;
            }
        }

        return $ones[0];
    }
}
