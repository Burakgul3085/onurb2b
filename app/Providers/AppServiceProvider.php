<?php

namespace App\Providers;

use App\Enums\DealerApplicationStatus;
use App\Enums\Permission;
use App\Models\CartItem;
use App\Models\User;
use App\Policies\CatalogPolicy;
use App\Policies\PriceListPolicy;
use App\Policies\ReportPolicy;
use App\Policies\StockPolicy;
use App\Support\Ops\DatabaseDumper;
use App\Support\Ops\MysqldumpDumper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DatabaseDumper::class, MysqldumpDumper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            config(['session.secure' => true]);
        }

        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->environment('production')
                ? $rule->letters()->mixedCase()->numbers()->symbols()
                : $rule;
        });

        Gate::define('manageCatalog', [CatalogPolicy::class, 'manage']);
        Gate::define('viewStock', [StockPolicy::class, 'viewAny']);
        Gate::define('adjustStock', [StockPolicy::class, 'adjust']);
        Gate::define('viewPrices', [PriceListPolicy::class, 'viewAny']);
        Gate::define('viewReports', [ReportPolicy::class, 'viewAny']);
        Gate::define('managePrices', [PriceListPolicy::class, 'create']);
        Gate::define('shop', function (User $user) {
            if ($user->dealer_id === null || ! $user->can(Permission::ProductsView->value)) {
                return false;
            }

            $dealer = $user->dealer;

            return $dealer !== null
                && $dealer->is_active
                && $dealer->application_status === DealerApplicationStatus::Approved;
        });

        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();
            $count = 0;

            if ($user instanceof User && $user->can('shop')) {
                $count = CartItem::query()->whereHas('cart', function ($query) use ($user) {
                    $query->where('user_id', $user->id)->where('dealer_id', $user->dealer_id);
                })->count();
            }

            $view->with('cartCount', $count);
        });
    }
}
