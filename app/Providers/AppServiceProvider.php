<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // invoice/report-গুলোর print header-এ একই organization profile সরবরাহ করে।
        View::composer([
            'backend.Sales.salesInvoice',
            'backend.Sales.salesReport',
            'backend.reports.productinventory',
            'backend.Accounts Entries.combinedVoucherReport',
            'backend.Accounts Entries.voucherEntryReport',
            'backend.Accounts Entries.VoucherEntries',
            'backend.Accounts Entries.employeePaymentLedger',
            'backend.customer.customerLedger',
            'backend.Supplier.supplierledger',
        ], function ($view) {
            $view->with('organization', Organization::query()->find(1));
        });

        View::composer('dashboard.dashboard', function ($view) {
            $expiryNotifications = Product::query()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', today()->addMonth())
                ->orderBy('expiry_date')
                ->get();

            $lowStockNotifications = Product::query()
                ->whereRaw('CAST(unit AS UNSIGNED) <= 10')
                ->orderByRaw('CAST(unit AS UNSIGNED) ASC')
                ->get();

            $view->with([
                'expiryNotifications' => $expiryNotifications,
                'lowStockNotifications' => $lowStockNotifications,
            ]);
        });
    }
}
