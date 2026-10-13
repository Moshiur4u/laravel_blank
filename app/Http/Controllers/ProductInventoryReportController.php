<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplierledger;
use App\Support\ExcelCsvExport;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductInventoryReportController extends Controller
{
    /**
     * সব পণ্যের ক্রয়, বর্তমান মজুত, মেয়াদ ও supplier তথ্যসহ inventory report দেখায়।
     */
    public function index(): View
    {
        $rows = $this->reportRows();

        return view('backend.reports.productinventory', [
            'rows' => $rows,
            'total_products' => $rows->count(),
            'total_purchased_quantity' => $rows->sum('purchased_quantity'),
            'total_purchase_amount' => $rows->sum('purchase_amount'),
            'total_stock' => $rows->sum('stock'),
        ]);
    }

    /**
     * Downloads the complete inventory report in an Excel-compatible format.
     */
    public function export(): StreamedResponse
    {
        $rows = $this->reportRows();
        $totalProducts = $rows->count();
        $totalPurchasedQuantity = $rows->sum('purchased_quantity');
        $totalPurchaseAmount = $rows->sum('purchase_amount');
        $totalStock = $rows->sum('stock');

        return ExcelCsvExport::download('product-inventory-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $rows,
            $totalProducts,
            $totalPurchasedQuantity,
            $totalPurchaseAmount,
            $totalStock
        ): void {
            $writeRow(['Product Inventory Report']);
            $writeRow(['Total Products', $totalProducts]);
            $writeRow(['Total Purchased Quantity', number_format((float) $totalPurchasedQuantity, 2, '.', '')]);
            $writeRow(['Total Purchase Amount', number_format((float) $totalPurchaseAmount, 2, '.', '')]);
            $writeRow(['Current Stock', number_format((float) $totalStock, 2, '.', '')]);
            $writeRow([]);
            $writeRow(['Product', 'Purchased Quantity', 'Purchase Amount', 'Current Stock', 'Expiry Date', 'Expiry Status', 'Supplier', 'Phone Number']);

            foreach ($rows as $row) {
                $writeRow([
                    $row['name'],
                    number_format((float) $row['purchased_quantity'], 2, '.', ''),
                    number_format((float) $row['purchase_amount'], 2, '.', ''),
                    number_format((float) $row['stock'], 2, '.', ''),
                    $row['expiry_date']?->format('Y-m-d'),
                    match ($row['expiry_status']) {
                        'expired' => 'Expired',
                        'expiring' => 'Expiring Soon',
                        default => $row['expiry_date'] ? 'Valid' : 'No expiry date',
                    },
                    $row['suppliers']->pluck('name')->implode(', '),
                    $row['suppliers']->pluck('phone')->filter()->implode(', '),
                ]);
            }
        });
    }

    /**
     * Builds the inventory rows shared by the report page and its export.
     */
    private function reportRows(): Collection
    {
        // Product ও ledger-এর supplier একবারে load করে প্রতিটি row-তে আলাদা query এড়ানো হয়।
        $products = Product::query()
            ->with(['supplier', 'purchaseLedgers.supplier'])
            ->orderBy('productName')
            ->get();

        $rows = $products->map(static function (Product $product): array {
            $purchaseLedgers = $product->purchaseLedgers;
            $suppliers = $purchaseLedgers
                ->map(static fn (Supplierledger $ledger) => $ledger->supplier)
                ->filter()
                ->unique('id')
                ->values();

            // Ledger না থাকা পুরোনো product-এর supplier-ও রিপোর্টে দেখানো হয়।
            if ($suppliers->isEmpty() && $product->supplier) {
                $suppliers = collect([$product->supplier]);
            }

            return [
                'name' => $product->productName,
                'purchased_quantity' => $purchaseLedgers->sum(
                    static fn (Supplierledger $ledger): float => (float) $ledger->quantity
                ),
                'purchase_amount' => $purchaseLedgers->sum(
                    static fn (Supplierledger $ledger): float => (float) $ledger->total
                ),
                'stock' => (float) $product->unit,
                'expiry_date' => $product->expiry_date,
                'expiry_status' => $product->isExpired()
                    ? 'expired'
                    : ($product->isExpiringSoon() ? 'expiring' : 'valid'),
                'suppliers' => $suppliers
                    ->map(static fn ($supplier): array => [
                        'name' => $supplier->name,
                        'phone' => $supplier->phone,
                    ])
                    ->values(),
            ];
        });

        return $rows;
    }
}