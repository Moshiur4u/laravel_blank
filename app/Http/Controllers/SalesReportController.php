<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\ExcelCsvExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        if (
            ! empty($filters['start_date'])
            && ! empty($filters['end_date'])
            && Carbon::parse($filters['end_date'])->lt(Carbon::parse($filters['start_date']))
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        $startDate = $filters['start_date'] ?? (empty($filters['end_date']) ? today()->toDateString() : null);
        $endDate = $filters['end_date'] ?? (empty($filters['start_date']) ? today()->toDateString() : null);

        $invoiceQuery = SaleInvoice::query()
            ->when($startDate, fn ($query) => $query->whereDate('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->whereHas(
                'items',
                fn ($itemQuery) => $itemQuery->where('product_id', $productId)
            ));

        $returnTotalsByInvoice = \Illuminate\Support\Facades\DB::table('product_returns')
            ->select('sale_invoice_id')
            ->selectRaw('SUM(return_amount) as returned_amount')
            ->groupBy('sale_invoice_id');

        // total_amount may include an older balance; report only revenue from the current sale.
        $totalSalesAmount = (float) ((clone $invoiceQuery)
            ->leftJoinSub($returnTotalsByInvoice, 'report_returns', 'report_returns.sale_invoice_id', '=', 'sale_invoices.id')
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount + ait_amount - COALESCE(report_returns.returned_amount, 0)), 0) as total')
            ->value('total') ?? 0);
        $totalDue = (float) (clone $invoiceQuery)->sum('due_amount');
        $totalSalesCount = (clone $invoiceQuery)->count();

        $returnTotalsByItem = \Illuminate\Support\Facades\DB::table('product_returns')
            ->select('sale_item_id')
            ->selectRaw('SUM(quantity) as returned_quantity')
            ->groupBy('sale_item_id');
        $topProductsQuery = SaleItem::query()
            ->join('sale_invoices', 'sale_items.sale_invoice_id', '=', 'sale_invoices.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoinSub($returnTotalsByItem, 'report_item_returns', 'report_item_returns.sale_item_id', '=', 'sale_items.id')
            ->when($startDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('sale_invoices.user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('sale_invoices.customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('sale_items.product_id', $productId));

        $netQuantity = 'CASE WHEN sale_items.quantity > COALESCE(report_item_returns.returned_quantity, 0) THEN sale_items.quantity - COALESCE(report_item_returns.returned_quantity, 0) ELSE 0 END';
        $netRevenue = "CASE WHEN sale_items.quantity > 0 THEN sale_items.line_total * ({$netQuantity}) / sale_items.quantity ELSE 0 END";
        $unitCost = 'COALESCE(sale_items.purchase_price, products.purchase_price, 0)';

        // পণ্যের return বাদ দিয়ে net quantity/revenue/cost, VAT ও AIT নির্ণয় করা হয়।
        $topProducts = (clone $topProductsQuery)
            ->select([
                'sale_items.product_id',
                'sale_items.product_name',
            ])
            ->selectRaw("CASE WHEN SUM({$netQuantity}) > 0 THEN SUM(({$netQuantity}) * {$unitCost}) / SUM({$netQuantity}) ELSE 0 END as purchase_price")
            ->selectRaw("CASE WHEN SUM({$netQuantity}) > 0 THEN SUM({$netRevenue}) / SUM({$netQuantity}) ELSE 0 END as avg_selling_price")
            ->selectRaw("SUM({$netQuantity}) as quantity_sold")
            ->selectRaw("SUM({$netRevenue}) as revenue")
            ->selectRaw("SUM(({$netQuantity}) * {$unitCost}) as total_cost")
            ->selectRaw("SUM(({$netRevenue}) - (({$netQuantity}) * {$unitCost})) as profit")
            ->selectRaw("SUM(CASE WHEN sale_invoices.subtotal > 0 THEN sale_invoices.vat_amount * ({$netRevenue}) / sale_invoices.subtotal ELSE 0 END) as vat_amount")
            ->selectRaw("SUM(CASE WHEN sale_invoices.subtotal > 0 THEN sale_invoices.ait_amount * ({$netRevenue}) / sale_invoices.subtotal ELSE 0 END) as ait_amount")
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('revenue')
            ->orderBy('sale_items.product_name')
            ->get();

        $totalProfit = (float) $topProducts->sum('profit');
        $totalCost = (float) $topProducts->sum('total_cost');

        $invoices = (clone $invoiceQuery)
            ->with([
                'customer',
                'employee',
                'items.product',
                'items.returns',
                'productReturns',
            ])
            ->withCount('items')
            ->latest('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('backend.Sales.salesReport', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => $filters,
            'products' => Product::query()->orderBy('productName')->get(['id', 'productName']),
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'invoices' => $invoices,
            'topProducts' => $topProducts,
            'totalSalesAmount' => $totalSalesAmount,
            'totalDue' => $totalDue,
            'totalSalesCount' => $totalSalesCount,
            'totalProfit' => $totalProfit,
            'totalCost' => $totalCost,
            'reportGeneratedAt' => now(),
        ]);
    }

    /**
     * Downloads every filtered sales-report row and its summary as Excel-compatible CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        if (
            ! empty($filters['start_date'])
            && ! empty($filters['end_date'])
            && Carbon::parse($filters['end_date'])->lt(Carbon::parse($filters['start_date']))
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        $startDate = $filters['start_date'] ?? (empty($filters['end_date']) ? today()->toDateString() : null);
        $endDate = $filters['end_date'] ?? (empty($filters['start_date']) ? today()->toDateString() : null);
        $invoiceQuery = SaleInvoice::query()
            ->when($startDate, fn ($query) => $query->whereDate('invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->whereHas(
                'items',
                fn ($itemQuery) => $itemQuery->where('product_id', $productId)
            ));

        $returnTotalsByInvoice = \Illuminate\Support\Facades\DB::table('product_returns')
            ->select('sale_invoice_id')
            ->selectRaw('SUM(return_amount) as returned_amount')
            ->groupBy('sale_invoice_id');
        $totalSalesAmount = (float) ((clone $invoiceQuery)
            ->leftJoinSub($returnTotalsByInvoice, 'report_returns', 'report_returns.sale_invoice_id', '=', 'sale_invoices.id')
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount + ait_amount - COALESCE(report_returns.returned_amount, 0)), 0) as total')
            ->value('total') ?? 0);
        $totalDue = (float) (clone $invoiceQuery)->sum('due_amount');
        $totalSalesCount = (clone $invoiceQuery)->count();

        $returnTotalsByItem = \Illuminate\Support\Facades\DB::table('product_returns')
            ->select('sale_item_id')
            ->selectRaw('SUM(quantity) as returned_quantity')
            ->groupBy('sale_item_id');
        $topProductsQuery = SaleItem::query()
            ->join('sale_invoices', 'sale_items.sale_invoice_id', '=', 'sale_invoices.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoinSub($returnTotalsByItem, 'report_item_returns', 'report_item_returns.sale_item_id', '=', 'sale_items.id')
            ->when($startDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('sale_invoices.user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('sale_invoices.customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('sale_items.product_id', $productId));

        $netQuantity = 'CASE WHEN sale_items.quantity > COALESCE(report_item_returns.returned_quantity, 0) THEN sale_items.quantity - COALESCE(report_item_returns.returned_quantity, 0) ELSE 0 END';
        $netRevenue = "CASE WHEN sale_items.quantity > 0 THEN sale_items.line_total * ({$netQuantity}) / sale_items.quantity ELSE 0 END";
        $unitCost = 'COALESCE(sale_items.purchase_price, products.purchase_price, 0)';

        $topProducts = (clone $topProductsQuery)
            ->select(['sale_items.product_id', 'sale_items.product_name'])
            ->selectRaw("CASE WHEN SUM({$netQuantity}) > 0 THEN SUM(({$netQuantity}) * {$unitCost}) / SUM({$netQuantity}) ELSE 0 END as purchase_price")
            ->selectRaw("CASE WHEN SUM({$netQuantity}) > 0 THEN SUM({$netRevenue}) / SUM({$netQuantity}) ELSE 0 END as avg_selling_price")
            ->selectRaw("SUM({$netQuantity}) as quantity_sold")
            ->selectRaw("SUM({$netRevenue}) as revenue")
            ->selectRaw("SUM(({$netQuantity}) * {$unitCost}) as total_cost")
            ->selectRaw("SUM(({$netRevenue}) - (({$netQuantity}) * {$unitCost})) as profit")
            ->selectRaw("SUM(CASE WHEN sale_invoices.subtotal > 0 THEN sale_invoices.vat_amount * ({$netRevenue}) / sale_invoices.subtotal ELSE 0 END) as vat_amount")
            ->selectRaw("SUM(CASE WHEN sale_invoices.subtotal > 0 THEN sale_invoices.ait_amount * ({$netRevenue}) / sale_invoices.subtotal ELSE 0 END) as ait_amount")
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('revenue')
            ->orderBy('sale_items.product_name')
            ->get();

        $totalProfit = (float) $topProducts->sum('profit');

        $invoices = (clone $invoiceQuery)
            ->with([
                'customer',
                'employee',
                'items.product',
                'items.returns',
                'productReturns',
            ])
            ->withCount('items')
            ->latest('invoice_date')
            ->lazy(500);
        $period = ($startDate ?? 'Beginning').' - '.($endDate ?? 'Present');

        return ExcelCsvExport::download('sales-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $period,
            $totalSalesAmount,
            $totalDue,
            $totalSalesCount,
            $totalProfit,
            $topProducts,
            $invoices
        ): void {
            $writeRow(['Sales Report']);
            $writeRow(['Period', $period]);
            $writeRow(['Total Sale Amount', number_format($totalSalesAmount, 2, '.', '')]);
            $writeRow(['Total Profit', number_format($totalProfit, 2, '.', '')]);
            $writeRow(['Total Due', number_format($totalDue, 2, '.', '')]);
            $writeRow(['Total Sales (Invoices)', $totalSalesCount]);
            $writeRow([]);
            $writeRow(['Product-wise Sales & Profit']);
            $writeRow(['Product', 'Purchase Price', 'Avg Selling Price', 'Quantity Sold', 'Total Sales (Revenue)', 'Total Purchase Cost', 'Profit', 'VAT', 'AIT']);
            foreach ($topProducts as $product) {
                $writeRow([
                    $product->product_name,
                    number_format((float) $product->purchase_price, 2, '.', ''),
                    number_format((float) $product->avg_selling_price, 2, '.', ''),
                    $product->quantity_sold,
                    number_format((float) $product->revenue, 2, '.', ''),
                    number_format((float) $product->total_cost, 2, '.', ''),
                    number_format((float) $product->profit, 2, '.', ''),
                    number_format((float) $product->vat_amount, 2, '.', ''),
                    number_format((float) $product->ait_amount, 2, '.', ''),
                ]);
            }
            $writeRow([]);
            $writeRow(['Sales Details']);
            $writeRow(['Date', 'Invoice', 'Customer', 'Employee', 'Items', 'Sale Amount', 'Profit', 'Due']);

            foreach ($invoices as $invoice) {
                $invoiceReturnedGross = $invoice->items->sum(
                    fn ($item) => (int) $item->quantity > 0
                        ? $item->returns->sum(fn ($return) => (float) $item->line_total * (int) $return->quantity / (int) $item->quantity)
                        : 0
                );
                $invoiceReturnedCost = $invoice->items->sum(
                    fn ($item) => $item->returns->sum(fn ($return) => $item->effective_purchase_price * (int) $return->quantity)
                );
                $invoiceReturnedDiscount = (float) $invoice->subtotal > 0
                    ? $invoiceReturnedGross * (float) $invoice->discount_amount / (float) $invoice->subtotal
                    : 0;
                $invoiceReturnedAmount = (float) $invoice->productReturns->sum('return_amount');
                $invoiceProfit = $invoice->items->sum(fn ($item) => (float) $item->profit)
                    - $invoiceReturnedGross
                    + $invoiceReturnedCost
                    - (float) $invoice->discount_amount
                    + $invoiceReturnedDiscount;
                $writeRow([
                    $invoice->invoice_date?->format('Y-m-d H:i:s'),
                    $invoice->invoice_no,
                    $invoice->customer?->name ?? $invoice->customer_name ?? 'Walk-in Customer',
                    $invoice->employee?->name ?? 'N/A',
                    $invoice->items_count,
                    number_format((float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->vat_amount + (float) $invoice->ait_amount - $invoiceReturnedAmount, 2, '.', ''),
                    number_format((float) $invoiceProfit, 2, '.', ''),
                    number_format((float) $invoice->due_amount, 2, '.', ''),
                ]);
            }
        });
    }
}
