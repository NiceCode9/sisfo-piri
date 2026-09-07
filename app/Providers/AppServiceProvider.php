<?php

namespace App\Providers;

use App\Models\Menu;
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
        View::composer('layouts.partials.sidebar', function ($view) {
            $user = auth()->user();

            $menus = Menu::parents()
                ->userCanAccess($user)
                ->with(['children' => fn ($query) => $query->userCanAccess($user), 'permissions'])
                ->get();

            $view->with('adminMenus', $menus);
        });
    }
}
