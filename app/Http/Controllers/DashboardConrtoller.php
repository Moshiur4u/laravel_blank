<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\SaleInvoice;
use App\Models\Supplierledger;
use App\Models\VoucherEntry;
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
        // Dashboard KPI ও chart-এর জন্য আজ, চলতি সপ্তাহ, মাস এবং বছরের date range তৈরি হয়।
        $today = Carbon::today();
        $periods = [
            'daily' => [$today->copy(), $today->copy()->addDay()],
            'weekly' => [
                $today->copy()->startOfWeek(Carbon::MONDAY),
                $today->copy()->startOfWeek(Carbon::MONDAY)->addWeek(),
            ],
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->startOfMonth()->addMonth()],
            'yearly' => [$today->copy()->startOfYear(), $today->copy()->startOfYear()->addYear()],
        ];

        $metrics = [];
        // ড্যাশবোর্ড কার্ডের জন্য daily, weekly, monthly ও yearly KPI এবং লাভ হিসাব করা হয়।
        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $period) {
            [$start, $end] = $periods[$period];

            // আগের বকেয়া নতুন বিক্রির আয় নয়, তাই revenue থেকে তা বাদ দিয়ে বিক্রি, discount ও VAT ধরা হয়।
            $invoiceTotals = SaleInvoice::query()
                ->where('invoice_date', '>=', $start)
                ->where('invoice_date', '<', $end);

            $revenue = (clone $invoiceTotals)
                ->selectRaw('COALESCE(SUM(subtotal - discount_amount + vat_amount), 0) as total')
                ->value('total');
            $due = (clone $invoiceTotals)->sum('due_amount');

            // supplier ledger-এর total-কে entry creation date অনুযায়ী ঐ সময়ের purchase order ধরা হয়।
            $purchaseTotal = Supplierledger::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->sum('total');

            // Employee voucher payment date এবং general voucher voucher date অনুযায়ী খরচ/আয় ধরা হয়।
            $employeeVoucherTotal = EmployeePaymentVoucher::query()
                ->where('payment_date', '>=', $start->toDateString())
                ->where('payment_date', '<', $end->toDateString())
                ->sum('amount');
            $debitVoucherTotal = VoucherEntry::query()
                ->where('voucher_date', '>=', $start->toDateString())
                ->where('voucher_date', '<', $end->toDateString())
                ->where('voucher_type', 'debit')
                ->sum('amount');
            $creditVoucherTotal = VoucherEntry::query()
                ->where('voucher_date', '>=', $start->toDateString())
                ->where('voucher_date', '<', $end->toDateString())
                ->where('voucher_type', 'credit')
                ->sum('amount');
            $expenses = (float) $employeeVoucherTotal
                + (float) $debitVoucherTotal
                - (float) $creditVoucherTotal;
            $profit = round(
                (float) $revenue
                    - (float) $purchaseTotal
                    - $expenses,
                2
            );

            $metrics[$period] = [
                'revenue' => (float) $revenue,
                'due' => (float) $due,
                'purchase' => (float) $purchaseTotal,
                'expenses' => round($expenses, 2),
                'profit' => $profit,
            ];
        }

        $yearStart = $periods['yearly'][0];
        $yearEnd = $periods['yearly'][1];
        $chartStart = $periods['weekly'][0]->lt($yearStart)
            ? $periods['weekly'][0]
            : $yearStart;
        $dailyTotals = [
            'revenue' => $this->dailyTotals('sale_invoices', 'invoice_date', 'subtotal - discount_amount + vat_amount', $chartStart, $yearEnd),
            'purchase' => $this->dailyTotals('supplierledgers', 'created_at', 'total', $chartStart, $yearEnd),
            'employee_vouchers' => $this->dailyTotals('employee_payment_vouchers', 'payment_date', 'amount', $chartStart, $yearEnd),
            'debit_vouchers' => $this->dailyTotals('voucher_entries', 'voucher_date', 'amount', $chartStart, $yearEnd, 'debit'),
            'credit_vouchers' => $this->dailyTotals('voucher_entries', 'voucher_date', 'amount', $chartStart, $yearEnd, 'credit'),
        ];

        $profitChart = [];
        foreach ($periods as $period => [$start, $end]) {
            $isYearly = $period === 'yearly';
            $buckets = [];
            $bucketDate = $start->copy();
            $sumForBucket = static function (array $totals, string $bucketKey) use ($isYearly): float {
                if (! $isYearly) {
                    return (float) ($totals[$bucketKey] ?? 0);
                }

                $sum = 0.0;
                foreach ($totals as $date => $amount) {
                    if (str_starts_with($date, $bucketKey)) {
                        $sum += (float) $amount;
                    }
                }

                return $sum;
            };

            while ($bucketDate < $end) {
                $buckets[] = [
                    'date' => $bucketDate->copy(),
                    'key' => $isYearly ? $bucketDate->format('Y-m') : $bucketDate->toDateString(),
                ];
                $bucketDate = $isYearly ? $bucketDate->addMonth() : $bucketDate->addDay();
            }

            $labels = [];
            $revenueValues = [];
            $profitValues = [];
            foreach ($buckets as $bucket) {
                $bucketDate = $bucket['date'];
                $bucketKey = $bucket['key'];
                $labels[] = $isYearly ? $bucketDate->format('M Y') : $bucketDate->format('d M');

                $revenue = $sumForBucket($dailyTotals['revenue'], $bucketKey);
                $purchase = $sumForBucket($dailyTotals['purchase'], $bucketKey);
                $employeeVouchers = $sumForBucket($dailyTotals['employee_vouchers'], $bucketKey);
                $debitVouchers = $sumForBucket($dailyTotals['debit_vouchers'], $bucketKey);
                $creditVouchers = $sumForBucket($dailyTotals['credit_vouchers'], $bucketKey);

                $expenses = $employeeVouchers + $debitVouchers - $creditVouchers;
                $revenueValues[] = round($revenue, 2);
                $profitValues[] = round($revenue - $purchase - $expenses, 2);
            }

            $profitChart[$period] = [
                'labels' => $labels,
                'revenue' => $revenueValues,
                'profit' => $profitValues,
            ];
        }

        // product-এর নাম snapshot থেকে রাখা হয়; ranking quantity sold অনুযায়ী সব সময়ের জন্য।
        // এছাড়া বিক্রির মোট আয়ের পাশাপাশি ক্রয়মূল্যের স্ন্যাপশট অনুযায়ী লাভ (profit) নির্ণয় করা হয়।
        $topProducts = DB::table('sale_items')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->select('sale_items.product_id', 'sale_items.product_name', 'products.img_url')
            ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sale_items.line_total) as sales_amount')
            ->selectRaw('SUM(sale_items.line_total - (sale_items.quantity * COALESCE(sale_items.purchase_price, products.purchase_price, 0))) as profit')
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

        return view('dashboard.dashboard-body', compact('metrics', 'profitChart', 'topProducts', 'topEmployees'));
    }

    /**
     * Date-key অনুযায়ী একটি metric aggregate করে chart-এ ব্যবহারের map তৈরি করে।
     */
    private function dailyTotals(
        string $table,
        string $dateColumn,
        string $amountExpression,
        Carbon $start,
        Carbon $end,
        ?string $voucherType = null
    ): array {
        $query = DB::table($table)
            ->where($dateColumn, '>=', $start->toDateString())
            ->where($dateColumn, '<', $end->toDateString());

        if ($voucherType !== null) {
            $query->where('voucher_type', $voucherType);
        }

        return $query
            ->selectRaw("DATE({$dateColumn}) as date_key")
            ->selectRaw("SUM({$amountExpression}) as total")
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('total', 'date_key')
            ->map(fn ($total) => (float) $total)
            ->all();
    }
}
