<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Force HTTPS on production to prevent Mixed Content errors
        if ($this->app->environment('production') || config('app.url') && str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        // Set PhpSpreadsheet temp directory within open_basedir allowed path
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }
        \PhpOffice\PhpSpreadsheet\Settings::setTempDir($tempPath);
    }
}
