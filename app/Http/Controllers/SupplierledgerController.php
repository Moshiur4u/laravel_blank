<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\VoucherEntry;
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
        $ledger = Supplierledger::with(['supplier', 'product', 'payments.createdBy'])->findOrFail($id);

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

        $creator = $request->user();

        DB::transaction(function () use ($request, $creator) {
            $ledger = Supplierledger::create([
                'supplier_id' => $request->supplier_id,
                'invoice_id' => $request->invoice_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'total' => $request->total,
                'payment' => $request->payment,
                'due' => $request->due,
            ]);

            if ((float) $ledger->payment > 0) {
                $supplier = Supplier::findOrFail($ledger->supplier_id);
                VoucherEntry::createWithCode([
                    'voucher_type' => 'debit',
                    'expense_income_type' => 'Supplier Payment ('.$supplier->name.')',
                    'voucher_date' => today(),
                    'amount' => number_format((float) $ledger->payment, 2, '.', ''),
                    'reference' => $ledger->invoice_id,
                    'description' => 'Payment to supplier '.$supplier->name,
                    'created_by' => $creator?->id,
                    'created_by_name' => $creator?->name ?? 'System',
                    'voucherable_type' => Supplierledger::class,
                    'voucherable_id' => $ledger->id,
                ]);
            }
        });

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
        $creator = $request->user();
        $supplierId = DB::transaction(function () use ($validated, $id, $creator) {
            $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($id);
            $supplier = Supplier::findOrFail($ledger->supplier_id);
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
                'created_by' => $creator?->id,
            ]);

            VoucherEntry::createWithCode([
                'voucher_type' => 'debit',
                'expense_income_type' => 'Supplier Payment ('.$supplier->name.')',
                'voucher_date' => $validated['payment_date'],
                'amount' => number_format($amountInCents / 100, 2, '.', ''),
                'reference' => $ledger->invoice_id,
                'description' => 'Payment to supplier '.$supplier->name,
                'created_by' => $creator?->id,
                'created_by_name' => $creator?->name ?? 'System',
                'voucherable_type' => Supplierledger::class,
                'voucherable_id' => $ledger->id,
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
     * একটি supplier payment receipt edit করে ledger balance ও সংশ্লিষ্ট debit voucher মিলিয়ে দেয়।
     */
    public function updatePayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $supplierId = DB::transaction(function () use ($validated, $paymentId) {
            $payment = SupplierLedgerPayment::query()->lockForUpdate()->findOrFail($paymentId);
            $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($payment->supplierledger_id);
            $supplier = Supplier::findOrFail($ledger->supplier_id);
            $oldAmountCents = (int) round((float) $payment->amount * 100);
            $newAmountCents = (int) round((float) $validated['amount'] * 100);
            $dueCents = (int) round((float) $ledger->due * 100);

            if ($newAmountCents > $dueCents + $oldAmountCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the current due plus this receipt.',
                ]);
            }

            $voucher = VoucherEntry::query()
                ->where('voucherable_type', Supplierledger::class)
                ->where('voucherable_id', $ledger->id)
                ->where('voucher_type', 'debit')
                ->whereDate('voucher_date', $payment->payment_date->toDateString())
                ->where('amount', number_format($oldAmountCents / 100, 2, '.', ''))
                ->orderBy('id')
                ->first();

            if (! $voucher) {
                $voucher = VoucherEntry::query()
                    ->whereNull('voucherable_type')
                    ->whereNull('voucherable_id')
                    ->where('voucher_type', 'debit')
                    ->where('expense_income_type', 'Supplier Payment ('.$supplier->name.')')
                    ->where('description', 'Payment to supplier '.$supplier->name)
                    ->where('reference', $ledger->invoice_id)
                    ->whereDate('voucher_date', $payment->payment_date->toDateString())
                    ->where('amount', number_format($oldAmountCents / 100, 2, '.', ''))
                    ->orderBy('id')
                    ->firstOrFail();
            }

            $voucher->update([
                'voucher_date' => $validated['payment_date'],
                'amount' => number_format($newAmountCents / 100, 2, '.', ''),
            ]);
            $payment->update([
                'payment_date' => $validated['payment_date'],
                'amount' => number_format($newAmountCents / 100, 2, '.', ''),
            ]);
            $ledger->update([
                'payment' => number_format(((int) round((float) $ledger->payment * 100) - $oldAmountCents + $newAmountCents) / 100, 2, '.', ''),
                'due' => number_format(($dueCents + $oldAmountCents - $newAmountCents) / 100, 2, '.', ''),
            ]);

            return $ledger->supplier_id;
        });

        return redirect()->route('supplier.ledger', $supplierId)
            ->with('success', 'Supplier payment updated successfully!');
    }

    /**
     * Remove the specified ledger entry from storage.
     */
    public function destroy($id)
    {
        $supplierId = DB::transaction(function () use ($id) {
            $ledger = Supplierledger::with('payments')->findOrFail($id);
            $supplier = Supplier::findOrFail($ledger->supplier_id);
            $supplierId = $ledger->supplier_id;
            $paymentRows = $ledger->payments
                ->map(fn (SupplierLedgerPayment $payment) => [
                    'date' => $payment->payment_date->toDateString(),
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                ])
                ->all();
            $initialPayment = round(
                (float) $ledger->payment - $ledger->payments->sum(fn (SupplierLedgerPayment $payment) => (float) $payment->amount),
                2
            );

            if ($initialPayment > 0) {
                $paymentRows[] = [
                    'date' => $ledger->created_at->toDateString(),
                    'amount' => number_format($initialPayment, 2, '.', ''),
                ];
            }

            VoucherEntry::deleteForSource(
                $ledger,
                'debit',
                'Supplier Payment ('.$supplier->name.')',
                'Payment to supplier '.$supplier->name,
                $ledger->invoice_id,
                $paymentRows
            );
            $ledger->delete();

            return $supplierId;
        });

        return redirect()->route('supplier.ledger', $supplierId)
            ->with('success', 'Ledger entry deleted successfully!');
    }
}
