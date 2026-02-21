<?php

namespace EduardoRibeiroDev\Browsershot\Facades;

use EduardoRibeiroDev\Browsershot\Services\BrowsershotService;
use Illuminate\Support\Facades\Facade;

/**
 *    @method static EduardoRibeiroDev\Browsershot\Services\BrowsershotService make(Illuminate\Contracts\View\View|string $content, array $data = [])
 */
class Browsershot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BrowsershotService::class;
    }
}
