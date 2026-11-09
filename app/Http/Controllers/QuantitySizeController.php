<?php

namespace App\Http\Controllers;

use App\Models\QuantitySize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuantitySizeController extends Controller
{
    public function index()
    {
        $quantitySizes = QuantitySize::query()->latest()->get();

        return view('backend.Product.QuantitySize.index', compact('quantitySizes'));
    }

    public function create()
    {
        return view('backend.Product.QuantitySize.create');
    }

    public function store(Request $request)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:quantity_sizes,name'],
        ], [
            'name.unique' => 'This Qty/Size option already exists.',
        ]);

        $quantitySize = QuantitySize::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'quantity_size' => [
                    'id' => $quantitySize->id,
                    'name' => $quantitySize->name,
                ],
            ], 201);
        }

        return redirect()->route('quantity-size.index')
            ->with('success', 'Qty/Size option created successfully.');
    }

    public function edit(QuantitySize $quantitySize)
    {
        return view('backend.Product.QuantitySize.edit', compact('quantitySize'));
    }

    public function update(Request $request, QuantitySize $quantitySize)
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('quantity_sizes', 'name')->ignore($quantitySize->id),
            ],
        ], [
            'name.unique' => 'This Qty/Size option already exists.',
        ]);

        $quantitySize->update($validated);

        return redirect()->route('quantity-size.index')
            ->with('success', 'Qty/Size option updated successfully.');
    }

    public function destroy(QuantitySize $quantitySize)
    {
        $quantitySize->delete();

        return redirect()->route('quantity-size.index')
            ->with('success', 'Qty/Size option deleted. Existing product values were preserved.');
    }
}
