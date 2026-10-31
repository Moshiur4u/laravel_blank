<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    /**
     * Display a listing of quotations.
     */
    public function index()
    {
        $quotations = Quotation::with('user', 'items')
            ->latest('quotation_date')
            ->latest('id')
            ->get();

        return view('backend.quotation.index', compact('quotations'));
    }

    /**
     * Show the form for creating a new quotation.
     */
    public function create()
    {
        $products = Product::query()->orderBy('productName')->get();
        $organization = Organization::query()->find(1);

        // Generate next quotation number format: QT-YYYYMM-0001
        $currentMonth = Carbon::now()->format('Ym');
        $latestQuotation = Quotation::where('quotation_no', 'like', "QT-{$currentMonth}-%")
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($latestQuotation) {
            $parts = explode('-', $latestQuotation->quotation_no);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $nextNumber = (int)$parts[2] + 1;
            }
        }
        $generatedQuotationNo = "QT-{$currentMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $defaultTerms = "1. This quotation is valid for 15 days from the date of issue.\n2. Prices are inclusive/exclusive of applicable taxes as indicated.\n3. Delivery will be scheduled upon confirmation of the order.\n4. Payment terms: 50% advance, balance upon delivery.";

        return view('backend.quotation.create', compact('products', 'organization', 'generatedQuotationNo', 'defaultTerms'));
    }

    /**
     * Find a saved customer by exact name or phone for the quotation form.
     */
    public function customerLookup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required_without:phone|nullable|string|max:255',
            'phone' => 'required_without:name|nullable|string|max:50',
        ]);

        $query = Customer::query();

        if (! empty($validated['phone'])) {
            $query->where('phone', trim($validated['phone']));
        } else {
            $query->whereRaw('LOWER(name) = LOWER(?)', [trim($validated['name'])]);
        }

        $matches = $query->limit(2)->get(['name', 'phone', 'address']);
        if ($matches->count() !== 1) {
            return response()->json([
                'found' => false,
                'ambiguous' => $matches->count() > 1,
            ]);
        }

        return response()->json([
            'found' => true,
            'customer' => $matches->first(),
        ]);
    }

    /**
     * Store a newly created quotation in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_address' => 'nullable|string|max:1000',
            'quotation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:flat,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0',
            'ait_percentage' => 'nullable|numeric|min:0|max:100',
            'terms_conditions' => 'nullable|string|max:3000',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($validated) {
            // Generate unique quotation number
            $currentMonth = Carbon::parse($validated['quotation_date'])->format('Ym');
            $latestQuotation = Quotation::where('quotation_no', 'like', "QT-{$currentMonth}-%")
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $nextNumber = 1;
            if ($latestQuotation) {
                $parts = explode('-', $latestQuotation->quotation_no);
                if (isset($parts[2]) && is_numeric($parts[2])) {
                    $nextNumber = (int)$parts[2] + 1;
                }
            }
            $quotationNo = "QT-{$currentMonth}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Compute subtotal from valid rows
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $lineTotal = round($qty * $unitPrice, 2);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => !empty($item['product_id']) ? $item['product_id'] : null,
                    'product_name' => trim($item['product_name']),
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($itemsData)) {
                throw ValidationException::withMessages(['items' => 'Please add at least one valid product item.']);
            }

            // Compute discount
            $discountType = $validated['discount_type'] ?? 'flat';
            $discountValue = (float)($validated['discount_value'] ?? 0);
            $discountAmount = 0;

            if ($discountType === 'percentage') {
                $discountAmount = round(($subtotal * $discountValue) / 100, 2);
            } else {
                $discountAmount = min(round($discountValue, 2), $subtotal);
            }

            // Compute VAT
            $vatPercentage = (float)($validated['vat_percentage'] ?? 0);
            $taxableSubtotal = max($subtotal - $discountAmount, 0);
            $vatAmount = round(($taxableSubtotal * $vatPercentage) / 100, 2);
            $aitPercentage = (float)($validated['ait_percentage'] ?? 0);
            $aitAmount = round(($taxableSubtotal * $aitPercentage) / 100, 2);

            $grandTotal = round($taxableSubtotal + $vatAmount + $aitAmount, 2);

            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'quotation_date' => $validated['quotation_date'],
                'customer_name' => trim($validated['customer_name']),
                'customer_phone' => trim($validated['customer_phone']),
                'customer_address' => trim($validated['customer_address'] ?? ''),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'vat_percentage' => $vatPercentage,
                'vat_amount' => $vatAmount,
                'ait_percentage' => $aitPercentage,
                'ait_amount' => $aitAmount,
                'total_amount' => $grandTotal,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            foreach ($itemsData as $data) {
                $quotation->items()->create($data);
            }

            return redirect()->route('quotation.show', $quotation->id)
                ->with('success', 'Quotation generated successfully.');
        });
    }

    /**
     * Display the specified quotation document.
     */
    public function show($id)
    {
        $quotation = Quotation::with('items', 'user')->findOrFail($id);
        $organization = Organization::query()->find(1);

        return view('backend.quotation.show', compact('quotation', 'organization'));
    }

    /**
     * Remove the specified quotation from storage.
     */
    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);
        $quotation->delete();

        return redirect()->route('quotation.index')
            ->with('success', 'Quotation deleted successfully.');
    }
}
