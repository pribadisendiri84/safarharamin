<?php

namespace App\Providers;

use App\Models\Airline;
use App\Models\Hotel;
use App\Models\User;
use App\Support\SiteProfile;
use App\Support\WaMessages;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
            $view->with('site', $site)
                ->with('wa', $site->waNumber)
                ->with('waFloat', WaMessages::floatButton());
        });

        View::composer('partials.package-card-catalog', function ($view) {
            $cached = request()->attributes->get('catalog_card_assets');
            if (is_array($cached)) {
                $view->with('catalogAirlines', $cached['airlines'])
                    ->with('catalogHotels', $cached['hotels']);

                return;
            }

            $airlines = collect();
            $hotels = collect();

            if (Schema::hasTable('airlines') && Schema::hasColumn('airlines', 'logo')) {
                $airlines = Airline::query()->get(['name', 'logo'])->keyBy('name');
            }
            if (Schema::hasTable('hotels') && Schema::hasColumn('hotels', 'logo')) {
                $hotels = Hotel::query()->get(['name', 'location', 'logo', 'stars'])
                    ->keyBy(fn (Hotel $hotel) => $hotel->location.'|'.$hotel->name);
            }

            request()->attributes->set('catalog_card_assets', compact('airlines', 'hotels'));
            $view->with('catalogAirlines', $airlines)
                ->with('catalogHotels', $hotels);
        });

        Gate::define('manage-users', fn (User $user) => $user->isSuperadmin());
        Gate::define('manage-catalog', fn (User $user) => $user->canManageCatalog());
        Gate::define('manage-inquiries', fn (User $user) => true);
    }
}
