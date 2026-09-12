<?php

namespace App\Providers;

use App\Cart\SessionCart;
use App\Models\User;
use App\Services\ProductCreator;
use App\Services\ProductDeleter;
use App\Services\ProductUpdater;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when([ProductCreator::class, ProductUpdater::class, ProductDeleter::class])
            ->needs(Filesystem::class)
            ->give(fn (): Filesystem => Storage::disk('public'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', function (User $user): bool {
            return $user->is_admin === true;
        });

        View::composer('store.partials.header', function (\Illuminate\View\View $view): void {
            $view->with('cartQuantity', app(SessionCart::class)->totalQuantity());
        });
    }
}
