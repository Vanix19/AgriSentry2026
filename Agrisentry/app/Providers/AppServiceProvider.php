<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A stalled MySQL read must not hold the web server for mysqlnd's
        // default 24 hours. Leave long-running console jobs unaffected.
        if (!$this->app->runningInConsole() && extension_loaded('mysqlnd')) {
            ini_set('mysqlnd.net_read_timeout', (string) max(1, (int) config('database.read_timeout', 10)));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
