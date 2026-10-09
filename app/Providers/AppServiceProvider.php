<?php

namespace App\Providers;

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
