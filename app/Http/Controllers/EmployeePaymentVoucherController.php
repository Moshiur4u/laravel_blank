<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $filename = 'employee-payment-ledger-' . $employee->id . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($vouchers, $employee, $expenseTypes, $paymentMethods) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the employee ledger export stream.');
            }

            // UTF-8 BOM দিলে Microsoft Excel-এ বাংলা/Unicode text সঠিকভাবে খোলে।
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Employee Payment Ledger'], ',', '"', '\\');
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

        $voucher = DB::transaction(function () use ($validated) {
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
