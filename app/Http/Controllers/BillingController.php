<?php

namespace App\Http\Controllers;

use App\Models\BillingEntry;
use App\Models\BillingRecord;
use App\Models\BillingSetting;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function entry(): View|RedirectResponse
    {
        $organization = Organization::query()->find(1);
        if (! $organization) {
            return $this->organizationSetupRedirect();
        }

        $entries = BillingEntry::query()
            ->where('organization_id', $organization->id)
            ->with('activeRecords')
            ->orderByDesc('billing_month')
            ->get();
        $settings = BillingSetting::query()->first();

        return view('backend.billing.entry', compact('entries', 'organization', 'settings'));
    }

    public function records(): View|RedirectResponse
    {
        $organization = Organization::query()->find(1);
        if (! $organization) {
            return $this->organizationSetupRedirect();
        }

        $entries = BillingEntry::query()
            ->where('organization_id', $organization->id)
            ->with(['activeRecords', 'records.receiver', 'records.updater', 'records.reverser'])
            ->orderByDesc('billing_month')
            ->get()
            ->groupBy(fn (BillingEntry $entry) => $entry->billing_month->format('Y-m'));

        return view('backend.billing.records', compact('entries', 'organization'));
    }

    public function transactions(Request $request): View|RedirectResponse
    {
        $organization = Organization::query()->find(1);
        if (! $organization) {
            return $this->organizationSetupRedirect();
        }

        $validated = $request->validate([
            'from_month' => ['nullable', 'date_format:Y-m'],
            'to_month' => ['nullable', 'date_format:Y-m'],
        ]);
        $fromMonth = $validated['from_month'] ?? null;
        $toMonth = $validated['to_month'] ?? null;
        $fromDate = $fromMonth ? Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth() : null;
        $toDate = $toMonth ? Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth() : null;

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            throw ValidationException::withMessages([
                'to_month' => 'The end month must be the same as or later than the start month.',
            ]);
        }

        $query = BillingEntry::query()
            ->where('organization_id', $organization->id)
            ->with(['activeRecords', 'records.receiver', 'records.reverser'])
            ->orderByDesc('billing_month');

        if ($fromDate) {
            $query->whereDate('billing_month', '>=', $fromDate->toDateString());
        }

        if ($toDate) {
            $query->whereDate('billing_month', '<=', $toDate->toDateString());
        }

        $entries = $query->get();
        $totalBillAmount = $entries->sum(fn (BillingEntry $entry) => (float) $entry->amount);
        $totalPaidAmount = $entries->sum(fn (BillingEntry $entry) => $entry->paidAmount());
        $totalDueAmount = $entries->sum(fn (BillingEntry $entry) => $entry->outstandingAmount());
        $billCount = $entries->count();

        return view('backend.billing.transactions', compact(
            'billCount',
            'entries',
            'fromMonth',
            'toMonth',
            'totalBillAmount',
            'totalDueAmount',
            'totalPaidAmount',
        ));
    }

    public function generate(Request $request): RedirectResponse
    {
        $organization = Organization::query()->find(1);
        if (! $organization) {
            return $this->organizationSetupRedirect();
        }

        $validated = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $billingMonth = Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth();

        if (BillingEntry::query()
            ->where('organization_id', $organization->id)
            ->whereDate('billing_month', $billingMonth->toDateString())
            ->exists()) {
            throw ValidationException::withMessages([
                'billing_month' => 'A bill already exists for this month.',
            ]);
        }

        BillingEntry::query()->create([
            'organization_id' => $organization->id,
            'billing_month' => $billingMonth,
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('billing.entry')->with('status', 'Monthly bill generated.');
    }

    public function storePayment(Request $request, BillingEntry $billingEntry): RedirectResponse
    {
        DB::transaction(function () use ($request, $billingEntry): void {
            $entry = BillingEntry::query()
                ->where('organization_id', $this->currentOrganization()->id)
                ->lockForUpdate()
                ->findOrFail($billingEntry->id);
            $outstanding = $entry->outstandingAmount();

            if ($outstanding <= 0) {
                abort(422, 'This bill is already fully paid.');
            }

            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'gt:0', 'max:'.number_format($outstanding, 2, '.', '')],
                'received_at' => ['required', 'date'],
                'payment_method' => ['nullable', 'string', 'max:50'],
                'reference' => ['nullable', 'string', 'max:150'],
                'note' => ['nullable', 'string', 'max:2000'],
            ]);

            $entry->records()->create([
                ...$validated,
                'received_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('billing.entry')->with('status', 'Payment recorded.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alert_message' => ['nullable', 'string', 'max:1000'],
            'show_marquee_alert' => ['nullable', 'boolean'],
        ]);

        BillingSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'alert_message' => $validated['alert_message'] ?? null,
                'show_marquee_alert' => $request->boolean('show_marquee_alert'),
                'updated_by' => $request->user()->id,
            ],
        );

        return redirect()->route('billing.entry')->with('status', 'Marquee settings updated.');
    }

    public function accessOff(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $organization = Organization::query()->find(1);
        if (! $organization) {
            return $this->organizationSetupRedirect();
        }

        $hasOutstandingBills = DB::transaction(function () use ($organization): bool {
            $hasOutstandingBills = BillingEntry::query()
                ->where('organization_id', $organization->id)
                ->with('activeRecords')
                ->lockForUpdate()
                ->get()
                ->contains(fn (BillingEntry $entry) => $entry->outstandingAmount() > 0);

            if ($hasOutstandingBills) {
                User::query()
                    ->where('status', '!=', 0)
                    ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
                    ->update(['status' => 0]);
            }

            return $hasOutstandingBills;
        });

        if (! $hasOutstandingBills) {
            return redirect()->route('billing.entry')
                ->withErrors(['access_off' => 'There are no outstanding bills to enforce.']);
        }

        return redirect()->route('billing.entry')->with('status', 'All non-admin users have been deactivated.');
    }

    public function updateRecord(Request $request, BillingRecord $billingRecord): RedirectResponse
    {
        DB::transaction(function () use ($request, $billingRecord): void {
            $entry = BillingEntry::query()
                ->where('organization_id', $this->currentOrganization()->id)
                ->lockForUpdate()
                ->findOrFail($billingRecord->billing_entry_id);
            $record = BillingRecord::query()
                ->where('billing_entry_id', $entry->id)
                ->lockForUpdate()
                ->findOrFail($billingRecord->id);

            abort_if($record->reversed_at, 409, 'A reversed payment cannot be edited.');

            $otherActivePayments = $entry->activeRecords()
                ->whereKeyNot($record->id)
                ->sum('amount');
            $availableAmount = max(0, round((float) $entry->amount - (float) $otherActivePayments, 2));
            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'gt:0', 'max:'.number_format($availableAmount, 2, '.', '')],
                'received_at' => ['required', 'date'],
                'payment_method' => ['nullable', 'string', 'max:50'],
                'reference' => ['nullable', 'string', 'max:150'],
                'note' => ['nullable', 'string', 'max:2000'],
            ]);

            $record->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('billing.records')->with('status', 'Payment record updated.');
    }

    public function revertRecord(Request $request, BillingRecord $billingRecord): RedirectResponse
    {
        DB::transaction(function () use ($request, $billingRecord): void {
            $entry = BillingEntry::query()
                ->where('organization_id', $this->currentOrganization()->id)
                ->lockForUpdate()
                ->findOrFail($billingRecord->billing_entry_id);
            $record = BillingRecord::query()
                ->where('billing_entry_id', $entry->id)
                ->lockForUpdate()
                ->findOrFail($billingRecord->id);

            abort_if($record->reversed_at, 409, 'This payment has already been reverted.');

            $record->update([
                'reversed_at' => now(),
                'reversed_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('billing.records')->with('status', 'Payment reverted; the bill balance was restored.');
    }

    private function currentOrganization(): Organization
    {
        return Organization::query()->findOrFail(1);
    }

    private function organizationSetupRedirect(): RedirectResponse
    {
        return redirect()->route('organization.edit')
            ->with('warning', 'Set up the organization profile before using billing.');
    }
}
