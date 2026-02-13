<?php

namespace EduardoRibeiroDev\Browsershot\Facades;

use EduardoRibeiroDev\Browsershot\Services\BrowsershotService;
use Illuminate\Support\Facades\Facade;

/**
 *    @method static self make(View|string $content)
 *    @method static self windowSize(int $width, int $height)
 *    @method static self proportion(string $proportion, int $baseWidth = 780)
 *    @method static self scale(int $scale)
 *    @method static self format(string $format)
 *    @method static self noSandbox(bool $noSandbox = true)
 *    @method static string generate()
 *    @method static Symfony\Component\HttpFoundation\StreamedResponse download(?string $fileName = null)
 *    @method static bool save(string $path)
 *    @method static string toBase64()
 *    @method static array getWindowSize()
 */
class Browsershot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BrowsershotService::class;
    }
}
