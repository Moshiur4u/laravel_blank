<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CombinedVoucherReportController extends Controller
{
    /**
     * Employee Payment এবং Debit/Credit voucher এক report-এ দেখায়।
     */
    public function index(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'period' => ['nullable', 'in:daily,monthly'],
            'date' => ['nullable', 'required_if:period,daily', 'date'],
            'month' => ['nullable', 'required_if:period,monthly', 'date_format:Y-m'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('voucher-report.combined')
                ->withErrors($validator)
                ->withInput($request->query());
        }

        $filters = $validator->validated();
        $period = $filters['period'] ?? 'daily';
        if ($period === 'monthly') {
            $selectedMonth = Carbon::createFromFormat('Y-m', $filters['month'] ?? today()->format('Y-m'));
            $startDate = $selectedMonth->copy()->startOfMonth()->toDateString();
            $endDate = $selectedMonth->copy()->endOfMonth()->toDateString();
            $selectedMonth = $selectedMonth->format('Y-m');
        } else {
            $startDate = $filters['date'] ?? today()->toDateString();
            $endDate = $startDate;
            $selectedMonth = today()->format('Y-m');
        }

        $employeeVouchers = DB::table('employee_payment_vouchers')
            ->leftJoin('users as creators', 'creators.id', '=', 'employee_payment_vouchers.created_by')
            ->whereDate('employee_payment_vouchers.created_at', '>=', $startDate)
            ->whereDate('employee_payment_vouchers.created_at', '<=', $endDate)
            ->selectRaw('DATE(employee_payment_vouchers.created_at) as report_date')
            ->addSelect('employee_payment_vouchers.voucher_no as voucher_code')
            ->selectRaw("'employee' as voucher_category")
            ->addSelect('employee_payment_vouchers.expense_type as voucher_head')
            ->addSelect('employee_payment_vouchers.employee_name')
            ->selectRaw("COALESCE(creators.name, 'Unknown / legacy voucher') as created_by_name")
            ->addSelect('employee_payment_vouchers.amount')
            ->addSelect('employee_payment_vouchers.reference')
            ->addSelect('employee_payment_vouchers.notes as description');

        $generalVouchers = DB::table('voucher_entries')
            ->whereBetween('voucher_date', [$startDate, $endDate])
            ->select('voucher_date as report_date')
            ->addSelect('voucher_code')
            ->addSelect('voucher_type as voucher_category')
            ->addSelect('expense_income_type as voucher_head')
            ->selectRaw('NULL as employee_name')
            ->addSelect('created_by_name')
            ->addSelect('amount')
            ->addSelect('reference')
            ->addSelect('description');

        $combinedVouchers = $employeeVouchers->unionAll($generalVouchers);
        $reportQuery = fn () => DB::query()->fromSub($combinedVouchers, 'combined_vouchers');

        $totals = $reportQuery()
            ->selectRaw("SUM(CASE WHEN voucher_category IN ('employee', 'debit') THEN 1 ELSE 0 END) as debit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN 1 ELSE 0 END) as credit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN amount ELSE 0 END) as employee_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->first();

        $vouchers = $reportQuery()
            ->orderByDesc('report_date')
            ->orderByDesc('voucher_code')
            ->paginate(30)
            ->withQueryString();

        return view('backend.Accounts Entries.combinedVoucherReport', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMonth' => $selectedMonth,
            'vouchers' => $vouchers,
            'debitVoucherCount' => (int) $totals->debit_voucher_count,
            'creditVoucherCount' => (int) $totals->credit_voucher_count,
            'employeeTotal' => (float) $totals->employee_total,
            'debitTotal' => (float) $totals->debit_total,
            'creditTotal' => (float) $totals->credit_total,
            'expenseTypes' => EmployeePaymentVoucher::EXPENSE_TYPES,
            'voucherTypes' => VoucherEntry::TYPES,
        ]);
    }
}
