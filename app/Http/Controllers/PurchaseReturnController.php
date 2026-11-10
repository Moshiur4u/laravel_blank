<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Supplierledger;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ]);

        return view('backend.PurchaseReturns.create', [
            'initialSupplierId' => $validated['supplier_id'] ?? null,
        ]);
    }

    public function search(Request $request)
    {
        $request->merge(['q' => trim((string) $request->input('q'))]);
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);
        $term = $validated['q'];
        $like = '%'.$term.'%';

        $suppliers = Supplier::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->whereHas('supplierledgers', fn ($query) => $query->whereNotNull('product_id'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone']);

        $purchases = Supplierledger::query()
            ->with(['supplier:id,name', 'product:id,productName,quantity_size'])
            ->whereNotNull('product_id')
            ->whereNotNull('invoice_id')
            ->where('invoice_id', 'like', $like)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'suppliers' => $suppliers->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
            ])->values(),
            'purchases' => $purchases->map(fn (Supplierledger $ledger) => [
                'id' => $ledger->id,
                'invoice_no' => $ledger->invoice_id,
                'supplier_id' => $ledger->supplier_id,
                'supplier_name' => $ledger->supplier?->name ?? 'Unknown supplier',
                'product_name' => $this->productName($ledger),
            ])->values(),
        ]);
    }

    public function supplierHistory(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'invoice_id' => 'nullable|string|max:100',
        ]);
        $supplier = Supplier::query()->findOrFail($validated['supplier_id']);

        $query = Supplierledger::query()
            ->with(['product:id,productName,quantity_size,unit,purchase_price', 'supplier:id,name'])
            ->where('supplier_id', $validated['supplier_id'])
            ->whereNotNull('product_id')
            ->withSum('purchaseReturns as returned_quantity', 'quantity')
            ->withSum('purchaseReturns as returned_amount', 'return_amount')
            ->latest('created_at')
            ->limit(100);

        if (! empty($validated['invoice_id'])) {
            $query->where('invoice_id', $validated['invoice_id']);
        }

        $purchases = $query->get();

        return response()->json([
            'supplier_name' => $supplier->name,
            'purchases' => $purchases->map(fn (Supplierledger $ledger) => $this->purchasePayload($ledger))->values(),
        ]);
    }

    public function purchaseDetails($id)
    {
        $ledger = Supplierledger::query()
            ->with(['product:id,productName,quantity_size,unit,purchase_price', 'supplier:id,name'])
            ->withSum('purchaseReturns as returned_quantity', 'quantity')
            ->withSum('purchaseReturns as returned_amount', 'return_amount')
            ->findOrFail($id);

        return response()->json($this->purchasePayload($ledger));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplierledger_id' => 'required|integer|exists:supplierledgers,id',
            'quantity' => 'required|numeric|min:0.001|decimal:0,3',
            'return_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'reason' => 'nullable|string|max:2000',
        ]);
        $creator = $request->user();

        $purchaseReturn = DB::transaction(function () use ($validated, $creator) {
            $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($validated['supplierledger_id']);
            $productId = filter_var($ledger->product_id, FILTER_VALIDATE_INT);
            $product = $productId
                ? Product::query()->lockForUpdate()->find($productId)
                : null;

            if (! $product || ! is_numeric($ledger->quantity) || ! is_numeric($ledger->total)) {
                throw ValidationException::withMessages([
                    'supplierledger_id' => 'This purchase line is not linked to a returnable inventory product.',
                ]);
            }
            $unitPrice = is_numeric($ledger->price)
                ? (float) $ledger->price
                : (is_numeric($product->purchase_price) ? (float) $product->purchase_price : null);
            if ($unitPrice === null || $unitPrice < 0) {
                throw ValidationException::withMessages([
                    'supplierledger_id' => 'This purchase line has no valid purchase price for calculating the return.',
                ]);
            }
            if (! is_numeric($product->unit)) {
                throw ValidationException::withMessages([
                    'supplierledger_id' => 'The product stock quantity is not numeric and cannot be adjusted.',
                ]);
            }
            if ($validated['return_date'] < $ledger->created_at->toDateString()) {
                throw ValidationException::withMessages([
                    'return_date' => 'The return date cannot be before the original purchase date.',
                ]);
            }

            $quantityMillis = $this->quantityMillis($validated['quantity']);
            $currentQuantityMillis = $this->quantityMillis($ledger->quantity);
            $remainingQuantityMillis = $currentQuantityMillis;
            $stockMillis = $this->quantityMillis($product->unit);

            if ($quantityMillis > $remainingQuantityMillis) {
                throw ValidationException::withMessages([
                    'quantity' => 'The return quantity cannot exceed the remaining purchased quantity.',
                ]);
            }
            if ($quantityMillis > $stockMillis) {
                throw ValidationException::withMessages([
                    'quantity' => 'The return quantity cannot exceed the product quantity currently in stock.',
                ]);
            }

            $currentTotalCents = (int) round((float) $ledger->total * 100);
            $unitPriceCents = (int) round($unitPrice * 100);
            $returnAmountCents = $quantityMillis === $remainingQuantityMillis
                ? $currentTotalCents
                : min(
                    (int) round($unitPriceCents * $quantityMillis / 1000),
                    $currentTotalCents
                );

            $dueCents = max((int) round((float) $ledger->due * 100), 0);
            $paymentCents = max((int) round((float) $ledger->payment * 100), 0);
            $amountAppliedToDueCents = min($returnAmountCents, $dueCents);
            $refundCents = $returnAmountCents - $amountAppliedToDueCents;

            if ($returnAmountCents > $currentTotalCents) {
                throw ValidationException::withMessages([
                    'supplierledger_id' => 'The return value exceeds the remaining purchase value.',
                ]);
            }
            if ($refundCents > $paymentCents) {
                throw ValidationException::withMessages([
                    'supplierledger_id' => 'The supplier refund exceeds the amount paid for this purchase.',
                ]);
            }

            $supplier = Supplier::query()->findOrFail($ledger->supplier_id);
            $invoiceNo = $ledger->invoice_id ?: 'SUP-'.str_pad((string) $ledger->id, 6, '0', STR_PAD_LEFT);
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PRET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'supplierledger_id' => $ledger->id,
                'supplier_id' => $supplier->id,
                'product_id' => $product->id,
                'returned_by' => $creator->id,
                'invoice_no' => $invoiceNo,
                'supplier_name' => $supplier->name,
                'product_name' => $product->productName,
                'quantity' => number_format($quantityMillis / 1000, 3, '.', ''),
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'return_date' => $validated['return_date'],
                'return_amount' => number_format($returnAmountCents / 100, 2, '.', ''),
                'refund_amount' => number_format($refundCents / 100, 2, '.', ''),
                'reason' => $validated['reason'] ?? null,
            ]);

            $ledger->update([
                'quantity' => $this->quantityString($remainingQuantityMillis - $quantityMillis),
                'total' => number_format(($currentTotalCents - $returnAmountCents) / 100, 2, '.', ''),
                'payment' => number_format(($paymentCents - $refundCents) / 100, 2, '.', ''),
                'due' => number_format(($dueCents - $amountAppliedToDueCents) / 100, 2, '.', ''),
            ]);
            $product->unit = $this->quantityString($stockMillis - $quantityMillis);
            $product->save();

            if ($refundCents > 0) {
                VoucherEntry::createWithCode([
                    'voucher_type' => 'credit',
                    'expense_income_type' => 'Supplier Refund ('.$supplier->name.')',
                    'voucher_date' => $validated['return_date'],
                    'amount' => number_format($refundCents / 100, 2, '.', ''),
                    'reference' => $purchaseReturn->return_no,
                    'description' => 'Refund received from supplier '.$supplier->name.' for purchase return '.$invoiceNo,
                    'created_by' => $creator->id,
                    'created_by_name' => $creator->name,
                    'voucherable_type' => PurchaseReturn::class,
                    'voucherable_id' => $purchaseReturn->id,
                ]);
            }

            return $purchaseReturn;
        });

        return redirect()
            ->route('purchase-returns.index', ['supplier_id' => $purchaseReturn->supplier_id])
            ->with('success', "Purchase return {$purchaseReturn->return_no} recorded. Supplier balance and stock were updated.");
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
        ]);
        if (
            ! empty($validated['from_date'])
            && ! empty($validated['to_date'])
            && $validated['to_date'] < $validated['from_date']
        ) {
            throw ValidationException::withMessages([
                'to_date' => 'The end date must be on or after the start date.',
            ]);
        }

        $defaultDate = $validated['from_date'] ?? $validated['to_date'] ?? today()->toDateString();
        $fromDate = $validated['from_date'] ?? $defaultDate;
        $toDate = $validated['to_date'] ?? $defaultDate;
        $returns = PurchaseReturn::query()->whereBetween('return_date', [$fromDate, $toDate]);
        $totalQuantity = (float) (clone $returns)->sum('quantity');
        $totalReturnAmount = (float) (clone $returns)->sum('return_amount');
        $totalRefundAmount = (float) (clone $returns)->sum('refund_amount');
        $returnCount = (clone $returns)->count();
        // Return report-এর Unit কলামের জন্য Product-এর দুই ধরনের unit তথ্য eager-load করা হয়।
        $rows = (clone $returns)
            ->with(['returnedBy', 'product:id,productName,quantity_size,stock_unit'])
            ->latest('return_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('backend.PurchaseReturns.report', compact(
            'fromDate',
            'toDate',
            'totalQuantity',
            'totalReturnAmount',
            'totalRefundAmount',
            'returnCount',
            'rows'
        ));
    }

    private function purchasePayload(Supplierledger $ledger): array
    {
        $returnedQuantity = (float) ($ledger->returned_quantity ?? 0);
        $remainingQuantity = max((float) $ledger->quantity, 0);
        $productName = $this->productName($ledger);

        return [
            'id' => $ledger->id,
            'invoice_no' => $ledger->invoice_id ?: 'SUP-'.str_pad((string) $ledger->id, 6, '0', STR_PAD_LEFT),
            'supplier_id' => $ledger->supplier_id,
            'supplier_name' => $ledger->supplier?->name ?? '',
            'product_name' => $productName,
            'purchased_quantity' => round($remainingQuantity + $returnedQuantity, 3),
            'returned_quantity' => round($returnedQuantity, 3),
            'remaining_quantity' => round($remainingQuantity, 3),
            'stock_quantity' => round((float) ($ledger->product?->unit ?? 0), 3),
            'unit_price' => is_numeric($ledger->price)
                ? (float) $ledger->price
                : (float) ($ledger->product?->purchase_price ?? 0),
            'purchase_date' => $ledger->created_at?->format('d M Y'),
            'returnable' => $ledger->product_id !== null && $remainingQuantity > 0,
        ];
    }

    private function productName(Supplierledger $ledger): string
    {
        return $ledger->product?->display_name ?? 'Product unavailable';
    }

    private function quantityMillis($quantity): int
    {
        return (int) round((float) $quantity * 1000);
    }

    private function quantityString(int $quantityMillis): string
    {
        return rtrim(rtrim(number_format(max($quantityMillis, 0) / 1000, 3, '.', ''), '0'), '.');
    }
}
