<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Brans = Brand::latest()->get();
        return view ('backend.Product.Brand.brandIndex',compact('Brans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view ('backend.Product.Brand.createBrand');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'=> 'required|unique:brands,name'
        ]);
        $brandNames = Brand::create(['name'=>$request->input('name')] );
        $brandNames->save();
        return redirect()->route('brand.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $brand = Brand::find($id);
       return view ('backend.product.brand.editBrand',compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'=>'required|unique:brands,name'
        ]);
        $Brands = Brand::find($id);
        // Brand::updated([
        //     'name'=>$request->input('name'),
        // ]);
        $Brands->update($request->all());
        flash()->success('Brand Successfully Update');
        return redirect()->route('brand.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Brand::find($id)->delete();
        sweetalert()->success('Brand Delete Successfully.');
        return redirect()->route('brand.index');
    }
}
