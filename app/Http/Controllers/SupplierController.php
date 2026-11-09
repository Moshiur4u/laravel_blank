<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::with('supplierledgers')->latest()->get();

        return view('backend.Supplier.supplierList', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.Supplier.addSupplier');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:suppliers,name',
            'email' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048',
        ], [
            'name.unique' => 'This supplier already exists.',
        ]);
        if ($validator->fails() && $request->expectsJson()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        $validator->validate();

        $supplier = new Supplier;
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;

        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $extension = $image->extension();
            $logoName = time().'.'.$extension;
            $image->move(public_path('uploads/suppliers/'), $logoName);
            $supplier->logo = $logoName;
        }

        $supplier->save();

        if ($request->expectsJson()) {
            return response()->json([
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                ],
            ], 201);
        }

        return redirect()->route('supplier.index')->with('success', 'Supplier added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return redirect()->route('supplier.ledger', $id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('backend.Supplier.updateSupplier', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;

        if ($request->hasFile('logo')) {
            if ($supplier->logo && file_exists(public_path('uploads/suppliers/'.$supplier->logo))) {
                @unlink(public_path('uploads/suppliers/'.$supplier->logo));
            }
            $image = $request->file('logo');
            $extension = $image->extension();
            $logoName = time().'.'.$extension;
            $image->move(public_path('uploads/suppliers'), $logoName);
            $supplier->logo = $logoName;
        }

        $supplier->save();

        return redirect()->route('supplier.index')->with('success', 'Supplier updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $hasVouchers = $supplier->supplierledgers()
            ->with('payments')
            ->get()
            ->contains(function (Supplierledger $ledger) use ($supplier) {
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

                return VoucherEntry::existsForSource(
                    $ledger,
                    'debit',
                    'Supplier Payment ('.$supplier->name.')',
                    'Payment to supplier '.$supplier->name,
                    $ledger->invoice_id,
                    $paymentRows
                );
            });

        if ($hasVouchers) {
            return redirect()->route('supplier.index')->with(
                'error',
                'Delete this supplier’s associated vouchers from the Combined Voucher Report before deleting the supplier.'
            );
        }

        if ($supplier->logo && file_exists(public_path('uploads/suppliers/'.$supplier->logo))) {
            @unlink(public_path('uploads/suppliers/'.$supplier->logo));
        }
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier deleted successfully!');
    }

    /**
     * Supplier-কে active/inactive করে; inactive supplier নতুন product-এ নির্বাচন করা যায় না।
     */
    public function toggleStatus($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return redirect()->route('supplier.index')->with(
            'success',
            $supplier->is_active ? 'Supplier activated successfully!' : 'Supplier marked inactive successfully!'
        );
    }
}
