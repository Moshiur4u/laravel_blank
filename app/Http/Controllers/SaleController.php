<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    private const PAYMENT_METHODS = ['cash', 'bank', 'nagad', 'bkash', 'rocket', 'upay'];

    public function index()
    {
        $products = Product::query()->orderBy('productName')->get();
        $invoices = SaleInvoice::with('employee')->latest('invoice_date')->limit(10)->get();

        return view('backend.Sales.salesIndex', compact('products', 'invoices'));
    }

    public function customerByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $customer = Customer::with(['ledgers' => function ($query) {
            $query->latest('sale_date')->limit(10);
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
                'ledger' => $customer->ledgers->map(fn (CustomerLedger $ledger) => [
                    'date' => $ledger->sale_date?->format('Y-m-d'),
                    'type' => $ledger->entry_type,
                    'item' => $ledger->product_name,
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
            'discount_amount' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:' . implode(',', self::PAYMENT_METHODS),
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $customerPhone = trim($validated['customer_phone']);
        $paymentAmount = round((float) ($validated['amount_paid'] ?? 0), 2);
        if ($paymentAmount > 0 && empty($validated['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'Select how the payment was received.',
            ]);
        }

        $invoice = DB::transaction(function () use ($validated, $customerPhone, $paymentAmount) {
            // একই পণ্যের একাধিক কার্ট লাইনের মজুত একসাথে যাচাই করতে পণ্যগুলো ID অনুযায়ী lock করা হয়।
            $quantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn ($rows) => $rows->sum(fn ($row) => (int) $row['quantity']));
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
                        'items' => ($product?->productName ?? 'A selected product') . ' does not have enough stock.',
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

            // ডিসকাউন্ট, VAT ও আগের বকেয়া সার্ভারেই হিসাব হয়; ব্রাউজারের পাঠানো total বিশ্বাস করা হয় না।
            $subtotal = 0;
            $lineItems = [];
            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                // Product Create-এর products.price হলো বিক্রয়মূল্য; sale item-এ সেই সময়কার দাম snapshot হিসেবে রাখা হয়।
                $unitPrice = round((float) $product->price, 2);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;
                $lineItems[] = compact('product', 'quantity', 'unitPrice', 'lineTotal');
            }

            $subtotal = round($subtotal, 2);
            $discount = round((float) ($validated['discount_amount'] ?? 0), 2);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The discount cannot be greater than the subtotal.',
                ]);
            }
            $vat = round((float) ($validated['vat_amount'] ?? 0), 2);
            $saleAmount = round($subtotal - $discount + $vat, 2);
            $invoiceTotal = round($saleAmount + ($includePreviousDue ? $previousDue : 0), 2);

            if ($paymentAmount > $invoiceTotal) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment cannot be greater than the invoice total.',
                ]);
            }

            $invoice = SaleInvoice::create([
                'invoice_no' => 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5)),
                'customer_id' => $customer?->id,
                'customer_phone' => $customerPhone,
                'customer_name' => $customer?->name,
                'user_id' => $userId,
                'invoice_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'vat_amount' => $vat,
                'previous_due' => $includePreviousDue ? $previousDue : 0,
                'include_previous_due' => (bool) $includePreviousDue,
                'total_amount' => $invoiceTotal,
                'amount_paid' => $paymentAmount,
                'due_amount' => round($invoiceTotal - $paymentAmount, 2),
                'payment_method' => $paymentAmount > 0 ? $validated['payment_method'] : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lineItems as $lineItem) {
                /** @var Product $product */
                $product = $lineItem['product'];
                $quantity = $lineItem['quantity'];

                SaleItem::create([
                    'sale_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'product_name' => $product->productName,
                    'quantity' => $quantity,
                    'unit_price' => $lineItem['unitPrice'],
                    'line_total' => $lineItem['lineTotal'],
                ]);

                $product->unit = (string) ((float) $product->unit - $quantity);
                $product->save();
            }

            if ($customer) {
                $totalQuantity = collect($lineItems)->sum('quantity');
                $productsSummary = collect($lineItems)
                    ->map(fn ($lineItem) => $lineItem['product']->productName . ' × ' . $lineItem['quantity'])
                    ->implode(', ');

                CustomerLedger::create([
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
            }

            return $invoice;
        });

        $invoice->load(['items.product', 'customer', 'employee']);
        $lowStockProducts = $invoice->items
            ->filter(fn (SaleItem $item) => $item->product && (float) $item->product->unit <= 10)
            ->map(fn (SaleItem $item) => $item->product_name)
            ->unique()
            ->values()
            ->all();

        return redirect()->route('sales.invoice', $invoice->id)
            ->with('low_stock_products', $lowStockProducts)
            ->with('auto_print', true);
    }

    public function invoice($id)
    {
        $invoice = SaleInvoice::with(['items', 'customer', 'employee'])->findOrFail($id);

        return view('backend.Sales.salesInvoice', compact('invoice'));
    }
}
