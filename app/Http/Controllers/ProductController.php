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
            'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048',
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
            $product_image_name = ($request->productName).'.'.$extension;
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
            'img_url' => $product_image_name,
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
        $product = Product::find($id);
        $ProductCategories = ProductCategory::latest()->get();
        $brands = Brand::all();

        return view('backend.Product.product.editProduct', compact('product', 'ProductCategories', 'brands'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
<<<<<<< HEAD
=======
        // ১. ভ্যালিডেশন
>>>>>>> 95fb4aebcf1c967bbc9c2f09a0b013e13ca29a07
        $request->validate([
            'productName' => 'required',
            'product_categorie_id' => 'required',
            'brand_id' => 'required',
            'price' => 'required',
            'unit' => 'required',
<<<<<<< HEAD
            'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1048'
        ]);
        $product_image = null;
        if ($request->imageName == !null) {
            # code...
        }
=======
            'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // ১০৪৮ KB বাড়িয়ে ২০৪৮ KB করা হলো
        ]);

        // ২. প্রডাক্ট খুঁজে বের করা
        $product = Product::findOrFail($id);

        // ডিফল্টভাবে ডেটাবেজে থাকা বর্তমান ইমেজের নাম রাখা হচ্ছে
        $productImageName = $product->img_url;

        // ৩. নতুন ইমেজ আপলোড চেক করা (ইনপুট নেম 'imageName')
        if ($request->hasFile('imageName')) {

            // পুরাতন ইমেজ ডিলিট করা (যদি ডেটাবেজে থাকে এবং ফাইলে বিদ্যমান থাকে)
            if ($product->img_url && file_exists(public_path('uploads/products/'.$product->img_url))) {
                unlink(public_path('uploads/products/'.$product->img_url));
            }

            // নতুন ইমেজ সেভ করা
            $productImage = $request->file('imageName');
            $extension = $productImage->getClientOriginalExtension();

            // ইউনিক নাম তৈরি করা (প্রডাক্টের নাম + টাইমস্ট্যাম্প)
            $productImageName = time().'_'.str_replace(' ', '_', $request->productName).'.'.$extension;

            // ফাইলটি নির্ধারিত ফোল্ডারে মুভ করা
            $productImage->move(public_path('uploads/products'), $productImageName);
        }

        // ৪. ডেটাবেজ আপডেট করা ($product ক্ষেত্র ব্যবহার করে)
        $product->update([
            'productName' => $request->productName,
            'product_categorie_id' => $request->product_categorie_id,
            'brand_id' => $request->brand_id,
            'price' => $request->price,
            'unit' => $request->unit,
            'img_url' => $productImageName,
        ]);

        return redirect()->route('product.index')->with('success', 'Product Updated Successfully');
>>>>>>> 95fb4aebcf1c967bbc9c2f09a0b013e13ca29a07
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}