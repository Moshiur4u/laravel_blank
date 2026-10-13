<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplierledger;
use Illuminate\View\View;

class ProductInventoryReportController extends Controller
{
    /**
     * সব পণ্যের ক্রয়, বর্তমান মজুত, মেয়াদ ও supplier তথ্যসহ inventory report দেখায়।
     */
    public function index(): View
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

        return view('backend.reports.productinventory', [
            'rows' => $rows,
            'total_products' => $rows->count(),
            'total_purchased_quantity' => $rows->sum('purchased_quantity'),
            'total_purchase_amount' => $rows->sum('purchase_amount'),
            'total_stock' => $rows->sum('stock'),
        ]);
    }
}