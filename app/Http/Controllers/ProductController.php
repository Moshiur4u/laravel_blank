<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuantitySize;
use App\Models\Supplier;
use App\Models\Supplierledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Products = Product::with('productCategory', 'brand')
            ->whereRaw('CAST(unit AS DOUBLE) > 0')
            ->get();

        return view('backend.Product.product.indexProduct', compact('Products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ProductCategories = ProductCategory::all();
        $brands = Brand::all();
        $quantitySizes = QuantitySize::query()->orderBy('name')->pluck('name');
        $existingProductNames = Product::query()
            ->whereNotNull('productName')
            ->where('productName', '<>', '')
            ->distinct()
            ->orderBy('productName')
            ->pluck('productName');
        // নতুন product-এ শুধু active supplier নির্বাচনযোগ্য রাখা হয়।
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('backend.Product.product.createProduct', compact(
            'brands',
            'ProductCategories',
            'quantitySizes',
            'suppliers',
            'existingProductNames'
        ));
    }

    /**
     * যাচাইকৃত তথ্য দিয়ে product তৈরি করে এবং supplier থাকলে একই সঙ্গে ledger entry লেখে।
     */
    public function store(Request $request)
    {
        $request->validate([
            'productName' => 'nullable',
            'product_categorie_id' => 'nullable',
            'brand_id' => 'nullable',
            'price' => 'nullable',
            // দুটো field-ই একসঙ্গে ঐচ্ছিক; supplier দিলে purchase price-ও দিতে হবে।
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'purchase_price' => 'nullable',
            'quantity_size' => 'nullable|string|max:50',
            // ledger-এর quantity গণনায় সঠিক numeric পরিমাণ নিশ্চিত করা হয়।
            'unit' => 'nullable',
            'purchase_date' => 'nullable|date',
            'menufecher_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
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
        // database transaction product ও তার ledger row-কে একসঙ্গে সফল বা ব্যর্থ রাখে।
        DB::transaction(function () use ($request, $product_image_name) {
            // আগে product সংরক্ষণ করলে ledger-এ তার আসল ID সংযুক্ত করা যায়।
            $product = Product::create([
                'productName' => $request->productName,
                'product_categorie_id' => $request->product_categorie_id,
                'brand_id' => $request->brand_id,
                // price আগের বিক্রয়মূল্য; ক্রয়মূল্য আলাদা কলামে এবং supplier আলাদা সম্পর্ক হিসেবে রাখা হয়।
                'supplier_id' => $request->supplier_id,
                'purchase_price' => $request->purchase_price,
                'price' => $request->price,
                'unit' => $request->unit,
                'quantity_size' => $request->quantity_size,
                'purchase_date' => $request->purchase_date,
                'mfg_date' => $request->menufecher_date,
                'expiry_date' => $request->expire_date,
                'description' => $request->description,
                'img_url' => $product_image_name,
            ]);

            // supplier নির্বাচন ও purchase price থাকলেই supplier ledger-এ ক্রয়ের row তৈরি হয়।
            if ($product->supplier_id !== null && $product->purchase_price !== null) {
                // ledger total হলো প্রতি এককের ক্রয়মূল্য × মোট quantity; প্রাথমিক payment শূন্য, তাই পুরোটা due।
                $ledgerTotal = round((float) $product->purchase_price * (float) $product->unit, 2);

                Supplierledger::create([
                    'supplier_id' => $product->supplier_id,
                    'product_id' => (string) $product->id,
                    'quantity' => (string) $product->unit,
                    'price' => (string) $product->purchase_price,
                    'total' => number_format($ledgerTotal, 2, '.', ''),
                    'payment' => '0.00',
                    'due' => number_format($ledgerTotal, 2, '.', ''),
                ]);
            }
        });

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
        $product = Product::findOrFail($id);
        $ProductCategories = ProductCategory::latest()->get();
        $brands = Brand::all();
        $quantitySizes = QuantitySize::query()->orderBy('name')->pluck('name');
        // $suppliers = Supplier::where('is_active', true)
        //     ->when($product->supplier_id, fn ($query) => $query->orWhereKey($product->supplier_id))
        //     ->orderBy('name')
        //     ->get();
        $suppliers = Supplier::query()
    ->where('is_active', true);

if ($product->supplier_id) {
    $suppliers->orWhere('id', $product->supplier_id);
}

$suppliers = $suppliers->orderBy('name')->get();

        return view('backend.Product.product.editProduct', compact(
            'product',
            'ProductCategories',
            'brands',
            'quantitySizes',
            'suppliers'
        ));
    }

    /**
     * Update the specified resource in storage.
     * পণ্য আপডেটের সময় supplier ledger ও inventory স্বয়ংক্রিয়ভাবে সমন্বয় করা হয়।
     */
    public function update(Request $request, $id)
    {
        // ১. ভ্যালিডেশন
        $request->validate([
            'productName' => 'nullable|string|max:255',
            'product_categorie_id' => 'nullable',
            'brand_id' => 'nullable',
            'supplier_id' => 'nullable',
            'purchase_price' => 'nullable',
            'price' => 'nullable',
            'unit' => 'nullable',
            'quantity_size' => 'nullable|string|max:50',
            'purchase_date' => 'nullable|date',
            'menufecher_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
            'imageName' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // ১০৪৮ KB বাড়িয়ে ২০৪৮ KB করা হলো
        ]);

        // ২. প্রডাক্ট খুঁজে বের করা
        $product = Product::findOrFail($id);
        $hasPurchaseReturns = $product->purchaseLedgers()
            ->whereHas('purchaseReturns')
            ->exists();
        $purchaseReturnSensitiveFieldsChanged = collect(['unit', 'supplier_id', 'purchase_price'])
            ->contains(function (string $field) use ($request, $product): bool {
                $incoming = $request->input($field);
                $current = $product->getAttribute($field);
                if (is_numeric($incoming) && is_numeric($current)) {
                    return (float) $incoming !== (float) $current;
                }

                return (string) $incoming !== (string) $current;
            });
        if ($hasPurchaseReturns && $purchaseReturnSensitiveFieldsChanged) {
            throw ValidationException::withMessages([
                'unit' => 'Stock, supplier, and purchase price cannot be changed after this product has purchase return history.',
            ]);
        }

        if ($request->filled('supplier_id')
            && (int) $request->supplier_id !== (int) $product->supplier_id
            && ! Supplier::whereKey($request->supplier_id)->where('is_active', true)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'supplier_id' => 'Please select an active supplier.',
            ]);
        }

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

        // ৪. DB transaction-এ product ও supplier ledger একসঙ্গে আপডেট করা
        // এর ফলে product ও ledger সবসময় একই অবস্থায় (consistent) থাকে।
        DB::transaction(function () use ($request, $product, $productImageName) {

            // ৪.১ products টেবিল আপডেট — inventory-র stock (unit) এখান থেকেই আসে
            $product->update([
                'productName'          => $request->productName,
                'product_categorie_id' => $request->product_categorie_id,
                'brand_id'             => $request->brand_id,
                'supplier_id'          => $request->supplier_id,
                'purchase_price'       => $request->purchase_price,
                'price'                => $request->price,
                'unit'                 => $request->unit,
                'quantity_size'        => $request->quantity_size,
                'purchase_date'        => $request->purchase_date,
                'mfg_date'             => $request->menufecher_date,
                'expiry_date'          => $request->expire_date,
                'description'          => $request->description,
                'img_url'              => $productImageName,
            ]);

            // ৪.২ Supplier Ledger সমন্বয়:
            // supplier_id ও purchase_price উভয়ই থাকলে ledger sync করা হবে।
            $newSupplierId    = $request->supplier_id;
            $newPurchasePrice = $request->purchase_price;
            $newUnit          = (float) ($request->unit ?? 0);

            if ($newSupplierId && $newPurchasePrice) {

                // নতুন মোট ক্রয়মূল্য হিসাব: price × quantity
                $newTotal = round((float) $newPurchasePrice * $newUnit, 2);

                // এই product-এর সবচেয়ে পুরনো ledger entry খোঁজা
                // (product তৈরির সময় store() মেথড যে entry তৈরি করেছিল)
                $existingLedger = Supplierledger::where('product_id', (string) $product->id)
                    ->oldest()
                    ->first();

                if ($existingLedger) {
                    // ইতোমধ্যে পরিশোধিত অর্থ সংরক্ষণ করা (payment অপরিবর্তিত থাকবে)
                    $paidAmount = (float) $existingLedger->payment;

                    // due = নতুন total - পরিশোধিত অর্থ (কখনো ঋণাত্মক হবে না)
                    $newDue = max(0.0, $newTotal - $paidAmount);

                    // বিদ্যমান ledger entry আপডেট করা
                    $existingLedger->update([
                        'supplier_id' => $newSupplierId,
                        'product_id'  => (string) $product->id,
                        'quantity'    => (string) $newUnit,
                        'price'       => (string) $newPurchasePrice,
                        'total'       => number_format($newTotal, 2, '.', ''),
                        'due'         => number_format($newDue, 2, '.', ''),
                        // payment ইচ্ছাকৃতভাবে অপরিবর্তিত রাখা হয়েছে
                    ]);
                } else {
                    // কোনো ledger entry না থাকলে নতুন entry তৈরি করা
                    Supplierledger::create([
                        'supplier_id' => $newSupplierId,
                        'product_id'  => (string) $product->id,
                        'quantity'    => (string) $newUnit,
                        'price'       => (string) $newPurchasePrice,
                        'total'       => number_format($newTotal, 2, '.', ''),
                        'payment'     => '0.00',
                        'due'         => number_format($newTotal, 2, '.', ''),
                    ]);
                }
            }
            // যদি supplier_id বা purchase_price না থাকে তাহলে ledger-এ কোনো পরিবর্তন করা হয় না।
        });

        return redirect()->route('product.index')->with('success', 'Product Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->img_url && file_exists(public_path('uploads/products/'.$product->img_url))) {
            unlink(public_path('uploads/products/'.$product->img_url));
        }
        $product->delete();
        return redirect()->route('product.index')->with('success', 'Product Deleted Successfully');
    }
}
