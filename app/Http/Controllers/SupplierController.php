<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

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
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048',
        ]);

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
        if ($supplier->logo && file_exists(public_path('uploads/suppliers/'.$supplier->logo))) {
            @unlink(public_path('uploads/suppliers/'.$supplier->logo));
        }
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier deleted successfully!');
    }
}
