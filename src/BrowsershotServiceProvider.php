<?php

namespace EduardoRibeiroDev\Browsershot;

use App\Services\BrowsershotService;
use Illuminate\Support\ServiceProvider;

class BrowsershotServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('browsershot_service', function () {
            return new BrowsershotService();
        });
    }
}
