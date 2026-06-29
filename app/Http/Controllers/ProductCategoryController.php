<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\Input;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ProductCategories = ProductCategory::latest()->get();
        // return view('frontend.product.productCategoryindex',compact('ProductCategories'));
        return view('backend.Product.Category.productCategoryCreate',compact('ProductCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ProductCategories = ProductCategory::latest()->get();
        return view('backend.Product.Category.productCategoryCreate',compact('ProductCategories'));
        // return view('frontend.product.CategoryCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'category_name'=>'required'
        ]);
        ProductCategory::create(['category_name'=>$request->input('category_name')]);
        return redirect()->route('category.index');
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $CategoryEdit=ProductCategory::find($id);
        return view('backend.Product.Category.Categoryedit',compact('CategoryEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $request->validate([
            'category_name'=>'required'
        ]);

        $ProductCategory =ProductCategory::find($id);
        $ProductCategory->update($request->all());
        // Method two
        // $ProductCategory->category_name =$request->input('category_name');
        // $ProductCategory->save();
        return redirect()->route('category.index');
        // return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ProductCategory::find($id)->delete();
        sweetalert()->success('Role Delete Successfully.');
       return back();
    }
}
