<?php

namespace App\Support;

use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\VoucherEntry;
use Illuminate\Support\Facades\DB;

class SaleInvoiceVoidService
{
    public function canVoid(SaleInvoice $invoice): bool
    {
        $user = auth()->user();
        $canVoidAnyDay = $user?->can('Voide_anyday_Invoice') ?? false;
        $canVoidToday = $user?->can('Voide_today_Invoice') ?? false;

        return $canVoidAnyDay || ($canVoidToday && $invoice->invoice_date?->isToday());
    }

    public function void(int $id): string
    {
        return DB::transaction(function () use ($id): string {
            $invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($id);
            abort_unless($this->canVoid($invoice), 403);

            $items = $invoice->items()->lockForUpdate()->get();
            $returns = ProductReturn::query()
                ->whereIn('sale_item_id', $items->modelKeys())
                ->get(['id', 'sale_item_id', 'product_id', 'quantity']);
            $returnedByItem = $returns
                ->groupBy('sale_item_id')
                ->map(fn ($productReturns) => $productReturns->sum('quantity'));
            $quantities = $items
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn ($productItems) => $productItems->sum(
                    fn (SaleItem $item) => max((int) $item->quantity - (int) $returnedByItem->get($item->id, 0), 0)
                ));
            /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product) {
                    $product->unit = (string) ((float) $product->unit + $quantity);
                    $product->save();
                }
            }

            $ledgers = CustomerLedger::query()
                ->where('sale_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->get();
            $ledgerIds = $ledgers->modelKeys();
            $customerName = $invoice->customer_name ?: 'Walk-in Customer';

            /** @var \Illuminate\Database\Eloquent\Collection<int, VoucherEntry> $relatedVouchers */
            $relatedVouchers = VoucherEntry::query()
                ->where(function ($query) use ($invoice, $ledgerIds, $customerName, $returns) {
                    $query->where(function ($source) use ($invoice) {
                        $source->where('voucherable_type', SaleInvoice::class)
                            ->where('voucherable_id', $invoice->id);
                    });

                    if ($returns->isNotEmpty()) {
                        $query->orWhere(function ($source) use ($returns) {
                            $source->where('voucherable_type', ProductReturn::class)
                                ->whereIn('voucherable_id', $returns->pluck('id'));
                        });
                    }

                    if ($ledgerIds !== []) {
                        $query->orWhere(function ($source) use ($ledgerIds) {
                            $source->where('voucherable_type', CustomerLedger::class)
                                ->whereIn('voucherable_id', $ledgerIds);
                        });
                    }

                    $query->orWhere(function ($legacy) use ($invoice, $customerName) {
                        $legacy->whereNull('voucherable_type')
                            ->whereNull('voucherable_id')
                            ->where('voucher_type', 'credit')
                            ->where('expense_income_type', $customerName)
                            ->where('reference', $invoice->invoice_no)
                            ->where('description', 'Payment received from customer ' . $customerName);
                    });
                })
                ->lockForUpdate()
                ->get();

            foreach ($relatedVouchers as $voucher) {
                $voucher->delete();
            }

            foreach ($ledgers as $ledger) {
                $ledger->delete();
            }

            $invoice->items()->delete();
            $invoice->delete();

            return $invoice->invoice_no;
        });
    }
}