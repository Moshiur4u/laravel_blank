<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Supplierledger;
use Illuminate\Http\Request;

class SupplierledgerController extends Controller
{
    /**
     * Display supplier details with all ledger entries.
     */
    public function index($supplier_id)
    {
        $supplier = Supplier::findOrFail($supplier_id);
        $ledgers = Supplierledger::with('product')
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
        $ledgers = Supplierledger::with('product')
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
        $request->validate([
            'invoice_id' => 'nullable|string',
            'product_id' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'payment' => 'nullable|numeric|min:0',
            'due' => 'nullable|numeric|min:0',
        ]);

        $ledger = Supplierledger::findOrFail($id);
        $ledger->update([
            'invoice_id' => $request->invoice_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'total' => $request->total,
            'payment' => $request->payment,
            'due' => $request->due,
        ]);

        return redirect()->route('supplier.ledger', $ledger->supplier_id)
            ->with('success', 'Ledger entry updated successfully!');
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
