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
        $Products = Product::with('productCategory', 'brand')->get();

        return view('backend.Product.product.indexProduct', compact('Products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ProductCategories = ProductCategory::all();
        $brands = Brand::all();

        return view('backend.Product.product.createProduct', compact('brands', 'ProductCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'productName' => 'required',
            'product_categorie_id' => 'required',
            'brand_id' => 'required',
            'price' => 'required',
            'unit' => 'required',
            'imageName' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $Product_image = $request->imageName;
        $extension = $Product_image->extension();
        $Product_Name = $request->productName . '.' . $extension;
        $request->imageName->move(public_path('products/') . $Product_Name);
        $Product_image_save = $Product_Name;

        $product = Product::create([
            'productName' => $request->productName,
            'product_categorie_id' => $request->product_categorie_id,
            'brand_id' => $request->brand_id,
            'price' => $request->price,
            'unit' => $request->unit,
            'img_url' => $Product_image_save
        ]);

        if ($product) {
            flash()->success('Product Added successfully!');
        } else {
            flash()->error('Product Added failed!');
        }
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
    public function edit($id)
    {
        // $product = Product::find($id);
        // $ProductCategories = ProductCategory::latest()->get();
        // $brand = Brand::latest()->get();

        return view('backend.Product.product.editProduct', compact('product', 'ProductCategories', 'brand'));
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
