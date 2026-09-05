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
            'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048'
        ]);
        // ইমেজ ভ্যারিয়েবল তৈরি করা হলো (যদি কোনো ইমেজ না থাকে তবে auto null হয়ে যাবে
        // এবং ডাটাবেজেও null যাবে ও এক্সটেনশন চেক করবেনা না)
        $product_image_name = null;
        // ইমেজ আপলোড করার শর্ত তৈরি করা হলো
        if ($request->hasFile('imageName')) {

            // ইমেজ ভ্যারিয়েবলে রেকুয়েস্ট থেকে ইমেজ নেওয়া হলো
            $image = $request->file('imageName');
            // ইমেজ এক্সটেনশন নেওয়া হলো
            $extension = $image->extension();
            // Photo Rename As par user name
            // $photo_name = Auth::User()->name.".".$extension;
            // শুধু রিক্যেস্ট থেকে ফাইলের নাম নিবে
            $product_image_name = ($request->productName) . '.' . $extension;
            // ইউজার ফোল্ডারে ইমেজ সেভ হবে ও  ফোল্ডার নাম ইউজার
            $image->move(public_path('uploads/products/'), $product_image_name);
        }
        // ডেটা ক্রিয়েট করা হলো
        Product::create([
            'productName' => $request->productName,
            'product_categorie_id' => $request->product_categorie_id,
            'brand_id' => $request->brand_id,
            'price' => $request->price,
            'unit' => $request->unit,
            'img_url' => $product_image_name
        ]);
        // রিডাইরেক্ট করা হলো এবং ফ্ল্যাশ মেসেজ দেখানো হলো
        return redirect()->route('product.index')->with('success', 'Product added successfully!');
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
