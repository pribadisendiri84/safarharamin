<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SiteProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $site = request()->attributes->get('site_profile');
            if (! $site instanceof SiteProfile) {
                $site = SiteProfile::current();
                if (app()->bound('request')) {
                    request()->attributes->set('site_profile', $site);
                }
            }
            $view->with('site', $site)->with('wa', $site->waNumber);
        });

        Gate::define('manage-users', fn (User $user) => $user->isSuperadmin());
        Gate::define('manage-catalog', fn (User $user) => $user->canManageCatalog());
        Gate::define('manage-inquiries', fn (User $user) => true);
    }
}
