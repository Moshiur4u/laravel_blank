<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\Organization;
use App\Models\User;
use App\Support\ExcelCsvExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePaymentVoucherController extends Controller
{
    /**
     * কর্মী পেমেন্ট ফর্ম, কর্মী তালিকা এবং সাম্প্রতিক ভাউচার দেখায়।
     */
    public function index()
    {
        $employees = User::query()->orderBy('name')->get(['id', 'name', 'salary']);
        $expenseTypes = EmployeePaymentVoucher::EXPENSE_TYPES;
        $paymentMethods = EmployeePaymentVoucher::PAYMENT_METHODS;
        $vouchers = EmployeePaymentVoucher::query()
            ->with('employee:id,name')
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);
        $totalAmount = EmployeePaymentVoucher::sum('amount');

        return view('backend.Accounts Entries.empolyeePayment', compact(
            'employees',
            'expenseTypes',
            'paymentMethods',
            'vouchers',
            'totalAmount'
        ));
    }

    /**
     * তৈরি-তারিখের পরিসর ও creator অনুযায়ী দৈনিক voucher report দেখায়।
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'created_by' => 'nullable|integer|exists:users,id',
            'employee_id' => 'nullable|integer|exists:users,id',
        ]);

        $validator->after(function ($validator) {
            $dates = $validator->getData();

            if (
                ! empty($dates['start_date'])
                && ! empty($dates['end_date'])
                && ! $validator->errors()->has('start_date')
                && ! $validator->errors()->has('end_date')
                && Carbon::parse($dates['end_date'])->lt(Carbon::parse($dates['start_date']))
            ) {
                $validator->errors()->add('end_date', 'The end date must be on or after the start date.');
            }
        });

        if ($validator->fails()) {
            return redirect()->route('employee-payment.report')
                ->withErrors($validator)
                ->withInput($request->query());
        }

        $validated = $validator->validated();
        $startDate = $validated['start_date'] ?? $validated['end_date'] ?? today()->toDateString();
        $endDate = $validated['end_date'] ?? $startDate;

        $reportQuery = EmployeePaymentVoucher::query()
            ->with(['employee:id,name', 'createdBy:id,name'])
            ->whereDate('employee_payment_vouchers.created_at', '>=', $startDate)
            ->whereDate('employee_payment_vouchers.created_at', '<=', $endDate);

        if (! empty($validated['created_by'])) {
            $reportQuery->where('employee_payment_vouchers.created_by', $validated['created_by']);
        }

        if (! empty($validated['employee_id'])) {
            $reportQuery->where('employee_payment_vouchers.employee_id', $validated['employee_id']);
        }

        $dailySummaries = (clone $reportQuery)
            ->setEagerLoads([])
            ->leftJoin('users as creators', 'creators.id', '=', 'employee_payment_vouchers.created_by')
            ->selectRaw('DATE(employee_payment_vouchers.created_at) as voucher_date')
            ->addSelect('employee_payment_vouchers.created_by')
            ->addSelect('creators.name as creator_name')
            ->addSelect('employee_payment_vouchers.employee_name')
            ->selectRaw('COUNT(employee_payment_vouchers.id) as voucher_count')
            ->selectRaw('SUM(employee_payment_vouchers.amount) as total_amount')
            ->groupByRaw('DATE(employee_payment_vouchers.created_at), employee_payment_vouchers.employee_id, employee_payment_vouchers.employee_name, employee_payment_vouchers.created_by, creators.name')
            ->orderByDesc('voucher_date')
            ->get();

        $totalCount = (clone $reportQuery)->count();
        $totalAmount = (float) (clone $reportQuery)->sum('amount');
        $vouchers = (clone $reportQuery)
            ->latest('employee_payment_vouchers.created_at')
            ->latest('employee_payment_vouchers.id')
            ->paginate(30)
            ->withQueryString();
        $creators = User::query()->orderBy('name')->get(['id', 'name']);
        $employees = User::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.Accounts Entries.VoucherEntries', compact(
            'startDate',
            'endDate',
            'validated',
            'dailySummaries',
            'totalCount',
            'totalAmount',
            'vouchers',
            'creators',
            'employees'
        ));
    }

    /**
     * Downloads every filtered employee voucher and its daily summary.
     */
    public function exportReport(Request $request): StreamedResponse|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'created_by' => 'nullable|integer|exists:users,id',
            'employee_id' => 'nullable|integer|exists:users,id',
        ]);
        $validator->after(function ($validator): void {
            $dates = $validator->getData();

            if (
                ! empty($dates['start_date'])
                && ! empty($dates['end_date'])
                && ! $validator->errors()->has('start_date')
                && ! $validator->errors()->has('end_date')
                && Carbon::parse($dates['end_date'])->lt(Carbon::parse($dates['start_date']))
            ) {
                $validator->errors()->add('end_date', 'The end date must be on or after the start date.');
            }
        });

        if ($validator->fails()) {
            return redirect()->route('employee-payment.report')
                ->withErrors($validator)
                ->withInput($request->query());
        }

        $filters = $validator->validated();
        $startDate = $filters['start_date'] ?? $filters['end_date'] ?? today()->toDateString();
        $endDate = $filters['end_date'] ?? $startDate;
        $reportQuery = EmployeePaymentVoucher::query()
            ->with(['employee:id,name', 'createdBy:id,name'])
            ->whereDate('employee_payment_vouchers.created_at', '>=', $startDate)
            ->whereDate('employee_payment_vouchers.created_at', '<=', $endDate)
            ->when($filters['created_by'] ?? null, fn ($query, $creatorId) => $query->where('employee_payment_vouchers.created_by', $creatorId))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_payment_vouchers.employee_id', $employeeId));
        $totalCount = (clone $reportQuery)->count();
        $totalAmount = (float) (clone $reportQuery)->sum('amount');
        $dailySummaries = (clone $reportQuery)
            ->setEagerLoads([])
            ->leftJoin('users as creators', 'creators.id', '=', 'employee_payment_vouchers.created_by')
            ->selectRaw('DATE(employee_payment_vouchers.created_at) as voucher_date')
            ->addSelect('creators.name as creator_name')
            ->addSelect('employee_payment_vouchers.employee_name')
            ->selectRaw('COUNT(employee_payment_vouchers.id) as voucher_count')
            ->selectRaw('SUM(employee_payment_vouchers.amount) as total_amount')
            ->groupByRaw('DATE(employee_payment_vouchers.created_at), employee_payment_vouchers.employee_id, employee_payment_vouchers.employee_name, employee_payment_vouchers.created_by, creators.name')
            ->orderByDesc('voucher_date')
            ->get();
        $vouchers = (clone $reportQuery)
            ->latest('employee_payment_vouchers.created_at')
            ->latest('employee_payment_vouchers.id')
            ->lazy(500);

        return ExcelCsvExport::download('employee-voucher-report-'.now()->format('Ymd-His').'.csv', function (callable $writeRow) use (
            $startDate,
            $endDate,
            $totalCount,
            $totalAmount,
            $dailySummaries,
            $vouchers
        ): void {
            $writeRow(['Daily Employee Voucher Report']);
            $writeRow(['Period', $startDate.' - '.$endDate]);
            $writeRow(['Voucher Count', $totalCount]);
            $writeRow(['Total Voucher Amount', number_format($totalAmount, 2, '.', '')]);
            $writeRow([]);
            $writeRow(['Daily Summary']);
            $writeRow(['Created Date', 'Employee', 'Created By', 'Voucher Count', 'Total Amount']);
            foreach ($dailySummaries as $summary) {
                $writeRow([
                    $summary->voucher_date,
                    $summary->employee_name,
                    $summary->creator_name ?: 'Unknown / legacy voucher',
                    (int) $summary->voucher_count,
                    number_format((float) $summary->total_amount, 2, '.', ''),
                ]);
            }
            $writeRow([]);
            $writeRow(['Voucher Details']);
            $writeRow(['Voucher No.', 'Created At', 'Created By', 'Employee', 'Expense Type', 'Payment Method', 'Reference', 'Notes', 'Amount']);
            foreach ($vouchers as $voucher) {
                $writeRow([
                    $voucher->voucher_no,
                    $voucher->created_at->format('Y-m-d H:i:s'),
                    $voucher->createdBy?->name ?: 'Unknown / legacy voucher',
                    $voucher->employee_name,
                    EmployeePaymentVoucher::EXPENSE_TYPES[$voucher->expense_type] ?? $voucher->expense_type,
                    EmployeePaymentVoucher::PAYMENT_METHODS[$voucher->payment_method] ?? $voucher->payment_method,
                    $voucher->reference,
                    $voucher->notes,
                    number_format((float) $voucher->amount, 2, '.', ''),
                ]);
            }
        });
    }

    /**
     * একজন কর্মীর সব ধরনের payment voucher-এর ledger এবং expense summary দেখায়।
     */
    public function ledger(int $employeeId)
    {
        $employee = User::findOrFail($employeeId);
        $expenseTypes = EmployeePaymentVoucher::EXPENSE_TYPES;
        $paymentMethods = EmployeePaymentVoucher::PAYMENT_METHODS;
        $employeeVouchers = EmployeePaymentVoucher::query()
            ->where('employee_id', $employee->id);

        $totalAmount = (clone $employeeVouchers)->sum('amount');
        $categoryTotals = (clone $employeeVouchers)
            ->selectRaw('expense_type, SUM(amount) as total')
            ->groupBy('expense_type')
            ->pluck('total', 'expense_type');
        $vouchers = $employeeVouchers
            ->latest('payment_date')
            ->latest('id')
            ->get();

        return view('backend.Accounts Entries.employeePaymentLedger', compact(
            'employee',
            'expenseTypes',
            'paymentMethods',
            'vouchers',
            'totalAmount',
            'categoryTotals'
        ));
    }

    /**
     * একজন কর্মীর voucher ledger-কে Excel-এ খোলা যায় এমন UTF-8 CSV হিসেবে download করে।
     */
    public function exportLedger(int $employeeId)
    {
        $employee = User::findOrFail($employeeId);
        $expenseTypes = EmployeePaymentVoucher::EXPENSE_TYPES;
        $paymentMethods = EmployeePaymentVoucher::PAYMENT_METHODS;
        $vouchers = EmployeePaymentVoucher::query()
            ->where('employee_id', $employee->id)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lazy(500);
        $organization = Organization::query()->find(1);
        $filename = 'employee-payment-ledger-' . $employee->id . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($vouchers, $employee, $expenseTypes, $paymentMethods, $organization) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the employee ledger export stream.');
            }

            // UTF-8 BOM দিলে Microsoft Excel-এ বাংলা/Unicode text সঠিকভাবে খোলে।
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Employee Payment Ledger'], ',', '"', '\\');
            if ($organization) {
                fputcsv($output, ['Organization', $this->spreadsheetSafeValue($organization->name)], ',', '"', '\\');
                fputcsv($output, ['Phone', $this->spreadsheetSafeValue($organization->phone)], ',', '"', '\\');
                fputcsv($output, ['Email', $this->spreadsheetSafeValue($organization->email)], ',', '"', '\\');
                fputcsv($output, ['Address', $this->spreadsheetSafeValue($organization->address)], ',', '"', '\\');
            }
            fputcsv($output, ['Employee', $this->spreadsheetSafeValue($employee->name)], ',', '"', '\\');
            fputcsv($output, ['Current Salary', $employee->salary ?? ''], ',', '"', '\\');
            fputcsv($output, [], ',', '"', '\\');
            fputcsv($output, [
                'Voucher No.',
                'Payment Date',
                'Expense Type',
                'Payment Method',
                'Reference',
                'Amount',
                'Notes',
            ], ',', '"', '\\');

            $totalAmountInCents = 0;
            foreach ($vouchers as $voucher) {
                $row = [
                    $this->spreadsheetSafeValue($voucher->voucher_no),
                    $voucher->payment_date->format('Y-m-d'),
                    $expenseTypes[$voucher->expense_type] ?? $this->spreadsheetSafeValue($voucher->expense_type),
                    $paymentMethods[$voucher->payment_method] ?? $this->spreadsheetSafeValue($voucher->payment_method),
                    $this->spreadsheetSafeValue($voucher->reference),
                    $voucher->amount,
                    $this->spreadsheetSafeValue($voucher->notes),
                ];
                fputcsv($output, $row, ',', '"', '\\');
                $totalAmountInCents += (int) round((float) $voucher->amount * 100);
            }

            fputcsv($output, [], ',', '"', '\\');
            fputcsv($output, ['All-time Total', '', '', '', '', number_format($totalAmountInCents / 100, 2, '.', '')], ',', '"', '\\');
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * যাচাই করা employee expense-কে transaction-এর মধ্যে voucher হিসেবে সংরক্ষণ করে।
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:users,id',
            'expense_type' => 'required|in:' . implode(',', array_keys(EmployeePaymentVoucher::EXPENSE_TYPES)),
            'payment_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'payment_method' => 'required|in:' . implode(',', array_keys(EmployeePaymentVoucher::PAYMENT_METHODS)),
            'reference' => 'nullable|string|max:100',
            'notes' => 'required_if:expense_type,other|nullable|string|max:2000',
        ]);

        $creatorId = $request->user()->id;

        $voucher = DB::transaction(function () use ($validated, $creatorId) {
            $employee = User::query()->lockForUpdate()->findOrFail($validated['employee_id']);
            $amountInCents = (int) round((float) $validated['amount'] * 100);

            // মূল Salary voucher কর্মীর নির্ধারিত বর্তমান বেতনের বেশি হতে পারে না।
            if ($validated['expense_type'] === 'salary') {
                $salaryInCents = (int) round((float) ($employee->salary ?? 0) * 100);

                if ($amountInCents > $salaryInCents) {
                    throw ValidationException::withMessages([
                        'amount' => $employee->salary
                            ? 'Salary voucher cannot exceed the employee salary of ৳' . number_format((float) $employee->salary, 2) . '.'
                            : 'This employee has no salary set. Add the employee salary before creating a salary voucher.',
                    ]);
                }
            }

            $voucher = EmployeePaymentVoucher::create([
                // প্রথমে null রাখা হয়; record ID পাওয়ার পর transaction-এর মধ্যেই unique number বসে।
                'voucher_no' => null,
                'employee_id' => $employee->id,
                'created_by' => $creatorId,
                'employee_name' => $employee->name,
                'expense_type' => $validated['expense_type'],
                'payment_date' => $validated['payment_date'],
                'amount' => number_format($amountInCents / 100, 2, '.', ''),
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $voucher->update([
                'voucher_no' => 'EPV-' . now()->format('Ymd') . '-' . str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $voucher;
        });

        return redirect()->route('employee-payment.index')
            ->with('success', 'Employee payment voucher ' . $voucher->voucher_no . ' created successfully.');
    }

    /**
     * Excel/CSV cell-এ formula হিসেবে চালানো ঠেকাতে untrusted text neutralize করে।
     */
    private function spreadsheetSafeValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'" . $value : $value;
    }
}
