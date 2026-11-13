<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentVoucher;
use App\Models\CustomerLedger;
use App\Models\CustomerLedgerPayment;
use App\Models\ProductReturn;
use App\Models\PurchaseReturn;
use App\Models\SupplierLedgerPayment;
use App\Models\Supplierledger;
use App\Models\SaleInvoice;
use App\Models\User;
use App\Models\VoucherEntry;
use App\Support\ExcelCsvExport;
use App\Support\SaleInvoiceVoidService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
            'voucher' => ['nullable', 'string', 'max:100'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
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

        $voucherSearch = $filters['voucher'] ?? null;
        $createdBy = isset($filters['created_by']) ? (int) $filters['created_by'] : null;
        $reportQuery = fn () => $this->combinedVoucherQuery($startDate, $endDate, $voucherSearch, $createdBy);

        $totals = $reportQuery()
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN 1 ELSE 0 END) as employee_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN 1 ELSE 0 END) as debit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN 1 ELSE 0 END) as credit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN amount ELSE 0 END) as employee_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->first();

        $employeeTotal = (float) $totals->employee_total;
        $debitTotal = (float) $totals->debit_total;
        $creditTotal = (float) $totals->credit_total;

        $vouchers = $reportQuery()
            ->orderByDesc('report_date')
            ->orderByDesc('voucher_code')
            ->paginate(30)
            ->withQueryString();

        $creatorsQuery = User::query()->visibleToCurrentUser(); // combined report-এ বর্তমান user-এর জন্য দৃশ্যমান creator-দের query করে।

        return view('backend.Accounts Entries.combinedVoucherReport', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMonth' => $selectedMonth,
            'voucherSearch' => $voucherSearch,
            'selectedCreator' => $createdBy,
            'creators' => $creatorsQuery->orderBy('name')->get(['id', 'name']),
            'vouchers' => $vouchers,
            'employeeVoucherCount' => (int) $totals->employee_voucher_count,
            'debitVoucherCount' => (int) $totals->debit_voucher_count,
            'creditVoucherCount' => (int) $totals->credit_voucher_count,
            'employeeTotal' => $employeeTotal,
            'debitTotal' => $debitTotal,
            'creditTotal' => $creditTotal,
            'cashInHandTotal' => $creditTotal - ($employeeTotal + $debitTotal),
            'expenseTypes' => EmployeePaymentVoucher::EXPENSE_TYPES,
            'voucherTypes' => VoucherEntry::TYPES,
        ]);
    }

    /**
     * Combined report row-এর মূল voucher এবং linked ledger payment একই transaction-এ update করে।
     */
    public function update(Request $request, string $category, int $voucherId): RedirectResponse
    {
        if ($category === 'employee') {
            $validated = $request->validate([
                'expense_type' => ['required', 'in:'.implode(',', array_keys(EmployeePaymentVoucher::EXPENSE_TYPES))],
                'payment_date' => ['required', 'date', 'before_or_equal:today'],
                'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
                'reference' => ['nullable', 'string', 'max:100'],
                'notes' => ['required_if:expense_type,other', 'nullable', 'string', 'max:2000'],
            ]);

            DB::transaction(function () use ($validated, $voucherId) {
                $voucher = EmployeePaymentVoucher::query()->lockForUpdate()->findOrFail($voucherId);

                if ($validated['expense_type'] === 'salary') {
                    $employee = User::query()->lockForUpdate()->findOrFail($voucher->employee_id);
                    $salaryInCents = $this->moneyToCents($employee->salary ?? 0);

                    if ($this->moneyToCents($validated['amount']) > $salaryInCents) {
                        throw ValidationException::withMessages([
                            'amount' => $employee->salary
                                ? 'Salary voucher cannot exceed the employee salary of ৳'.number_format((float) $employee->salary, 2).'.'
                                : 'This employee has no salary set. Add the employee salary before saving a salary voucher.',
                        ]);
                    }
                }

                $voucher->update([
                    'expense_type' => $validated['expense_type'],
                    'payment_date' => $validated['payment_date'],
                    'amount' => number_format($this->moneyToCents($validated['amount']) / 100, 2, '.', ''),
                    'reference' => $validated['reference'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            });
        } else {
            $validated = $request->validate([
                'voucher_date' => ['required', 'date'],
                'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
                'expense_income_type' => ['nullable', 'string', 'max:100'],
                'reference' => ['nullable', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:2000'],
            ]);

            DB::transaction(function () use ($validated, $category, $voucherId) {
                $voucher = VoucherEntry::query()
                    ->where('voucher_type', $category)
                    ->lockForUpdate()
                    ->findOrFail($voucherId);
                if ($voucher->voucherable_type === ProductReturn::class) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Product return refund vouchers can only be changed by processing a product return.',
                    ]);
                }
                if ($voucher->voucherable_type === PurchaseReturn::class) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Purchase return refund vouchers can only be changed by processing a purchase return.',
                    ]);
                }
                $amount = number_format($this->moneyToCents($validated['amount']) / 100, 2, '.', '');

                if ($voucher->voucherable_type === Supplierledger::class) {
                    $this->updateSupplierPayment($voucher, $validated['voucher_date'], $amount);
                } elseif ($voucher->voucherable_type === CustomerLedger::class) {
                    $this->updateCustomerPayment($voucher, $validated['voucher_date'], $amount);
                } else {
                    $voucher->expense_income_type = $validated['expense_income_type'] ?? $voucher->expense_income_type;
                    $voucher->reference = $validated['reference'] ?? null;
                    $voucher->description = $validated['description'] ?? null;
                }

                $voucher->update([
                    'voucher_date' => $validated['voucher_date'],
                    'amount' => $amount,
                ]);
            });
        }

        return redirect()->route('voucher-report.combined', $request->only(['period', 'date', 'month', 'voucher', 'created_by']))
            ->with('success', 'Voucher updated successfully.');
    }

    /**
     * Combined report row মুছে দেওয়ার আগে linked supplier/customer payment ও balance সমন্বয় করে।
     */
    public function destroy(Request $request, string $category, int $voucherId): RedirectResponse
    {
        if ($category === 'employee') {
            DB::transaction(function () use ($voucherId) {
                EmployeePaymentVoucher::query()->lockForUpdate()->findOrFail($voucherId)->delete();
            });
        } else {
            $voucherSource = VoucherEntry::query()
                ->where('voucher_type', $category)
                ->findOrFail($voucherId);
            $saleInvoiceId = match ($voucherSource->voucherable_type) {
                SaleInvoice::class => $voucherSource->voucherable_id,
                CustomerLedger::class => CustomerLedger::query()
                    ->whereKey($voucherSource->voucherable_id)
                    ->value('sale_invoice_id'),
                default => null,
            };

            if ($saleInvoiceId) {
                $voidService = app(SaleInvoiceVoidService::class);
                $invoice = SaleInvoice::query()->findOrFail((int) $saleInvoiceId);
                if (! $voidService->canVoid($invoice)) {
                    return redirect()->route('voucher-report.combined', $request->only(['period', 'date', 'month', 'voucher', 'created_by']))
                        ->with('warning', 'You do not have permission to void this sale invoice, so the voucher was not deleted.');
                }

                $invoiceNo = $voidService->void((int) $saleInvoiceId);

                return redirect()->route('voucher-report.combined', $request->only(['period', 'date', 'month', 'voucher', 'created_by']))
                    ->with('success', "Invoice {$invoiceNo} was voided; its stock and payment entries were reversed.");
            }

            DB::transaction(function () use ($category, $voucherId) {
                $voucher = VoucherEntry::query()
                    ->where('voucher_type', $category)
                    ->lockForUpdate()
                    ->findOrFail($voucherId);
                if ($voucher->voucherable_type === ProductReturn::class) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Product return refund vouchers can only be removed by voiding the related invoice.',
                    ]);
                }
                if ($voucher->voucherable_type === PurchaseReturn::class) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Purchase return refund vouchers can only be removed by voiding the related purchase return.',
                    ]);
                }

                if ($voucher->voucherable_type === Supplierledger::class) {
                    $this->deleteSupplierPayment($voucher);
                } elseif ($voucher->voucherable_type === CustomerLedger::class) {
                    $this->deleteCustomerPayment($voucher);
                }

                $voucher->delete();
            });
        }

        return redirect()->route('voucher-report.combined', $request->only(['period', 'date', 'month', 'voucher', 'created_by']))
            ->with('success', 'Voucher deleted successfully.');
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
            'voucher' => ['nullable', 'string', 'max:100'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
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

        $voucherSearch = $filters['voucher'] ?? null;
        $createdBy = isset($filters['created_by']) ? (int) $filters['created_by'] : null;
        $totals = $this->combinedVoucherQuery($startDate, $endDate, $voucherSearch, $createdBy)
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN 1 ELSE 0 END) as employee_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN 1 ELSE 0 END) as debit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN 1 ELSE 0 END) as credit_voucher_count")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'employee' THEN amount ELSE 0 END) as employee_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->selectRaw("SUM(CASE WHEN voucher_category = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->first();
        $vouchers = $this->combinedVoucherQuery($startDate, $endDate, $voucherSearch, $createdBy)
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
            $writeRow(['Employee Voucher Count', (int) $totals->employee_voucher_count]);
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
    private function combinedVoucherQuery(
        string $startDate,
        string $endDate,
        ?string $voucherSearch = null,
        ?int $createdBy = null
    ): Builder
    {
        $employeeVouchers = DB::table('employee_payment_vouchers')
            ->leftJoin('users as creators', 'creators.id', '=', 'employee_payment_vouchers.created_by')
            ->whereBetween('employee_payment_vouchers.payment_date', [$startDate, $endDate])
            ->select('employee_payment_vouchers.payment_date as report_date')
            ->addSelect('employee_payment_vouchers.id as voucher_id')
            ->addSelect('employee_payment_vouchers.voucher_no as voucher_code')
            ->selectRaw("'employee' as voucher_category")
            ->addSelect('employee_payment_vouchers.expense_type as voucher_head')
            ->addSelect('employee_payment_vouchers.employee_name')
            ->addSelect('employee_payment_vouchers.created_by as created_by_id')
            ->selectRaw("COALESCE(creators.name, 'Unknown / legacy voucher') as created_by_name")
            ->addSelect('employee_payment_vouchers.amount')
            ->addSelect('employee_payment_vouchers.reference')
            ->addSelect('employee_payment_vouchers.notes as description')
            ->selectRaw('NULL as voucherable_type')
            ->selectRaw('NULL as voucherable_id');

        $generalVouchers = DB::table('voucher_entries')
            ->whereBetween('voucher_date', [$startDate, $endDate])
            ->select('voucher_date as report_date')
            ->addSelect('id as voucher_id')
            ->addSelect('voucher_code')
            ->addSelect('voucher_type as voucher_category')
            ->addSelect('expense_income_type as voucher_head')
            ->selectRaw('NULL as employee_name')
            ->addSelect('created_by as created_by_id')
            ->addSelect('created_by_name')
            ->addSelect('amount')
            ->addSelect('reference')
            ->addSelect('description')
            ->addSelect('voucherable_type')
            ->addSelect('voucherable_id');

        $query = DB::query()->fromSub($employeeVouchers->unionAll($generalVouchers), 'combined_vouchers');

        if ($voucherSearch !== null && $voucherSearch !== '') {
            $query->where('voucher_code', 'like', '%'.$voucherSearch.'%');
        }

        if ($createdBy !== null) {
            $query->where('created_by_id', $createdBy);
        }

        return $query;
    }

    private function updateSupplierPayment(VoucherEntry $voucher, string $date, string $amount): void
    {
        $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($voucher->voucherable_id);
        if ($ledger->purchaseReturns()->exists()) {
            throw ValidationException::withMessages([
                'voucher' => 'Supplier payment vouchers cannot be changed after a purchase return has been recorded.',
            ]);
        }
        $payment = SupplierLedgerPayment::query()
            ->where('supplierledger_id', $ledger->id)
            ->whereDate('payment_date', $voucher->voucher_date)
            ->where('amount', $voucher->amount)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        $oldCents = $this->moneyToCents($voucher->amount);
        $newCents = $this->moneyToCents($amount);
        $paidCents = $this->moneyToCents($ledger->payment);
        $dueCents = $this->moneyToCents($ledger->due);

        if ($newCents > 9_999_999_999) {
            throw ValidationException::withMessages([
                'amount' => 'Supplier payment cannot exceed ৳99,999,999.99.',
            ]);
        }

        if ($newCents > $dueCents + $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot be greater than the current due plus this payment.',
            ]);
        }

        if ($payment) {
            $payment->update([
                'payment_date' => $date,
                'amount' => $amount,
            ]);
        } elseif ($paidCents - $this->moneyToCents(DB::table('supplier_ledger_payments')->where('supplierledger_id', $ledger->id)->sum('amount')) < $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'The linked supplier payment could not be matched to its ledger history.',
            ]);
        }

        $ledger->update([
            'payment' => number_format(($paidCents - $oldCents + $newCents) / 100, 2, '.', ''),
            'due' => number_format(($dueCents + $oldCents - $newCents) / 100, 2, '.', ''),
        ]);
    }

    private function deleteSupplierPayment(VoucherEntry $voucher): void
    {
        $ledger = Supplierledger::query()->lockForUpdate()->findOrFail($voucher->voucherable_id);
        if ($ledger->purchaseReturns()->exists()) {
            throw ValidationException::withMessages([
                'voucher' => 'Supplier payment vouchers cannot be removed after a purchase return has been recorded.',
            ]);
        }
        $payment = SupplierLedgerPayment::query()
            ->where('supplierledger_id', $ledger->id)
            ->whereDate('payment_date', $voucher->voucher_date)
            ->where('amount', $voucher->amount)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
        $oldCents = $this->moneyToCents($voucher->amount);
        $paidCents = $this->moneyToCents($ledger->payment);

        if ($payment) {
            $payment->delete();
        } elseif ($paidCents - $this->moneyToCents(DB::table('supplier_ledger_payments')->where('supplierledger_id', $ledger->id)->sum('amount')) < $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'The linked supplier payment could not be matched to its ledger history.',
            ]);
        }

        $ledger->update([
            'payment' => number_format(($paidCents - $oldCents) / 100, 2, '.', ''),
            'due' => number_format(($this->moneyToCents($ledger->due) + $oldCents) / 100, 2, '.', ''),
        ]);
    }

    private function updateCustomerPayment(VoucherEntry $voucher, string $date, string $amount): void
    {
        $ledger = CustomerLedger::query()->lockForUpdate()->findOrFail($voucher->voucherable_id);
        $payment = CustomerLedgerPayment::query()
            ->where('customer_ledger_id', $ledger->id)
            ->whereDate('payment_date', $voucher->voucher_date)
            ->where('amount', $voucher->amount)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        $oldCents = $this->moneyToCents($voucher->amount);
        $newCents = $this->moneyToCents($amount);
        $receivedCents = $this->moneyToCents($ledger->payment_amount);
        $dueCents = $this->moneyToCents($ledger->due_amount);

        if ($newCents > $dueCents + $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot be greater than the current due plus this payment.',
            ]);
        }

        if ($payment) {
            $payment->update([
                'payment_date' => $date,
                'amount' => $amount,
            ]);
        } elseif ($receivedCents - $this->moneyToCents(DB::table('customer_ledger_payments')->where('customer_ledger_id', $ledger->id)->sum('amount')) < $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'The linked customer payment could not be matched to its ledger history.',
            ]);
        }

        $ledger->update([
            'payment_amount' => number_format(($receivedCents - $oldCents + $newCents) / 100, 2, '.', ''),
            'due_amount' => number_format(($dueCents + $oldCents - $newCents) / 100, 2, '.', ''),
        ]);
    }

    private function deleteCustomerPayment(VoucherEntry $voucher): void
    {
        $ledger = CustomerLedger::query()->lockForUpdate()->findOrFail($voucher->voucherable_id);
        $payment = CustomerLedgerPayment::query()
            ->where('customer_ledger_id', $ledger->id)
            ->whereDate('payment_date', $voucher->voucher_date)
            ->where('amount', $voucher->amount)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
        $oldCents = $this->moneyToCents($voucher->amount);
        $receivedCents = $this->moneyToCents($ledger->payment_amount);

        if ($payment) {
            $payment->delete();
        } elseif ($receivedCents - $this->moneyToCents(DB::table('customer_ledger_payments')->where('customer_ledger_id', $ledger->id)->sum('amount')) < $oldCents) {
            throw ValidationException::withMessages([
                'amount' => 'The linked customer payment could not be matched to its ledger history.',
            ]);
        }

        $ledger->update([
            'payment_amount' => number_format(($receivedCents - $oldCents) / 100, 2, '.', ''),
            'due_amount' => number_format(($this->moneyToCents($ledger->due_amount) + $oldCents) / 100, 2, '.', ''),
        ]);
    }

    private function moneyToCents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
