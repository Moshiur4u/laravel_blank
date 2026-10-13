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

        // total_amount may include an older balance; report only revenue from the current sale.
        $totalSalesAmount = (float) ((clone $invoiceQuery)
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
            ->value('total') ?? 0);
        $totalDue = (float) (clone $invoiceQuery)->sum('due_amount');
        $totalSalesCount = (clone $invoiceQuery)->count();

        $topProductsQuery = SaleItem::query()
            ->join('sale_invoices', 'sale_items.sale_invoice_id', '=', 'sale_invoices.id')
            ->when($startDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('sale_invoices.user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('sale_invoices.customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('sale_items.product_id', $productId));

        $topProducts = $topProductsQuery
            ->select([
                'sale_items.product_id',
                'sale_items.product_name',
            ])
            ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('revenue')
            ->orderBy('sale_items.product_name')
            ->limit(5)
            ->get();

        $invoices = (clone $invoiceQuery)
            ->with(['customer', 'employee'])
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

        $totalSalesAmount = (float) ((clone $invoiceQuery)
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
            ->value('total') ?? 0);
        $totalDue = (float) (clone $invoiceQuery)->sum('due_amount');
        $totalSalesCount = (clone $invoiceQuery)->count();
        $topProductsQuery = SaleItem::query()
            ->join('sale_invoices', 'sale_items.sale_invoice_id', '=', 'sale_invoices.id')
            ->when($startDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('sale_invoices.invoice_date', '<=', $endDate))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('sale_invoices.user_id', $employeeId))
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('sale_invoices.customer_id', $customerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('sale_items.product_id', $productId));
        $topProducts = $topProductsQuery
            ->select(['sale_items.product_name'])
            ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('revenue')
            ->orderBy('sale_items.product_name')
            ->limit(5)
            ->get();
        $invoices = (clone $invoiceQuery)
            ->with(['customer', 'employee'])
            ->withCount('items')
            ->latest('invoice_date')
            ->lazy(500);
        $period = ($startDate ?? 'Beginning').' - '.($endDate ?? 'Present');

        return ExcelCsvExport::download('sales-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $period,
            $totalSalesAmount,
            $totalDue,
            $totalSalesCount,
            $topProducts,
            $invoices
        ): void {
            $writeRow(['Sales Report']);
            $writeRow(['Period', $period]);
            $writeRow(['Total Sale Amount', number_format($totalSalesAmount, 2, '.', '')]);
            $writeRow(['Total Due', number_format($totalDue, 2, '.', '')]);
            $writeRow(['Total Sales (Invoices)', $totalSalesCount]);
            $writeRow([]);
            $writeRow(['Top Products by Revenue']);
            $writeRow(['Product', 'Quantity Sold', 'Revenue']);
            foreach ($topProducts as $product) {
                $writeRow([$product->product_name, $product->quantity_sold, number_format((float) $product->revenue, 2, '.', '')]);
            }
            $writeRow([]);
            $writeRow(['Sales Details']);
            $writeRow(['Date', 'Invoice', 'Customer', 'Employee', 'Items', 'Sale Amount', 'Due']);

            foreach ($invoices as $invoice) {
                $writeRow([
                    $invoice->invoice_date?->format('Y-m-d H:i:s'),
                    $invoice->invoice_no,
                    $invoice->customer?->name ?? $invoice->customer_name ?? 'Walk-in Customer',
                    $invoice->employee?->name ?? 'N/A',
                    $invoice->items_count,
                    number_format((float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->vat_amount, 2, '.', ''),
                    number_format((float) $invoice->due_amount, 2, '.', ''),
                ]);
            }
        });
    }
}
