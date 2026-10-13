<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class VoucherEntryController extends Controller
{
    /**
     * Debit/Credit voucher তৈরির form ও সাম্প্রতিক voucher দেখায়।
     */
    public function index(): View
    {
        $vouchers = VoucherEntry::query()
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);
        $todayVouchers = VoucherEntry::query()->whereDate('voucher_date', today());
        $todayCount = (clone $todayVouchers)->count();
        $todayAmount = (float) (clone $todayVouchers)->sum('amount');

        return view('backend.Accounts Entries.voucherEntryCreate', [
            'voucherTypes' => VoucherEntry::TYPES,
            'vouchers' => $vouchers,
            'todayCount' => $todayCount,
            'todayAmount' => $todayAmount,
        ]);
    }

    /**
     * User-entered expense/income type-সহ voucher তৈরি করে স্বয়ংক্রিয় code দেয়।
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_type' => ['required', 'string', 'in:'.implode(',', array_keys(VoucherEntry::TYPES))],
            'expense_income_type' => ['required', 'string', 'max:100'],
            'voucher_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $creator = $request->user();

        $voucher = DB::transaction(function () use ($validated, $creator) {
            $voucher = VoucherEntry::create([
                ...$validated,
                'voucher_code' => null,
                'amount' => number_format((float) $validated['amount'], 2, '.', ''),
                'created_by' => $creator->id,
                'created_by_name' => $creator->name,
            ]);

            // Primary key makes each automatically generated code unique.
            $prefix = $validated['voucher_type'] === 'debit' ? 'DBV' : 'CRV';
            $voucher->update([
                'voucher_code' => $prefix.'-'.Carbon::parse($validated['voucher_date'])->format('Ymd')
                    .'-'.str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $voucher;
        });

        return redirect()->route('voucher-entries.index')
            ->with('success', 'Voucher '.$voucher->voucher_code.' created successfully.');
    }

    /**
     * Voucher তারিখ ও তৈরিকারী user দিয়ে দৈনিক report filter করে।
     */
    public function report(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
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
            return redirect()->route('voucher-entries.report')
                ->withErrors($validator)
                ->withInput($request->query());
        }

        $filters = $validator->validated();
        $startDate = $filters['start_date'] ?? $filters['end_date'] ?? today()->toDateString();
        $endDate = $filters['end_date'] ?? $startDate;

        $reportQuery = VoucherEntry::query()
            ->whereBetween('voucher_date', [$startDate, $endDate]);

        if (! empty($filters['created_by'])) {
            $reportQuery->where('created_by', $filters['created_by']);
        }

        $dailySummaries = (clone $reportQuery)
            ->selectRaw('voucher_date, voucher_type, created_by, created_by_name')
            ->selectRaw('COUNT(*) as voucher_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('voucher_date', 'voucher_type', 'created_by', 'created_by_name')
            ->orderByDesc('voucher_date')
            ->get();

        $totalCount = (clone $reportQuery)->count();
        $debitTotal = (float) (clone $reportQuery)->where('voucher_type', 'debit')->sum('amount');
        $creditTotal = (float) (clone $reportQuery)->where('voucher_type', 'credit')->sum('amount');
        $vouchers = (clone $reportQuery)
            ->latest('voucher_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();
        $creators = User::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.Accounts Entries.voucherEntryReport', compact(
            'filters',
            'startDate',
            'endDate',
            'dailySummaries',
            'totalCount',
            'debitTotal',
            'creditTotal',
            'vouchers',
            'creators'
        ));
    }
}
