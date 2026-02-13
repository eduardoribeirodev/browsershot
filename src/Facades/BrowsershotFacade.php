<?php

namespace EduardoRibeiroDev\Browsershot\Facades;

use Illuminate\Support\Facades\Facade;

class BrowsershotFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'browsershot_service';
    }
}
