<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
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
            'total_expired_products' => $rows->where('expiry_status', 'expired')->count(),
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
        $totalExpiredProducts = $rows->where('expiry_status', 'expired')->count();

        return ExcelCsvExport::download('product-inventory-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $rows,
            $totalProducts,
            $totalPurchasedQuantity,
            $totalPurchaseAmount,
            $totalStock,
            $totalExpiredProducts
        ): void {
            $writeRow(['Product Inventory Report']);
            $writeRow(['Total Products', $totalProducts]);
            $writeRow(['Total Purchased Quantity', number_format((float) $totalPurchasedQuantity, 2, '.', '')]);
            $writeRow(['Total Purchase Amount', number_format((float) $totalPurchaseAmount, 2, '.', '')]);
            $writeRow(['Current Stock', number_format((float) $totalStock, 2, '.', '')]);
            $writeRow(['Expired Products', $totalExpiredProducts]);
            $writeRow([]);
            $writeRow([
                'Product',
                'Purchase Price',
                'Avg Selling Price',
                'Quantity Sold',
                'Total Sales',
                'Total Cost',
                'Profit',
                'Purchased Quantity',
                'Purchase Amount',
                'Current Stock',
                'Expiry Date',
                'Expiry Status',
                'Supplier',
                'Phone Number',
            ]);

            foreach ($rows as $row) {
                $writeRow([
                    $row['name'],
                    number_format((float) $row['purchase_price'], 2, '.', ''),
                    number_format((float) $row['avg_selling_price'], 2, '.', ''),
                    number_format((float) $row['quantity_sold'], 2, '.', ''),
                    number_format((float) $row['revenue'], 2, '.', ''),
                    number_format((float) $row['total_cost'], 2, '.', ''),
                    number_format((float) $row['profit'], 2, '.', ''),
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
        $salesByProduct = collect();

        if ($products->isNotEmpty()) {
            // বিক্রির সময়কার ক্রয়মূল্যকে অগ্রাধিকার দিয়ে Sales Report-এর মতো লাভ হিসাব করা হয়।
            $salesByProduct = SaleItem::query()
                ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                ->whereIn('sale_items.product_id', $products->pluck('id'))
                ->select('sale_items.product_id')
                ->selectRaw('CASE WHEN SUM(sale_items.quantity) > 0 THEN SUM(sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0)) / SUM(sale_items.quantity) ELSE 0 END as purchase_price')
                ->selectRaw('CASE WHEN SUM(sale_items.quantity) > 0 THEN SUM(sale_items.line_total) / SUM(sale_items.quantity) ELSE 0 END as avg_selling_price')
                ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
                ->selectRaw('SUM(sale_items.line_total) as revenue')
                ->selectRaw('SUM(sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0)) as total_cost')
                ->selectRaw('SUM(sale_items.line_total - (sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0))) as profit')
                ->groupBy('sale_items.product_id')
                ->get()
                ->keyBy('product_id');
        }

        $rows = $products->map(static function (Product $product) use ($salesByProduct): array {
            $purchaseLedgers = $product->purchaseLedgers;
            $sales = $salesByProduct->get($product->id);
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
                'purchase_price' => (float) ($sales?->purchase_price ?? 0),
                'avg_selling_price' => (float) ($sales?->avg_selling_price ?? 0),
                'quantity_sold' => (float) ($sales?->quantity_sold ?? 0),
                'revenue' => (float) ($sales?->revenue ?? 0),
                'total_cost' => (float) ($sales?->total_cost ?? 0),
                'profit' => (float) ($sales?->profit ?? 0),
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