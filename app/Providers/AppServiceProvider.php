<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Relation::morphMap([
            'Admin'      => 'App\Admin',
            'Enterprise' => 'App\Enterprise',
            'Household'  => 'App\Household',
            'Collector'  => 'App\Collector',
            'Host'       => 'App\Host'
        ]);

    }
}
