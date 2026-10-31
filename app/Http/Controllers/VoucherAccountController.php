<?php

namespace App\Http\Controllers;

use App\Models\VoucherAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherAccountController extends Controller
{
    public function index(): View
    {
        return view('backend.Accounts Entries.voucherAccountIndex', [
            'voucherAccounts' => VoucherAccount::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('backend.Accounts Entries.voucherAccountCreate');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:voucher_accounts,name'],
        ]);

        VoucherAccount::create($validated);

        return redirect()->route('voucher-accounts.index')
            ->with('success', 'Voucher account created successfully.');
    }

    public function edit(VoucherAccount $voucherAccount): View
    {
        return view('backend.Accounts Entries.voucherAccountEdit', compact('voucherAccount'));
    }

    public function update(Request $request, VoucherAccount $voucherAccount): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('voucher_accounts', 'name')->ignore($voucherAccount->id),
            ],
        ]);

        $voucherAccount->update($validated);

        return redirect()->route('voucher-accounts.index')
            ->with('success', 'Voucher account updated successfully.');
    }

    public function destroy(VoucherAccount $voucherAccount): RedirectResponse
    {
        $voucherAccount->delete();

        return redirect()->route('voucher-accounts.index')
            ->with('success', 'Voucher account deleted successfully.');
    }
}
