<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\Console\Input\Input;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $ProductCategories = ProductCategory::latest()->get();
        return view('backend.Product.Category.ProductCategoryindex', compact('ProductCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ProductCategories = ProductCategory::latest()->get();
        return view('backend.Product.Category.ProductCategoryCreate', compact('ProductCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|unique:product_categories,category_name',
        ], [
            'category_name.unique' => 'This product category already exists.',
        ]);
        if ($validator->fails() && $request->expectsJson()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        $validator->validate();

        $productCategory = ProductCategory::create([
            'category_name' => $request->input('category_name'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'category' => [
                    'id' => $productCategory->id,
                    'category_name' => $productCategory->category_name,
                ],
            ], 201);
        }

        return redirect()->route('category.create');
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
        $CategoryEdit = ProductCategory::find($id);
        return view('backend.Product.Category.Categoryedit', compact('CategoryEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => [
                'required',
                Rule::unique('product_categories', 'category_name')->ignore($id),
            ],
        ], [
            'category_name.unique' => 'The name has already been taken.',
        ]);

        $ProductCategory = ProductCategory::find($id);
        $ProductCategory->update($request->all());
        // Method two
        // $ProductCategory->category_name =$request->input('category_name');
        // $ProductCategory->save();
        return redirect()->route('category.create');
        // return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        ProductCategory::find($id)->delete();
        sweetalert()->success('Category Delete Successfully.');
        return back();
    }
}
