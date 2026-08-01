<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::useBootstrap();

        if (config('app.env') == 'production') {
            URL::forceScheme('https');
        }
    }
}
