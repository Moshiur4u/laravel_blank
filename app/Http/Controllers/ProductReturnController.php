<?php

namespace App\Http\Controllers;

use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductReturnController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|integer|exists:sale_invoices,id',
        ]);

        return view('backend.ProductReturns.create', [
            'initialInvoiceId' => $validated['invoice_id'] ?? null,
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

        $customerInvoices = SaleInvoice::query()
            ->where(function ($query) use ($like, $term) {
                $query->where('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like);
                if (stripos('Walk-in Customer', $term) !== false) {
                    $query->orWhere(function ($walkInQuery) {
                        $walkInQuery->whereNull('customer_id')
                            ->whereNull('customer_name')
                            ->whereNull('customer_phone');
                    });
                }
            })
            ->latest('invoice_date')
            ->limit(40)
            ->get(['id', 'customer_id', 'customer_name', 'customer_phone']);

        $customers = $customerInvoices
            ->unique(fn (SaleInvoice $invoice) => $invoice->customer_id
                ? 'customer:'.$invoice->customer_id
                : 'snapshot:'.strtolower(trim((string) $invoice->customer_phone)).'|'.strtolower(trim((string) $invoice->customer_name)))
            ->map(fn (SaleInvoice $invoice) => [
                'customer_id' => $invoice->customer_id,
                'name' => $invoice->customer_name ?: 'Walk-in Customer',
                'phone' => $invoice->customer_phone,
            ])
            ->values();

        $invoices = SaleInvoice::query()
            ->where('invoice_no', 'like', $like)
            ->latest('invoice_date')
            ->limit(10)
            ->get(['id', 'invoice_no', 'customer_id', 'customer_name', 'customer_phone', 'invoice_date']);

        return response()->json([
            'customers' => $customers,
            'invoices' => $invoices->map(fn (SaleInvoice $invoice) => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'customer_name' => $invoice->customer_name ?: 'Walk-in Customer',
                'customer_phone' => $invoice->customer_phone,
                'customer_id' => $invoice->customer_id,
                'invoice_date' => $invoice->invoice_date?->format('d M Y, h:i A'),
            ])->values(),
        ]);
    }

    public function customerHistory(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'phone' => 'nullable|string|max:30',
            'name' => 'nullable|string|max:255|required_without_all:customer_id,phone',
        ]);

        $query = SaleInvoice::query();
        if (! empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        } elseif (! empty($validated['phone'])) {
            $query->whereNull('customer_id')
                ->where('customer_phone', trim($validated['phone']));
            if (! empty($validated['name'])) {
                $query->where('customer_name', $validated['name']);
            }
        } else {
            $name = trim($validated['name']);
            $query->whereNull('customer_id')
                ->where(function ($nameQuery) use ($name) {
                    $nameQuery->where('customer_name', $name);
                    if ($name === 'Walk-in Customer') {
                        $nameQuery->orWhereNull('customer_name');
                    }
                });
        }

        $invoices = $query
            ->with(['items' => fn ($itemQuery) => $itemQuery->with('product')->withSum('returns as returned_quantity', 'quantity')])
            ->latest('invoice_date')
            ->limit(50)
            ->get();

        return response()->json([
            'invoices' => $invoices->map(fn (SaleInvoice $invoice) => $this->invoicePayload($invoice))->values(),
        ]);
    }

    public function invoiceDetails($id)
    {
        $invoice = SaleInvoice::query()
            ->with(['items' => fn ($query) => $query->with('product')->withSum('returns as returned_quantity', 'quantity')])
            ->findOrFail($id);

        return response()->json($this->invoicePayload($invoice));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_item_id' => 'required|integer|exists:sale_items,id',
            'quantity' => 'required|integer|min:1',
            'return_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'reason' => 'nullable|string|max:2000',
        ]);
        $creator = $request->user();

        $productReturn = DB::transaction(function () use ($validated, $creator) {
            $itemReference = SaleItem::query()->findOrFail($validated['sale_item_id']);
            $invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($itemReference->sale_invoice_id);
            $saleItem = SaleItem::query()
                ->where('sale_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->findOrFail($itemReference->id);

            if (! $saleItem->product_id) {
                throw ValidationException::withMessages([
                    'sale_item_id' => 'This product is no longer in inventory and cannot be returned.',
                ]);
            }

            $product = Product::query()->lockForUpdate()->find($saleItem->product_id);
            if (! $product) {
                throw ValidationException::withMessages([
                    'sale_item_id' => 'This product is no longer in inventory and cannot be returned.',
                ]);
            }

            if ($validated['return_date'] < $invoice->invoice_date->toDateString()) {
                throw ValidationException::withMessages([
                    'return_date' => 'The return date cannot be before the original invoice date.',
                ]);
            }

            $previousReturns = ProductReturn::query()
                ->where('sale_item_id', $saleItem->id)
                ->get(['quantity', 'return_amount']);
            $previousQuantity = (int) $previousReturns->sum('quantity');
            $remainingQuantity = (int) $saleItem->quantity - $previousQuantity;
            $quantity = (int) $validated['quantity'];

            if ($quantity > $remainingQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$remainingQuantity} unit(s) remain eligible for return.",
                ]);
            }

            $subtotal = (float) $invoice->subtotal;
            $lineTotal = (float) $saleItem->line_total;
            $lineShare = $subtotal > 0 ? $lineTotal / $subtotal : 0;
            $fullLineReturnAmount = round(
                $lineTotal
                - ((float) $invoice->discount_amount * $lineShare)
                + ((float) $invoice->vat_amount * $lineShare)
                + ((float) $invoice->ait_amount * $lineShare),
                2
            );
            $alreadyReturnedAmount = (float) $previousReturns->sum('return_amount');
            $returnAmount = $quantity === $remainingQuantity
                ? round($fullLineReturnAmount - $alreadyReturnedAmount, 2)
                : min(
                    round($fullLineReturnAmount * $quantity / (int) $saleItem->quantity, 2),
                    round($fullLineReturnAmount - $alreadyReturnedAmount, 2)
                );
            $returnAmount = max($returnAmount, 0);

            $ledger = CustomerLedger::query()
                ->where('sale_invoice_id', $invoice->id)
                ->where('entry_type', 'sale')
                ->lockForUpdate()
                ->first();

            if ($ledger) {
                $ledgerTotal = round((float) $ledger->total_amount, 2);
                $ledgerPayment = round((float) $ledger->payment_amount, 2);
                $ledgerDue = max(round((float) $ledger->due_amount, 2), 0);

                if ($returnAmount > $ledgerTotal) {
                    throw ValidationException::withMessages([
                        'sale_item_id' => 'The return amount exceeds the remaining sale balance.',
                    ]);
                }

                $amountAppliedToDue = min($returnAmount, $ledgerDue);
                $refundAmount = round($returnAmount - $amountAppliedToDue, 2);
                if ($refundAmount > $ledgerPayment) {
                    throw ValidationException::withMessages([
                        'sale_item_id' => 'The return refund exceeds the payment recorded for this invoice.',
                    ]);
                }

                $ledger->update([
                    'total_amount' => round($ledgerTotal - $returnAmount, 2),
                    'payment_amount' => round($ledgerPayment - $refundAmount, 2),
                    'due_amount' => round($ledgerDue - $amountAppliedToDue, 2),
                    'quantity' => max((int) $ledger->quantity - $quantity, 0),
                    'payment_type' => $ledgerDue > $amountAppliedToDue ? 'due' : 'full_payment',
                ]);
            } else {
                $invoiceDue = max((float) $invoice->due_amount, 0);
                $amountAppliedToDue = min($returnAmount, $invoiceDue);
                $refundAmount = round($returnAmount - $amountAppliedToDue, 2);
            }

            $invoice->update([
                'total_amount' => round((float) $invoice->total_amount - $returnAmount, 2),
                'amount_paid' => round((float) $invoice->amount_paid - $refundAmount, 2),
                'due_amount' => round(max((float) $invoice->due_amount - $amountAppliedToDue, 0), 2),
            ]);

            $productReturn = ProductReturn::create([
                'return_no' => 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'sale_invoice_id' => $invoice->id,
                'sale_item_id' => $saleItem->id,
                'product_id' => $product->id,
                'customer_id' => $invoice->customer_id,
                'returned_by' => $creator->id,
                'invoice_no' => $invoice->invoice_no,
                'product_name' => $saleItem->product_name,
                'quantity' => $quantity,
                'return_date' => $validated['return_date'],
                'return_amount' => $returnAmount,
                'refund_amount' => $refundAmount,
                'reason' => $validated['reason'] ?? null,
            ]);

            $product->unit = (string) ((float) $product->unit + $quantity);
            $product->save();

            if ($refundAmount > 0) {
                VoucherEntry::createWithCode([
                    'voucher_type' => 'debit',
                    'expense_income_type' => $invoice->customer_name ?: 'Walk-in Customer',
                    'voucher_date' => $validated['return_date'],
                    'amount' => number_format($refundAmount, 2, '.', ''),
                    'reference' => $productReturn->return_no,
                    'description' => "Refund for returned product {$saleItem->display_product_name} from invoice {$invoice->invoice_no}",
                    'created_by' => $creator->id,
                    'created_by_name' => $creator->name,
                    'voucherable_type' => ProductReturn::class,
                    'voucherable_id' => $productReturn->id,
                ]);
            }

            return $productReturn;
        });

        return redirect()
            ->route('product-returns.index', ['invoice_id' => $productReturn->sale_invoice_id])
            ->with('success', "Return {$productReturn->return_no} recorded. Stock and invoice balance were updated.");
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

        $returns = ProductReturn::query()
            ->whereBetween('return_date', [$fromDate, $toDate]);
        $totalQuantity = (int) (clone $returns)->sum('quantity');
        $totalReturnAmount = (float) (clone $returns)->sum('return_amount');
        $totalRefundAmount = (float) (clone $returns)->sum('refund_amount');
        $returnCount = (clone $returns)->count();
        // Return report-এর Unit কলামের জন্য Product-এর দুই ধরনের unit তথ্য eager-load করা হয়।
        $rows = (clone $returns)
            ->with(['returnedBy', 'invoice', 'product:id,productName,quantity_size,stock_unit'])
            ->latest('return_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('backend.ProductReturns.report', compact(
            'fromDate',
            'toDate',
            'totalQuantity',
            'totalReturnAmount',
            'totalRefundAmount',
            'returnCount',
            'rows'
        ));
    }

    private function invoicePayload(SaleInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'invoice_date' => $invoice->invoice_date?->format('d M Y, h:i A'),
            'customer_name' => $invoice->customer_name ?: 'Walk-in Customer',
            'customer_phone' => $invoice->customer_phone,
            'items' => $invoice->items->map(function (SaleItem $item) {
                $returnedQuantity = (int) ($item->returned_quantity ?? 0);
                $remainingQuantity = $item->remaining_quantity;

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->display_product_name,
                    'quantity' => (int) $item->quantity,
                    'returned_quantity' => $returnedQuantity,
                    'remaining_quantity' => $remainingQuantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'returnable' => $item->product_id !== null && $remainingQuantity > 0,
                ];
            })->values(),
        ];
    }
}
