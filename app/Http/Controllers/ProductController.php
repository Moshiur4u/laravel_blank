<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Products = Product::with(['brand','productCategory'])->get();
        return view('backend.Product.product.indexProduct',compact('Products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ProductCategories = ProductCategory::all();
        $brands = Brand::all();
        return view('backend.Product.product.createProduct',compact('brands','ProductCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
       $validated = $request->validate([
            'productName'=>'required|unique:products,productName',
            'product_categorie_id'=>'required',
            'brand_id'=>'required',
            'price'=>'required',
            'quantity'=>'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
         if ($request->hasFile('image')) {
        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('\productImage'), $imageName);
        $validated['image'] = $imageName;
    }
        Product::createOrFirst($request->all($validated));
        // Product::create($validated);
        return redirect()->route('product.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('backend.Product.product.editProduct');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
