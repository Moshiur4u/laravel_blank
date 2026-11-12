<?php

namespace App\Support;

use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\VoucherEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleInvoiceUpdateService
{
    /**
     * Updates invoice items and synchronizes stock, ledger and any walk-in receipt voucher.
     *
     * @param array<int, array{product_id: int|string, quantity: int|string, unit_price: int|float|string}> $items
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $items, array $attributes = []): SaleInvoice
    {
        return DB::transaction(function () use ($id, $items, $attributes): SaleInvoice {
            $invoice = SaleInvoice::query()->lockForUpdate()->findOrFail($id);
            $user = auth()->user();
            abort_unless(
                $user?->hasRole('admin')
                    || ($user?->can('Update_today_Invoice') && $invoice->invoice_date?->isToday()),
                403
            );

            if (ProductReturn::query()->where('sale_invoice_id', $invoice->id)->exists()) {
                throw ValidationException::withMessages([
                    'items' => 'An invoice with product returns cannot be updated.',
                ]);
            }

            $oldItems = $invoice->items()->lockForUpdate()->get();
            $oldQuantities = $oldItems
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn($productItems) => $productItems->sum('quantity'));
            $newQuantities = collect($items)
                ->groupBy('product_id')
                ->map(fn($productItems) => $productItems->sum(fn($item) => (int) $item['quantity']));
            $productIds = $oldQuantities->keys()
                ->merge($newQuantities->keys())
                ->unique()
                ->sort()
                ->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($newQuantities as $productId => $quantity) {
                $product = $products->get($productId);
                $availableStock = (float) ($product?->unit ?? 0) + (int) $oldQuantities->get($productId, 0);

                if (! $product || $availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ($product?->display_name ?? 'A selected product') . ' does not have enough stock.',
                    ]);
                }
            }

            $ledger = CustomerLedger::query()
                ->where('sale_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            $subtotal = 0.0;
            $lineItems = [];
            $oldPurchasePrices = $oldItems
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn($productItems) => $productItems->first()->purchase_price);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = round((float) $item['unit_price'], 2);
                $lineTotal = round($quantity * $unitPrice, 2);
                $subtotal += $lineTotal;
                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'purchase_price' => $oldPurchasePrices->has($product->id)
                        ? $oldPurchasePrices->get($product->id)
                        : $product->purchase_price,
                ];
            }

            $subtotal = round($subtotal, 2);
            $discountType = $attributes['discount_type'] ?? $invoice->discount_type;
            $discountValue = isset($attributes['discount_value'])
                ? round((float) $attributes['discount_value'], 2)
                : (float) $invoice->discount_value;
            if ($discountType === 'percentage' && $discountValue > 100) {
                throw ValidationException::withMessages([
                    'discount_value' => 'The discount percentage cannot be greater than 100.',
                ]);
            }
            $discount = $discountType === 'percentage'
                ? round($subtotal * $discountValue / 100, 2)
                : round($discountValue, 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'items' => 'The updated subtotal cannot be lower than the invoice discount.',
                ]);
            }

            $taxableAmount = round($subtotal - $discount, 2);
            $oldTaxableAmount = (float) $invoice->subtotal - (float) $invoice->discount_amount;
            $vatRate = array_key_exists('vat_percentage', $attributes)
                ? (float) $attributes['vat_percentage'] / 100
                : ($oldTaxableAmount > 0 ? (float) $invoice->vat_amount / $oldTaxableAmount : 0);
            $aitRate = array_key_exists('ait_percentage', $attributes)
                ? (float) $attributes['ait_percentage'] / 100
                : ($oldTaxableAmount > 0 ? (float) $invoice->ait_amount / $oldTaxableAmount : 0);
            $vat = round($taxableAmount * $vatRate, 2);
            $ait = round($taxableAmount * $aitRate, 2);
            $saleAmount = round($taxableAmount + $vat + $ait, 2);
            $invoiceTotal = round(
                $saleAmount + ($invoice->include_previous_due ? (float) $invoice->previous_due : 0),
                2
            );
            $paymentAmount = round((float) ($attributes['amount_paid'] ?? $invoice->amount_paid), 2);
            $additionalPayments = $ledger
                ? round((float) $ledger->payments()->sum('amount'), 2)
                : 0;
            $totalPayments = round($paymentAmount + $additionalPayments, 2);

            if ($totalPayments > $invoiceTotal) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'The invoice total cannot be lower than the amount already received.',
                ]);
            }
            $previousInitialPayment = round((float) $invoice->amount_paid, 2);

            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                if ($product) {
                    $product->unit = (string) (
                        (float) $product->unit
                        + (int) $oldQuantities->get($productId, 0)
                        - (int) $newQuantities->get($productId, 0)
                    );
                    $product->save();
                }
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discount,
                'vat_amount' => $vat,
                'ait_amount' => $ait,
                'total_amount' => $invoiceTotal,
                'amount_paid' => $paymentAmount,
                'payment_method' => $paymentAmount > 0
                    ? ($attributes['payment_method'] ?? $invoice->payment_method)
                    : null,
                'due_amount' => round($invoiceTotal - $totalPayments, 2),
                'notes' => array_key_exists('notes', $attributes) ? $attributes['notes'] : $invoice->notes,
                'terms_conditions' => array_key_exists('terms_conditions', $attributes)
                    ? $attributes['terms_conditions']
                    : $invoice->terms_conditions,
            ]);

            $invoice->items()->delete();
            foreach ($lineItems as $lineItem) {
                SaleItem::create([
                    'sale_invoice_id' => $invoice->id,
                    'product_id' => $lineItem['product']->id,
                    'product_name' => $lineItem['product']->productName,
                    'quantity' => $lineItem['quantity'],
                    'purchase_price' => $lineItem['purchase_price'],
                    'unit_price' => $lineItem['unit_price'],
                    'line_total' => $lineItem['line_total'],
                ]);
            }

            if ($ledger) {
                $totalQuantity = collect($lineItems)->sum('quantity');
                $productsSummary = collect($lineItems)
                    ->map(fn($lineItem) => $lineItem['product']->display_name . ' × ' . $lineItem['quantity'])
                    ->implode(', ');

                $ledger->update([
                    'product_name' => mb_substr($productsSummary, 0, 255),
                    'quantity' => $totalQuantity,
                    'unit_price' => $totalQuantity > 0 ? round($subtotal / $totalQuantity, 2) : 0,
                    'total_amount' => $saleAmount,
                    'payment_amount' => $totalPayments,
                    'payment_method' => $paymentAmount > 0
                        ? ($attributes['payment_method'] ?? $ledger->payment_method)
                        : null,
                    'payment_type' => $invoiceTotal > $totalPayments ? 'due' : 'full_payment',
                    'due_amount' => round($invoiceTotal - $totalPayments, 2),
                ]);
                $this->synchronizeCustomerInitialVoucher(
                    $invoice,
                    $ledger,
                    $previousInitialPayment,
                    $paymentAmount
                );
            } else {
                $this->synchronizeWalkInVoucher($invoice, $paymentAmount);
            }

            return $invoice->fresh(['items.product', 'customer', 'employee']);
        });
    }

    private function synchronizeCustomerInitialVoucher(
        SaleInvoice $invoice,
        CustomerLedger $ledger,
        float $oldPaymentAmount,
        float $paymentAmount
    ): void {
        $customerName = $invoice->customer_name ?: $invoice->customer?->name ?: 'Customer';
        $description = 'Payment received from customer ' . $customerName;
        $voucherQuery = VoucherEntry::query()
            ->where('voucher_type', 'credit')
            ->where('reference', $invoice->invoice_no)
            ->where('description', $description)
            ->whereDate('voucher_date', $ledger->sale_date)
            ->where('amount', number_format($oldPaymentAmount, 2, '.', ''))
            ->where(function ($query) use ($ledger, $customerName) {
                $query->where(function ($source) use ($ledger) {
                    $source->where('voucherable_type', CustomerLedger::class)
                        ->where('voucherable_id', $ledger->id);
                })->orWhere(function ($legacy) use ($customerName) {
                    $legacy->whereNull('voucherable_type')
                        ->whereNull('voucherable_id')
                        ->where('expense_income_type', $customerName);
                });
            });
        $voucher = $oldPaymentAmount > 0
            ? $voucherQuery->lockForUpdate()->orderBy('id')->first()
            : null;

        if ($paymentAmount <= 0) {
            $voucher?->delete();

            return;
        }

        if ($voucher) {
            $voucher->update([
                'amount' => number_format($paymentAmount, 2, '.', ''),
            ]);

            return;
        }

        $creator = auth()->user();
        VoucherEntry::createWithCode([
            'voucher_type' => 'credit',
            'expense_income_type' => $customerName,
            'voucher_date' => $ledger->sale_date,
            'amount' => number_format($paymentAmount, 2, '.', ''),
            'reference' => $invoice->invoice_no,
            'description' => $description,
            'created_by' => $creator?->id ?? $invoice->user_id,
            'created_by_name' => $creator?->name ?? $invoice->employee?->name ?? 'System',
            'voucherable_type' => CustomerLedger::class,
            'voucherable_id' => $ledger->id,
        ]);
    }

    private function synchronizeWalkInVoucher(SaleInvoice $invoice, float $paymentAmount): void
    {
        $customerName = $invoice->customer_name ?: 'Walk-in Customer';
        $description = 'Payment received from customer ' . $customerName;
        $vouchers = VoucherEntry::query()
            ->where('voucher_type', 'credit')
            ->where(function ($query) use ($invoice, $customerName, $description) {
                $query->where(function ($source) use ($invoice) {
                    $source->where('voucherable_type', SaleInvoice::class)
                        ->where('voucherable_id', $invoice->id);
                })->orWhere(function ($legacy) use ($invoice, $customerName, $description) {
                    $legacy->whereNull('voucherable_type')
                        ->whereNull('voucherable_id')
                        ->where('expense_income_type', $customerName)
                        ->where('reference', $invoice->invoice_no)
                        ->where('description', $description);
                });
            })
            ->lockForUpdate()
            ->get();

        if ($paymentAmount <= 0) {
            $vouchers->each->delete();

            return;
        }

        if ($vouchers->isEmpty()) {
            $creator = auth()->user();
            VoucherEntry::createWithCode([
                'voucher_type' => 'credit',
                'expense_income_type' => $customerName,
                'voucher_date' => today(),
                'amount' => number_format($paymentAmount, 2, '.', ''),
                'reference' => $invoice->invoice_no,
                'description' => $description,
                'created_by' => $creator?->id ?? $invoice->user_id,
                'created_by_name' => $creator?->name ?? $invoice->employee?->name ?? 'System',
                'voucherable_type' => SaleInvoice::class,
                'voucherable_id' => $invoice->id,
            ]);

            return;
        }

        $firstVoucher = $vouchers->first();
        $otherAmounts = (float) $vouchers->skip(1)->sum('amount');
        if ($otherAmounts > $paymentAmount) {
            throw ValidationException::withMessages([
                'items' => 'The invoice vouchers cannot be synchronized with the amount received.',
            ]);
        }

        $firstVoucher->update([
            'amount' => number_format($paymentAmount - $otherAmounts, 2, '.', ''),
        ]);
    }
}
