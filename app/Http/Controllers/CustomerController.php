<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::with(['employee', 'ledgers'])->latest()->get();
        $totalSales = $customers->sum(function ($customer) {
            return $customer->ledgers->sum('total_amount');
        });
        $totalReceived = $customers->sum(function ($customer) {
            return $customer->ledgers->sum('payment_amount');
        });
        $totalDue = $customers->sum(function ($customer) {
            return (float) $customer->opening_due
                + $customer->ledgers->sum('total_amount')
                - $customer->ledgers->sum('payment_amount');
        });

        return view('backend.customer.customerIndex', compact('customers', 'totalSales', 'totalReceived', 'totalDue'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::orderBy('name')->get();

        return view('backend.customer.addCustomer', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:temporary,permanent',
            'employee_id' => 'nullable|required_if:customer_type,permanent|exists:users,id',
            'shop_name' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code',
            'opening_due' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'customer_type' => $request->customer_type,
            'employee_id' => $request->employee_id,
            'shop_name' => $request->shop_name,
            'customer_code' => $request->customer_code,
            'opening_due' => $request->opening_due ?? 0,
            'status' => 1,
            'notes' => $request->notes,
        ]);

        if (! $customer->customer_code) {
            $customer->update([
                'customer_code' => 'CUST-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            ]);
        }

        return redirect()->route('customer.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Display customer ledger.
     */
    public function ledger($id)
    {
        $customer = Customer::with([
            'employee',
            'ledgers' => function ($query) {
                $query->with(['receiver', 'employee'])->latest();
            },
        ])->findOrFail($id);

        $employees = User::orderBy('name')->get();
        $totalSales = $customer->ledgers->sum('total_amount');
        $totalPayment = $customer->ledgers->sum('payment_amount');
        $totalDue = (float) $customer->opening_due
            + $customer->ledgers->sum('total_amount')
            - $customer->ledgers->sum('payment_amount');

        return view('backend.customer.customerLedger', compact('customer', 'employees', 'totalSales', 'totalPayment', 'totalDue'));
    }

    /**
     * Store a customer ledger entry.
     */
    public function ledgerStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'entry_type' => 'required|in:sale,payment',
            'sale_date' => 'required|date',
            'product_name' => 'required_if:entry_type,sale|nullable|string|max:255',
            'quantity' => 'required_if:entry_type,sale|nullable|integer|min:1',
            'unit_price' => 'required_if:entry_type,sale|nullable|numeric|min:0',
            'payment_type' => 'required_if:entry_type,sale|nullable|in:cash,due,full_payment',
            'payment_amount' => 'required_if:entry_type,payment|nullable|numeric|min:0.01',
            'employee_id' => 'nullable|exists:users,id',
            'received_by' => 'required_if:entry_type,payment|nullable|exists:users,id',
            'invoice_no' => 'nullable|string|max:100',
            'paid_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $entryType = $validated['entry_type'];
        $quantity = $entryType === 'sale' ? (int) $validated['quantity'] : 0;
        $unitPrice = $entryType === 'sale' ? (float) $validated['unit_price'] : 0;
        $total = round($unitPrice * $quantity, 2);
        $paymentType = $entryType === 'payment' ? 'cash' : $validated['payment_type'];
        $paymentAmount = (float) ($validated['payment_amount'] ?? 0);
        $dueAmount = 0;

        // বিক্রির ক্ষেত্রে নগদ/পূর্ণ পরিশোধ পুরো টাকা জমা করে; বাকিতে আংশিক জমা বাদে বাকিটা বকেয়া হয়।
        if ($entryType === 'sale') {
            if (in_array($paymentType, ['cash', 'full_payment'], true)) {
                $paymentAmount = $total;
            } else {
                $paymentAmount = min($paymentAmount, $total);
            }
            $dueAmount = round($total - $paymentAmount, 2);
        }

        // প্রতিটি লেজার সারিতে বিক্রি (debit) ও আদায় (credit) রাখা হয়; মোট বকেয়া = আগের বকেয়া + বিক্রি - আদায়।
        DB::transaction(function () use ($validated, $customer, $entryType, $quantity, $unitPrice, $total, $paymentType, $paymentAmount, $dueAmount) {
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'employee_id' => $validated['employee_id'] ?? $customer->employee_id,
                'received_by' => $validated['received_by'] ?? null,
                'entry_type' => $entryType,
                'sale_date' => $validated['sale_date'],
                'invoice_no' => $validated['invoice_no'] ?? null,
                'product_name' => $validated['product_name'] ?? null,
                'paid_by' => $validated['paid_by'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'payment_type' => $paymentType,
                'payment_amount' => $paymentAmount,
                'due_amount' => $dueAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('customer.ledger', $customer->id)->with('success', 'Ledger entry saved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $employees = User::orderBy('name')->get();

        return view('backend.customer.addCustomer', compact('customer', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:temporary,permanent',
            'employee_id' => 'nullable|required_if:customer_type,permanent|exists:users,id',
            'shop_name' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code,' . $customer->id,
            'opening_due' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'customer_type' => $request->customer_type,
            'employee_id' => $request->employee_id,
            'shop_name' => $request->shop_name,
            'customer_code' => $request->customer_code ?? $customer->customer_code,
            'opening_due' => $request->opening_due ?? 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('customer.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customer.index')->with('success', 'Customer deleted successfully.');
    }
}
