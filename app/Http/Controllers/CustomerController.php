<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerLedgerPayment; // customer ledger-এর অতিরিক্ত payment history সংরক্ষণ করে।
use App\Models\User;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException; // অসম্ভব balance update-কে validation error হিসেবে ফেরায়।

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::with(['employee', 'ledgers'])->latest()->get();
        $totalSales = $customers->sum(function ($customer) {
            return $customer->ledgers->sum('total_amount');
        });
        $totalReceived = $customers->sum(function ($customer) {
            return $customer->ledgers->sum('payment_amount');
        });
        $totalDue = $customers->sum(function ($customer) {
            return (float) $customer->opening_due
                + $customer->ledgers->sum('total_amount')
                - $customer->ledgers->sum('payment_amount');
        });

        return view('backend.customer.customerIndex', compact('customers', 'totalSales', 'totalReceived', 'totalDue'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::orderBy('name')->get();

        return view('backend.customer.addCustomer', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:temporary,permanent',
            'employee_id' => 'nullable|required_if:customer_type,permanent|exists:users,id',
            'shop_name' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code',
            'opening_due' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'customer_type' => $request->customer_type,
            'employee_id' => $request->employee_id,
            'shop_name' => $request->shop_name,
            'customer_code' => $request->customer_code,
            'opening_due' => $request->opening_due ?? 0,
            'status' => 1,
            'notes' => $request->notes,
        ]);

        if (! $customer->customer_code) {
            $customer->update([
                'customer_code' => 'CUST-' . str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            ]);
        }

        return redirect()->route('customer.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Display customer ledger.
     */
    public function ledger($id)
    {
        $customer = Customer::with([
            'employee',
            'ledgers' => function ($query) {
                $query->with(['receiver', 'employee', 'payments.receiver'])->latest(); // ledger, দায়িত্বপ্রাপ্ত কর্মী ও payment history একবারে আনা হয়।
            },
        ])->findOrFail($id);

        $employees = User::orderBy('name')->get();
        $totalSales = $customer->ledgers->sum('total_amount');
        $totalPayment = $customer->ledgers->sum('payment_amount');
        $totalDue = (float) $customer->opening_due
            + $customer->ledgers->sum('total_amount')
            - $customer->ledgers->sum('payment_amount');

        return view('backend.customer.customerLedger', compact('customer', 'employees', 'totalSales', 'totalPayment', 'totalDue'));
    }

    /**
     * Store a customer ledger entry.
     */
    public function ledgerStore(Request $request)
    {
        $creator = $request->user();
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id', // ledger entry-র customer বাধ্যতামূলক।
            'entry_type' => 'required|in:sale,payment', // কেবল sale বা standalone payment entry গ্রহণ করা হয়।
            'sale_date' => 'required|date', // entry-র ব্যবসায়িক তারিখ বাধ্যতামূলক।
            'product_name' => 'required_if:entry_type,sale|nullable|string|max:255', // sale হলে পণ্যের নাম লাগে।
            'quantity' => 'required_if:entry_type,sale|nullable|integer|min:1', // sale quantity ধনাত্মক পূর্ণসংখ্যা।
            'unit_price' => 'required_if:entry_type,sale|nullable|numeric|min:0', // sale-এর একক মূল্য ঋণাত্মক নয়।
            'payment_type' => 'required_if:entry_type,sale|nullable|in:cash,due,full_payment', // sale payment-এর ধরন নির্ধারণ করে।
            'payment_amount' => 'required_if:entry_type,payment|nullable|numeric|min:0', // standalone payment amount server-side-এ due-এর সঙ্গে যাচাই হয়।
            'employee_id' => 'nullable|exists:users,id', // sale-এর দায়িত্বপ্রাপ্ত কর্মী ঐচ্ছিক।
            'received_by' => 'required_if:entry_type,payment|nullable|exists:users,id', // payment entry-তে receiver আবশ্যক।
            'invoice_no' => 'nullable|string|max:100', // ঐচ্ছিক invoice নম্বর।
            'paid_by' => 'nullable|string|max:255', // payment প্রদানকারীর পরিচয়।
            'notes' => 'nullable|string', // ঐচ্ছিক entry note।
        ]);

        $customerId = DB::transaction(function () use ($validated, $creator) { // entry ও due validation একই transaction-এ সম্পন্ন হয়।
            $customer = Customer::query()->lockForUpdate()->findOrFail($validated['customer_id']); // একই customer-এ concurrent payment হলে row lock দেয়।
            $entryType = $validated['entry_type']; // পরবর্তী হিসাবের জন্য entry type স্থানীয়ভাবে রাখা হয়।
            $quantity = $entryType === 'sale' ? (int) $validated['quantity'] : 0; // payment row-তে quantity শূন্য থাকে।
            $unitPrice = $entryType === 'sale' ? (float) $validated['unit_price'] : 0; // payment row-তে unit price শূন্য থাকে।
            $totalCents = $entryType === 'sale' ? $this->moneyToCents($unitPrice * $quantity) : 0; // বিক্রির মোটকে পয়সায় হিসাব করে rounding drift এড়ায়।
            $paymentType = $entryType === 'payment' ? 'cash' : $validated['payment_type']; // payment entry-কে cash আদায় হিসেবে চিহ্নিত করে।
            $paymentCents = $entryType === 'sale' && in_array($paymentType, ['cash', 'full_payment'], true) // cash/full payment-এ পুরো sale amount পরিশোধ ধরা হয়।
                ? $totalCents // সম্পূর্ণ পরিশোধের অঙ্ক।
                : $this->moneyToCents($validated['payment_amount'] ?? 0); // due sale বা payment entry-র জমা অঙ্ক।

            if ($entryType === 'payment' && $paymentCents < 1) { // standalone receipt-এ শূন্য payment গ্রহণ করা হয় না।
                throw ValidationException::withMessages(['payment_amount' => 'Payment amount must be greater than zero.']); // পুরোনো minimum payment rule বজায় রাখে।
            }

            if ($entryType === 'sale' && $paymentCents > $totalCents) { // sale total-এর বেশি initial payment অনুমোদিত নয়।
                throw ValidationException::withMessages(['payment_amount' => 'Payment cannot be greater than the sale amount.']); // invalid amount-এর স্পষ্ট validation error।
            }

            if ($entryType === 'payment') { // standalone payment opening/আগের unallocated due পরিশোধ করে।
                $totals = CustomerLedger::query()->where('customer_id', $customer->id)->selectRaw('COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(payment_amount), 0) AS paid')->first(); // customer-এর বর্তমান sale ও received sum নেয়।
                $outstandingCents = $this->moneyToCents($customer->opening_due) + $this->moneyToCents($totals->total) - $this->moneyToCents($totals->paid); // opening due-সহ সর্বমোট বকেয়া পয়সায় বের করে।

                if ($paymentCents > $outstandingCents) { // outstanding-এর চেয়ে বড় payment reject করে।
                    throw ValidationException::withMessages(['payment_amount' => 'Payment cannot be greater than the current customer due.']); // overpayment-এর কারণ দেখায়।
                }
            }

            $ledger = CustomerLedger::create([ // validated sale/payment ledger row তৈরি করে।
                'customer_id' => $customer->id, // entry-র parent customer।
                'employee_id' => $validated['employee_id'] ?? $customer->employee_id, // submitted employee না থাকলে customer-এর assigned employee।
                'received_by' => $validated['received_by'] ?? null, // entry-র receiver user।
                'entry_type' => $entryType, // sale অথবা standalone payment।
                'sale_date' => $validated['sale_date'], // ব্যবসায়িক তারিখ।
                'invoice_no' => $validated['invoice_no'] ?? null, // optional invoice/reference নম্বর।
                'product_name' => $validated['product_name'] ?? null, // sale product; payment entry-তে null।
                'paid_by' => $validated['paid_by'] ?? null, // standalone payment প্রদানকারীর নাম।
                'quantity' => $quantity, // sale quantity; payment entry-তে শূন্য।
                'unit_price' => $unitPrice, // sale unit price; payment entry-তে শূন্য।
                'total_amount' => $totalCents / 100, // পয়সা থেকে database decimal-এ sale total।
                'payment_type' => $paymentType, // sale-এর cash/due/full-payment ধরন।
                'payment_amount' => $paymentCents / 100, // entry-তে আদায় হওয়া অর্থ।
                'due_amount' => ($totalCents - ($entryType === 'sale' ? $paymentCents : 0)) / 100, // sale due; standalone payment-এ শূন্য।
                'notes' => $validated['notes'] ?? null, // ঐচ্ছিক entry note।
            ]);

            if ($paymentCents > 0) {
                VoucherEntry::createWithCode([
                    'voucher_type' => 'credit',
                    'expense_income_type' => $customer->name,
                    'voucher_date' => $validated['sale_date'],
                    'amount' => number_format($paymentCents / 100, 2, '.', ''),
                    'reference' => $validated['invoice_no'] ?? null,
                    'description' => 'Payment received from customer '.$customer->name,
                    'created_by' => $creator?->id,
                    'created_by_name' => $creator?->name ?? 'System',
                    'voucherable_type' => CustomerLedger::class,
                    'voucherable_id' => $ledger->id,
                ]);
            }

            return $customer->id; // transaction শেষে customer ledger route-এ ফেরার ID।
        });

        return redirect()->route('customer.ledger', $customerId)->with('success', 'Ledger entry saved successfully.'); // সফল সংরক্ষণের পর ledger দেখায়।
    }

    /**
     * নির্দিষ্ট customer ledger row-র sale বা payment field edit করার form দেখায়।
     */
    public function ledgerEdit($id)
    {
        $ledger = CustomerLedger::with(['payments.receiver'])->findOrFail($id); // edit form-এর জন্য ledger ও payment history লোড করে।
        $customer = Customer::with('employee')->findOrFail($ledger->customer_id); // customer তথ্য ও দায়িত্বপ্রাপ্ত কর্মী আনে।
        $employees = User::orderBy('name')->get(); // dropdown-এ কর্মীদের সাজানো তালিকা দেয়।
        $ledgers = CustomerLedger::with(['receiver', 'employee', 'payments.receiver'])->where('customer_id', $customer->id)->latest()->get(); // edit অবস্থাতেও সম্পূর্ণ ledger table দেখায়।
        $totalSales = $ledgers->sum('total_amount'); // edit page summary-র মোট বিক্রি।
        $totalPayment = $ledgers->sum('payment_amount'); // edit page summary-র মোট আদায়।
        $totalDue = $this->moneyToCents($customer->opening_due) / 100 + $totalSales - $totalPayment; // opening due-সহ মোট বকেয়া।

        return view('backend.customer.customerLedger', compact('customer', 'employees', 'ledgers', 'totalSales', 'totalPayment', 'totalDue', 'ledger')); // edit mode-সহ একই ledger page render করে।
    }

    /**
     * Sale-এর পণ্যের তথ্য বা standalone payment entry-র amount/date update করে।
     */
    public function ledgerUpdate(Request $request, $id)
    {
        $ledger = CustomerLedger::findOrFail($id); // edit হওয়া ledger row চিহ্নিত করে।
        $rules = [ // উভয় entry type-এর সাধারণ field validation।
            'sale_date' => 'required|date', // ledger row-র তারিখ বাধ্যতামূলক।
            'invoice_no' => 'nullable|string|max:100', // ঐচ্ছিক invoice নম্বর।
            'received_by' => 'nullable|exists:users,id', // receiver থাকলে users table-এ থাকতে হবে।
            'notes' => 'nullable|string', // ঐচ্ছিক note।
        ];

        if ($ledger->entry_type === 'sale') { // sale edit-এ product/quantity/price পরিবর্তন করা যায়।
            $rules += [ // sale-specific validation যোগ করে।
                'product_name' => 'required|string|max:255', // sale-এর product name আবশ্যক।
                'quantity' => 'required|integer|min:1', // quantity ধনাত্মক পূর্ণসংখ্যা।
                'unit_price' => 'required|numeric|min:0', // unit price ঋণাত্মক হতে পারে না।
                'employee_id' => 'nullable|exists:users,id', // assigned employee ঐচ্ছিক।
            ];
        } else { // payment edit-এ amount ও প্রদানকারীর তথ্য পরিবর্তন করা যায়।
            $rules += [ // payment-specific validation যোগ করে।
                'payment_amount' => 'required|numeric|min:0.01', // payment amount শূন্যের বেশি হতে হবে।
                'paid_by' => 'nullable|string|max:255', // payer-এর নাম/পরিচয় ঐচ্ছিক।
            ];
        }

        $validated = $request->validate($rules); // request field যাচাই করে।

        DB::transaction(function () use ($validated, $ledger) { // হিসাব ও row update atomically করে।
            $customer = Customer::query()->lockForUpdate()->findOrFail($ledger->customer_id); // একই customer-এর balance পরিবর্তনে lock নেয়।
            $lockedLedger = CustomerLedger::query()->lockForUpdate()->findOrFail($ledger->id); // সংশ্লিষ্ট entry-তে concurrent update আটকায়।

            if ($lockedLedger->entry_type === 'sale') { // sale row-র total, paid এবং due পুনর্গণনা করে।
                $totalCents = $this->moneyToCents((float) $validated['unit_price'] * (int) $validated['quantity']); // quantity × unit price পয়সায় হিসাব করে।
                $paidCents = $this->moneyToCents($lockedLedger->payment_amount); // আগে জমা হওয়া সব টাকা অপরিবর্তিত রাখে।

                if ($totalCents < $paidCents) { // paid amount-এর চেয়ে কম sale total হলে due ঋণাত্মক হবে।
                    throw ValidationException::withMessages(['unit_price' => 'Sale total cannot be less than the amount already received.']); // অসামঞ্জস্যপূর্ণ total reject করে।
                }

                $lockedLedger->update([ // sale-এর editable field ও derived amount update করে।
                    'sale_date' => $validated['sale_date'], // sale-এর ব্যবসায়িক তারিখ।
                    'invoice_no' => $validated['invoice_no'] ?? null, // invoice নম্বর।
                    'product_name' => $validated['product_name'], // পণ্যের নাম।
                    'quantity' => $validated['quantity'], // বিক্রিত quantity।
                    'unit_price' => $validated['unit_price'], // একক মূল্য।
                    'total_amount' => $totalCents / 100, // server হিসাব করা মোট।
                    'payment_type' => $totalCents > $paidCents ? 'due' : ($lockedLedger->payment_type === 'cash' ? 'cash' : 'full_payment'), // নতুন total অনুযায়ী due/full status ঠিক করে।
                    'due_amount' => ($totalCents - $paidCents) / 100, // সব জমা বাদে অবশিষ্ট sale due।
                    'employee_id' => $validated['employee_id'] ?? null, // assigned employee।
                    'received_by' => $validated['received_by'] ?? null, // receiver।
                    'notes' => $validated['notes'] ?? null, // sale note।
                ]);
            } else { // standalone payment edit-এ নতুন amount-ও বকেয়া অতিক্রম করতে পারবে না।
                $totals = CustomerLedger::query()->where('customer_id', $customer->id)->selectRaw('COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(payment_amount), 0) AS paid')->first(); // customer-এর সব ledger total আনে।
                $dueBeforeCurrentPayment = $this->moneyToCents($customer->opening_due) + $this->moneyToCents($totals->total) - $this->moneyToCents($totals->paid) + $this->moneyToCents($lockedLedger->payment_amount); // বর্তমান payment বাদ দিয়ে পুনরায় অনুমোদনযোগ্য সর্বোচ্চ amount বের করে।
                $paymentCents = $this->moneyToCents($validated['payment_amount']); // update amount-কে pয়সায় রূপান্তর করে।

                if ($paymentCents > $dueBeforeCurrentPayment) { // balance-এ যতটুকু due আছে তার বেশি গ্রহণ করা হয় না।
                    throw ValidationException::withMessages(['payment_amount' => 'Payment cannot be greater than the current customer due.']); // overpayment error-সহ update বাতিল করে।
                }

                $lockedLedger->update([ // standalone payment row-র editable field update করে।
                    'sale_date' => $validated['sale_date'], // payment date।
                    'invoice_no' => $validated['invoice_no'] ?? null, // receipt/invoice number।
                    'payment_amount' => $paymentCents / 100, // নতুন payment amount।
                    'paid_by' => $validated['paid_by'] ?? null, // payer পরিচয়।
                    'received_by' => $validated['received_by'] ?? null, // receiver।
                    'notes' => $validated['notes'] ?? null, // payment note।
                ]);
            }
        });

        return redirect()->route('customer.ledger', $ledger->customer_id)->with('success', 'Ledger entry updated successfully.'); // update শেষে সংশ্লিষ্ট ledger-এ ফেরায়।
    }

    /**
     * Sale ledger entry-তে তারিখভিত্তিক payment যোগ করে paid/due amount সমন্বয় করে।
     */
    public function ledgerPaymentStore(Request $request, $id)
    {
        $creator = $request->user();
        $validated = $request->validate([ // payment form-এর input যাচাই।
            'payment_date' => 'required|date|before_or_equal:today', // ভবিষ্যতের payment date গ্রহণ করা হয় না।
            'amount' => 'required|numeric|min:0.01', // payment amount ধনাত্মক হতে হবে।
            'received_by' => 'nullable|exists:users,id', // receiver ঐচ্ছিক কিন্তু valid user হতে হবে।
            'notes' => 'nullable|string', // payment note ঐচ্ছিক।
        ]);

        $customerId = DB::transaction(function () use ($validated, $id, $creator) { // child payment ও parent totals একই transaction-এ লেখে।
            $ledger = CustomerLedger::query()->findOrFail($id); // সংশ্লিষ্ট sale ledger row খুঁজে।
            $customer = Customer::query()->lockForUpdate()->findOrFail($ledger->customer_id); // customer balance-এ concurrent payment serialize করে।
            $ledger = CustomerLedger::query()->lockForUpdate()->findOrFail($id); // sale row-তে concurrent payment serialize করে।

            if ($ledger->entry_type !== 'sale') { // standalone payment row-তে nested payment যোগ করা যাবে না।
                throw ValidationException::withMessages(['amount' => 'Additional payments can only be recorded against a sale.']); // অনুপযুক্ত entry type-এর error।
            }

            $amountCents = $this->moneyToCents($validated['amount']); // জমার অঙ্ক পয়সায় হিসাব করে।
            $dueCents = $this->moneyToCents($ledger->due_amount); // বর্তমান sale due পয়সায় আনে।

            if ($amountCents > $dueCents) { // sale-এর বকেয়ার বেশি payment reject করে।
                throw ValidationException::withMessages(['amount' => 'Payment cannot be greater than the current sale due.']); // entry-specific overpayment error।
            }

            CustomerLedgerPayment::create([ // date/amount/receiver-সহ child payment history তৈরি করে।
                'customer_ledger_id' => $ledger->id, // parent sale ledger row।
                'payment_date' => $validated['payment_date'], // received date।
                'amount' => $amountCents / 100, // exact decimal amount।
                'received_by' => $validated['received_by'] ?? null, // optional receiver user ID।
                'notes' => $validated['notes'] ?? null, // payment-specific note।
            ]);

            VoucherEntry::createWithCode([
                'voucher_type' => 'credit',
                'expense_income_type' => $customer->name,
                'voucher_date' => $validated['payment_date'],
                'amount' => number_format($amountCents / 100, 2, '.', ''),
                'reference' => $ledger->invoice_no,
                'description' => 'Payment received from customer '.$customer->name,
                'created_by' => $creator?->id,
                'created_by_name' => $creator?->name ?? 'System',
                'voucherable_type' => CustomerLedger::class,
                'voucherable_id' => $ledger->id,
            ]);

            $ledger->update([ // parent sale-এর aggregate payment ও due হালনাগাদ করে।
                'payment_amount' => ($this->moneyToCents($ledger->payment_amount) + $amountCents) / 100, // initial payment-এ নতুন amount যোগ করে।
                'due_amount' => ($dueCents - $amountCents) / 100, // নতুন payment বাদে due।
            ]);

            return $customer->id; // redirect-এর জন্য customer ID ফেরায়।
        });

        return redirect()->route('customer.ledger', $customerId)->with('success', 'Customer payment recorded successfully.'); // সফল payment-এর পর ledger দেখায়।
    }

    /**
     * একটি customer receipt edit করে sale-এর আদায়/বকেয়া এবং সংশ্লিষ্ট credit voucher হালনাগাদ করে।
     */
    public function ledgerPaymentUpdate(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
            'received_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $paymentLedgerId = CustomerLedgerPayment::query()->findOrFail($paymentId)->customer_ledger_id;

        $customerId = DB::transaction(function () use ($validated, $paymentId, $paymentLedgerId) {
            $ledger = CustomerLedger::query()->findOrFail($paymentLedgerId);
            $customer = Customer::query()->lockForUpdate()->findOrFail($ledger->customer_id);
            $ledger = CustomerLedger::query()->lockForUpdate()->findOrFail($paymentLedgerId);
            $payment = CustomerLedgerPayment::query()->lockForUpdate()->findOrFail($paymentId);

            if ($ledger->entry_type !== 'sale') {
                throw ValidationException::withMessages([
                    'amount' => 'Receipt history can only be edited for a sale.',
                ]);
            }

            $oldAmountCents = $this->moneyToCents($payment->amount);
            $newAmountCents = $this->moneyToCents($validated['amount']);
            $dueCents = $this->moneyToCents($ledger->due_amount);

            if ($newAmountCents > $dueCents + $oldAmountCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the current due plus this receipt.',
                ]);
            }

            $voucher = VoucherEntry::query()
                ->where('voucherable_type', CustomerLedger::class)
                ->where('voucherable_id', $ledger->id)
                ->where('voucher_type', 'credit')
                ->whereDate('voucher_date', $payment->payment_date->toDateString())
                ->where('amount', number_format($oldAmountCents / 100, 2, '.', ''))
                ->orderBy('id')
                ->first();

            if (! $voucher) {
                $voucher = VoucherEntry::query()
                    ->whereNull('voucherable_type')
                    ->whereNull('voucherable_id')
                    ->where('voucher_type', 'credit')
                    ->where('expense_income_type', $customer->name)
                    ->where('description', 'Payment received from customer '.$customer->name)
                    ->where('reference', $ledger->invoice_no)
                    ->whereDate('voucher_date', $payment->payment_date->toDateString())
                    ->where('amount', number_format($oldAmountCents / 100, 2, '.', ''))
                    ->orderBy('id')
                    ->firstOrFail();
            }

            $voucher->update([
                'voucher_date' => $validated['payment_date'],
                'amount' => number_format($newAmountCents / 100, 2, '.', ''),
            ]);
            $payment->update([
                'payment_date' => $validated['payment_date'],
                'amount' => number_format($newAmountCents / 100, 2, '.', ''),
                'received_by' => $validated['received_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            $ledger->update([
                'payment_amount' => ($this->moneyToCents($ledger->payment_amount) - $oldAmountCents + $newAmountCents) / 100,
                'due_amount' => ($dueCents + $oldAmountCents - $newAmountCents) / 100,
            ]);

            return $customer->id;
        });

        return redirect()->route('customer.ledger', $customerId)
            ->with('success', 'Customer payment updated successfully.');
    }

    /**
     * নির্দিষ্ট customer ledger row মুছে দেয়; payment child migration-এ cascade হয়।
     */
    public function ledgerDestroy($id)
    {
        $customerId = DB::transaction(function () use ($id) {
            $ledger = CustomerLedger::with('payments')->findOrFail($id);
            $customer = Customer::findOrFail($ledger->customer_id);
            $customerId = $ledger->customer_id;
            $paymentRows = $ledger->payments
                ->map(fn (CustomerLedgerPayment $payment) => [
                    'date' => $payment->payment_date->toDateString(),
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                ])
                ->all();
            $initialPayment = round(
                (float) $ledger->payment_amount - $ledger->payments->sum(fn (CustomerLedgerPayment $payment) => (float) $payment->amount),
                2
            );

            if ($initialPayment > 0) {
                $paymentRows[] = [
                    'date' => $ledger->sale_date->toDateString(),
                    'amount' => number_format($initialPayment, 2, '.', ''),
                ];
            }

            VoucherEntry::deleteForSource(
                $ledger,
                'credit',
                $customer->name,
                'Payment received from customer '.$customer->name,
                $ledger->invoice_no,
                $paymentRows
            );
            $ledger->delete();

            return $customerId;
        });

        return redirect()->route('customer.ledger', $customerId)->with('success', 'Ledger entry deleted successfully.'); // delete শেষে customer ledger-এ ফেরায়।
    }

    /**
     * নির্বাচিত sale invoice বা payment receipt-এর জন্য print view তৈরি করে।
     */
    public function ledgerInvoice($id)
    {
        $ledger = CustomerLedger::with(['customer', 'employee', 'receiver', 'payments.receiver'])->findOrFail($id); // invoice-এর সব customer/employee/payment তথ্য একবারে আনে।
        $invoiceNumber = $ledger->invoice_no ?: 'CUS-' . str_pad((string) $ledger->id, 6, '0', STR_PAD_LEFT); // invoice number না থাকলে stable customer reference দেখায়।

        return view('backend.customer.customerLedgerInvoice', compact('ledger', 'invoiceNumber')); // print invoice view-க்கு record-уудыг পাঠায়।
    }

    // মুদ্রার পরিমাণকে পূর্ণ পয়সায় রূপান্তর করে floating-point rounding সমস্যা এড়ায়।
    private function moneyToCents($amount): int
    {
        return (int) round((float) $amount * 100); // decimal amount-কে integer cents হিসেবে ফেরায়।
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $employees = User::orderBy('name')->get();

        return view('backend.customer.addCustomer', compact('customer', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'required|in:temporary,permanent',
            'employee_id' => 'nullable|required_if:customer_type,permanent|exists:users,id',
            'shop_name' => 'nullable|string|max:255',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code,' . $customer->id,
            'opening_due' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'customer_type' => $request->customer_type,
            'employee_id' => $request->employee_id,
            'shop_name' => $request->shop_name,
            'customer_code' => $request->customer_code ?? $customer->customer_code,
            'opening_due' => $request->opening_due ?? 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('customer.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customer.index')->with('success', 'Customer deleted successfully.');
    }
}
