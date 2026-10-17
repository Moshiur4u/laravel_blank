<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\Product;
use App\Models\UserActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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
        Event::listen(Login::class, function (Login $event): void {
            UserActivityLog::create([
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'event' => 'login',
                'description' => 'Signed in',
                'properties' => ['guard' => $event->guard],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user) {
                return;
            }

            UserActivityLog::create([
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'event' => 'logout',
                'description' => 'Signed out',
                'properties' => ['guard' => $event->guard],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

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
            $organization = Organization::query()->find(1);
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
                'organization' => $organization,
                'expiryNotifications' => $expiryNotifications,
                'lowStockNotifications' => $lowStockNotifications,
            ]);
        });
    }
}
