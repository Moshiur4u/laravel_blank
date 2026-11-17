<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductSubcategoryController extends Controller
{
    public function index(): View
    {
        $subcategories = ProductSubcategory::query()
            ->with('category')
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('backend.Product.Subcategory.index', compact('subcategories'));
    }

    public function create(Request $request): View
    {
        $categories = ProductCategory::query()->orderBy('category_name')->get();
        $selectedCategoryId = $request->integer('category_id');
        $isModal = $request->ajax();
        $view = $isModal
            ? 'backend.Product.Subcategory._form'
            : 'backend.Product.Subcategory.create';

        return view($view, compact('categories', 'selectedCategoryId', 'isModal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_subcategories', 'name')
                    ->where(fn ($query) => $query->where('category_id', $request->input('category_id'))),
            ],
        ]);

        $subcategory = ProductSubcategory::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'subcategory' => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'category_id' => $subcategory->category_id,
                ],
            ], 201);
        }

        return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function edit(ProductSubcategory $subcategory): View
    {
        $categories = ProductCategory::query()->orderBy('category_name')->get();

        return view('backend.Product.Subcategory.edit', compact('subcategory', 'categories'));
    }

    public function update(Request $request, ProductSubcategory $subcategory)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_subcategories', 'name')
                    ->where(fn ($query) => $query->where('category_id', $request->input('category_id')))
                    ->ignore($subcategory->id),
            ],
        ]);

        DB::transaction(function () use ($subcategory, $validated): void {
            $subcategory->update($validated);
            $subcategory->products()->update([
                'product_categorie_id' => $validated['category_id'],
            ]);
        });

        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(ProductSubcategory $subcategory)
    {
        $subcategory->delete();

        return redirect()->route('subcategories.index')->with('success', 'Subcategory deleted successfully.');
    }

    public function options(ProductCategory $category): JsonResponse
    {
        return response()->json([
            'subcategories' => $category->subcategories()
                ->orderBy('name')
                ->get(['id', 'category_id', 'name']),
        ]);
    }
}
