<?php

namespace App\Http\Controllers;

use App\Models\SaleInvoice;
use App\Models\Supplierledger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardConrtoller extends Controller
{
    public function dashboard(){
        return view ('dashboard.dashboard');
    }

    /**
     * তারিখভিত্তিক ব্যবসার সারাংশ এবং সর্বোচ্চ বিক্রি হওয়া তালিকা dashboard view-তে পাঠায়।
     */
    public function dashboardbody()
    {
        // আজ, চলতি সপ্তাহ (সোমবার থেকে) ও চলতি মাসের জন্য inclusive-start/exclusive-end range তৈরি হয়।
        $today = Carbon::today();
        $periods = [
            'daily' => [$today->copy(), $today->copy()->addDay()],
            'weekly' => [
                $today->copy()->startOfWeek(Carbon::MONDAY),
                $today->copy()->startOfWeek(Carbon::MONDAY)->addWeek(),
            ],
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->startOfMonth()->addMonth()],
        ];

        $metrics = [];
        foreach ($periods as $period => [$start, $end]) {
            // আগের বকেয়া নতুন বিক্রির আয় নয়, তাই revenue থেকে তা বাদ দিয়ে বিক্রি, discount ও VAT ধরা হয়।
            $invoiceTotals = SaleInvoice::query()
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<', $end);

            $revenue = (clone $invoiceTotals)
                ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
                ->value('total');
            $due = (clone $invoiceTotals)->sum('due_amount');

            // products.price থেকে purchase_price বাদ দিয়ে বিক্রিত quantity গুণে এই period-এর profit হয়।
            // কোনো sold item-এর ক্রয়মূল্য না থাকলে partial/ভুল profit না দেখিয়ে period-টি অসম্পূর্ণ ধরা হয়।
            $profitSummary = DB::table('sale_items')
                ->join('sale_invoices', 'sale_invoices.id', '=', 'sale_items.sale_invoice_id')
                ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sale_invoices.invoice_date', '>=', $start)
                ->where('sale_invoices.invoice_date', '<', $end)
                ->selectRaw('COUNT(*) as sold_items')
                ->selectRaw('SUM(CASE WHEN products.purchase_price IS NULL THEN 1 ELSE 0 END) as missing_cost_items')
                ->selectRaw('SUM((products.price - products.purchase_price) * sale_items.quantity) as total')
                ->first();
            $profit = $profitSummary->sold_items > 0 && $profitSummary->missing_cost_items > 0
                ? null
                : (float) ($profitSummary->total ?? 0);

            // supplier ledger-এর total-কে entry creation date অনুযায়ী ঐ সময়ের purchase order ধরা হয়।
            $purchaseTotal = Supplierledger::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->sum('total');

            $metrics[$period] = [
                'revenue' => (float) $revenue,
                'due' => (float) $due,
                'profit' => $profit,
                'purchase' => (float) $purchaseTotal,
            ];
        }

        // product-এর নাম snapshot থেকে রাখা হয়; ranking quantity sold অনুযায়ী সব সময়ের জন্য।
        $topProducts = DB::table('sale_items')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->select('sale_items.product_id', 'sale_items.product_name', 'products.img_url')
            ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sale_items.line_total) as sales_amount')
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'products.img_url')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        // employee-কে মোট বিক্রি (আগের বকেয়া বাদে) দিয়ে rank করা হয়; নাম না থাকলে Unknown দেখানো হয়।
        $topEmployees = DB::table('sale_invoices')
            ->leftJoin('users', 'users.id', '=', 'sale_invoices.user_id')
            ->select('sale_invoices.user_id')
            ->selectRaw("COALESCE(users.name, 'Unknown') as employee_name")
            ->selectRaw('COUNT(sale_invoices.id) as invoice_count')
            ->selectRaw('SUM(sale_invoices.total_amount - sale_invoices.previous_due) as sales_amount')
            ->groupBy('sale_invoices.user_id', 'users.name')
            ->orderByDesc('sales_amount')
            ->limit(5)
            ->get();

        return view('dashboard.dashboard-body', compact('metrics', 'topProducts', 'topEmployees'));
    }
}
