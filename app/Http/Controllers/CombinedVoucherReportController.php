<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\VoucherEntry;
use App\Support\ExcelCsvExport;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $reportQuery = fn () => $this->combinedVoucherQuery($startDate, $endDate);

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

    /**
     * Downloads every voucher in the selected combined-report period.
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
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
            $periodLabel = $selectedMonth->format('F Y');
        } else {
            $startDate = $filters['date'] ?? today()->toDateString();
            $endDate = $startDate;
            $periodLabel = Carbon::parse($startDate)->format('d M Y');
        }

        $totals = $this->combinedVoucherQuery($startDate, $endDate)
            ->selectRaw("SUM(CASE WHEN voucher_category IN ('employee', 'debit') THEN 1 ELSE 0 END) as debit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN 1 ELSE 0 END) as credit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN amount ELSE 0 END) as employee_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->first();
        $vouchers = $this->combinedVoucherQuery($startDate, $endDate)
            ->orderByDesc('report_date')
            ->orderByDesc('voucher_code')
            ->cursor();

        return ExcelCsvExport::download('combined-voucher-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $periodLabel,
            $totals,
            $vouchers
        ): void {
            $writeRow(['Combined Voucher Report']);
            $writeRow(['Period', $periodLabel]);
            $writeRow(['Debit Voucher Count', (int) $totals->debit_voucher_count]);
            $writeRow(['Credit Voucher Count', (int) $totals->credit_voucher_count]);
            $writeRow(['Employee Voucher Total', number_format((float) $totals->employee_total, 2, '.', '')]);
            $writeRow(['Total Debit', number_format((float) $totals->debit_total, 2, '.', '')]);
            $writeRow(['Total Credit', number_format((float) $totals->credit_total, 2, '.', '')]);
            $writeRow([]);
            $writeRow(['Date', 'Voucher No.', 'Voucher Type', 'Voucher Head', 'Debit', 'Credit', 'Employee', 'Created By', 'Reference / Description']);

            foreach ($vouchers as $voucher) {
                $isCredit = $voucher->voucher_category === 'credit';
                $voucherType = $voucher->voucher_category === 'employee'
                    ? 'Employee'
                    : (VoucherEntry::TYPES[$voucher->voucher_category] ?? $voucher->voucher_category);
                $voucherHead = $voucher->voucher_category === 'employee'
                    ? (EmployeePaymentVoucher::EXPENSE_TYPES[$voucher->voucher_head] ?? $voucher->voucher_head)
                    : $voucher->voucher_head;

                $writeRow([
                    Carbon::parse($voucher->report_date)->format('Y-m-d'),
                    $voucher->voucher_code,
                    $voucherType,
                    $voucherHead,
                    $isCredit ? '' : number_format((float) $voucher->amount, 2, '.', ''),
                    $isCredit ? number_format((float) $voucher->amount, 2, '.', '') : '',
                    $voucher->employee_name,
                    $voucher->created_by_name,
                    $voucher->reference ?: $voucher->description,
                ]);
            }
        });
    }

    /**
     * Builds the shared union query for combined report rows.
     */
    private function combinedVoucherQuery(string $startDate, string $endDate): Builder
    {
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

        return DB::query()->fromSub($employeeVouchers->unionAll($generalVouchers), 'combined_vouchers');
    }
}
