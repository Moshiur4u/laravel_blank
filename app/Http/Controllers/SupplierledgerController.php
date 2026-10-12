<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierledgerController extends Controller
{
    /**
     * Display supplier details with all ledger entries.
     */
    public function index($supplier_id)
    {
        $supplier = Supplier::findOrFail($supplier_id);
        $ledgers = Supplierledger::with(['product', 'payments'])
            ->where('supplier_id', $supplier_id)
            ->latest()
            ->get();
        $products = Product::all();

        // Calculate summary totals
        $totalAmount = $ledgers->sum('total');
        $totalPayment = $ledgers->sum('payment');
        $totalDue = $ledgers->sum('due');

        return view('backend.Supplier.supplierledger', compact(
            'supplier',
            'ledgers',
            'products',
            'totalAmount',
            'totalPayment',
            'totalDue'
        ));
    }

    /**
     * নির্দিষ্ট লেজার এন্ট্রির তথ্য দিয়ে প্রিন্টযোগ্য সাপ্লায়ার ইনভয়েস দেখায়।
     */
    public function invoice($id)
    {
        // supplier, product ও payment history একসঙ্গে আনা হয়, যেন invoice view-তে query বারবার না চলে।
        $ledger = Supplierledger::with(['supplier', 'product', 'payments'])->findOrFail($id);

        // invoice_id না থাকলে ledger ID দিয়ে স্থায়ী display number বানানো হয়, database-এ কিছু লেখা হয় না।
        $invoiceNumber = $ledger->invoice_id ?: 'SUP-' . str_pad((string) $ledger->id, 6, '0', STR_PAD_LEFT);

        // invoice-এ শুধু পড়ার জন্য ledger ও তৈরি করা display number পাঠানো হয়।
        return view('backend.Supplier.supplierInvoice', compact('ledger', 'invoiceNumber'));
    }

    /**
     * Store a newly created ledger entry in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_id' => 'nullable|string',
            'product_id' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'payment' => 'nullable|numeric|min:0',
            'due' => 'nullable|numeric|min:0',
        ]);

        Supplierledger::create([
            'supplier_id' => $request->supplier_id,
            'invoice_id' => $request->invoice_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'total' => $request->total,
            'payment' => $request->payment,
            'due' => $request->due,
        ]);

        return redirect()->route('supplier.ledger', $request->supplier_id)
            ->with('success', 'Ledger entry added successfully!');
    }

    /**
     * Show the form for editing the specified ledger entry.
     */
    public function edit($id)
    {
        $ledger = Supplierledger::findOrFail($id);
        $supplier = Supplier::findOrFail($ledger->supplier_id);
        $products = Product::all();
        $ledgers = Supplierledger::with(['product', 'payments'])
            ->where('supplier_id', $ledger->supplier_id)
            ->latest()
            ->get();

        $totalAmount = $ledgers->sum('total');
        $totalPayment = $ledgers->sum('payment');
        $totalDue = $ledgers->sum('due');

        return view('backend.Supplier.supplierledger', compact(
            'supplier',
            'ledgers',
            'products',
            'totalAmount',
            'totalPayment',
            'totalDue',
            'ledger'
        ));
    }

    /**
     * Update the specified ledger entry in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|string',
            'product_id' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
        ]);

        // payment record-এর সঙ্গে মিল রাখতে ledger-এর financial update row lock-এর মধ্যে করা হয়।
        $ledger = DB::transaction(function () use ($validated, $id) {
            $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($id);
            $paidInCents = (int) round((float) $ledger->payment * 100);
            $totalInCents = (int) round((float) ($validated['total'] ?? 0) * 100);

            // আগে পরিশোধিত অঙ্কের নিচে মোট কমালে due ঋণাত্মক হবে, তাই ledger update থামানো হয়।
            if ($totalInCents < $paidInCents) {
                throw ValidationException::withMessages([
                    'total' => 'Total cannot be less than the amount already paid.',
                ]);
            }

            $ledger->update([
                'invoice_id' => $validated['invoice_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'quantity' => $validated['quantity'] ?? null,
                'price' => $validated['price'] ?? null,
                'total' => $validated['total'] ?? null,
                // payment অপরিবর্তিত থাকে; নতুন payment শুধু payment entry action দিয়েই যোগ হয়।
                'due' => number_format(($totalInCents - $paidInCents) / 100, 2, '.', ''),
            ]);

            return $ledger;
        });

        return redirect()->route('supplier.ledger', $ledger->supplier_id)
            ->with('success', 'Ledger entry updated successfully!');
    }

    /**
     * তারিখ ও পরিমাণসহ supplier payment সংরক্ষণ করে ledger-এর paid/due totals সমন্বয় করে।
     */
    public function storePayment(Request $request, $id)
    {
        // payment date ভবিষ্যৎ হতে পারে না এবং প্রতিটি payment শূন্যের বেশি হতে হবে।
        $validated = $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // একই ledger-এ একযোগে payment এলে lock ব্যবহার করে due অতিক্রম করা ঠেকানো হয়।
        $supplierId = DB::transaction(function () use ($validated, $id) {
            $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($id);
            $amountInCents = (int) round((float) $validated['amount'] * 100);
            $dueInCents = (int) round((float) $ledger->due * 100);

            // বর্তমান বকেয়ার বেশি payment গ্রহণ করা হয় না।
            if ($amountInCents > $dueInCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the current due.',
                ]);
            }

            // payment history row-তে তারিখ ও payment-এর প্রকৃত অঙ্ক আলাদা করে রাখা হয়।
            SupplierLedgerPayment::create([
                'supplierledger_id' => $ledger->id,
                'payment_date' => $validated['payment_date'],
                'amount' => number_format($amountInCents / 100, 2, '.', ''),
            ]);

            $paidInCents = (int) round((float) $ledger->payment * 100) + $amountInCents;
            $remainingDueInCents = $dueInCents - $amountInCents;

            // ledger summary-তে payment যোগ এবং due বিয়োগ করে পুরোনো amount/due columns সচল রাখা হয়।
            $ledger->update([
                'payment' => number_format($paidInCents / 100, 2, '.', ''),
                'due' => number_format($remainingDueInCents / 100, 2, '.', ''),
            ]);

            return $ledger->supplier_id;
        });

        return redirect()->route('supplier.ledger', $supplierId)
            ->with('success', 'Supplier payment recorded successfully!');
    }

    /**
     * Remove the specified ledger entry from storage.
     */
    public function destroy($id)
    {
        $ledger = Supplierledger::findOrFail($id);
        $supplierId = $ledger->supplier_id;
        $ledger->delete();

        return redirect()->route('supplier.ledger', $supplierId)
            ->with('success', 'Ledger entry deleted successfully!');
    }
}
