<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Insumo;
use App\Models\Credito;
use App\Observers\CreditoObserver;
use Illuminate\Support\Facades\View;

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

    public function boot()
    {
        View::composer('*', function ($view) {
            $criticos = Insumo::whereColumn('stock', '<', 'stock_minimo')
                ->orderBy('stock')
                ->take(5)
                ->get(['nombre', 'stock', 'stock_minimo', 'id']);
            $view->with('alertasStockCritico', $criticos);
        });

        Credito::observe(CreditoObserver::class);
    }
}
