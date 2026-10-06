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
        // $suppliers = Supplier::latest()->get();

        // return view('backend.Supplier.supplierList', compact('suppliers'));    
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

        $supplier = new Supplier();
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phone = $request->phone;
        $supplier->address = $request->address;

        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $extension = $image->extension();
            $logoName = time().'.'.$extension;
            $image->move(public_path('uploads/suppliers'), $logoName);
            $supplier->logo = $logoName;
        }

        $supplier->save();

        return redirect()->route('supplier.index')->with('success', 'Supplier added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
