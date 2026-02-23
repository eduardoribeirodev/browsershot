<?php

namespace EduardoRibeiroDev\Browsershot\Services;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrowsershotService
{
    private ?string $html = null;
    private ?string $url = null;

    // Configurações Gerais
    private ?array $size = null;
    private ?array $center = null;
    private float $scale = 1;
    private string $extension = 'png';
    private bool $noSandbox = true;

    // Configurações de PDF
    private ?bool $landscape = false;
    private ?array $margins = null;
    private ?string $pages = null;

    // Callback para manipulação direta
    private ?Closure $modifyBrowsershotUsing = null;

    private const PX_PER_UNIT = [
        'px' => 1,
        'mm' => 3.7795,
        'cm' => 37.795,
        'in' => 96,
    ];

    private const NAMED_SIZES = [
        'a0'      => [841,  1189],
        'a1'      => [594,  841],
        'a2'      => [420,  594],
        'a3'      => [297,  420],
        'a4'      => [210,  297],
        'a5'      => [148,  210],
        'a6'      => [105,  148],
        'letter'  => [216,  279],
        'legal'   => [216,  356],
        'tabloid' => [279,  432],
        'ledger'  => [432,  279],
    ];

    public static function make(View|string $content, array $data = []): self
    {
        $instance = new static();

        if ($content instanceof View) {
            $instance->html = $content->render();
        } elseif (view()->exists($content)) {
            $instance->html = view($content, $data)->render();
        } elseif (filter_var($content, FILTER_VALIDATE_URL)) {
            $instance->url = $content;
        } else {
            $instance->html = $content;
        }

        return $instance;
    }

    public function modifyBrowsershotUsing(Closure $callback): self
    {
        $this->modifyBrowsershotUsing = $callback;
        return $this;
    }

    public function size(int $width, int $height, string $unit = 'px'): self
    {
        if (!isset(self::PX_PER_UNIT[$unit])) {
            throw new \InvalidArgumentException("Unidade desconhecida: {$unit}");
        }

        $pxFactor = self::PX_PER_UNIT[$unit];
        $this->size = [$width * $pxFactor, $height * $pxFactor];

        return $this;
    }

    public function center(int $x, int $y): self
    {
        $this->center = [$x, $y];
        return $this;
    }

    public function clip(int $x, int $y, int $width, int $height): self
    {
        $this->center($x, $y);
        $this->size($width, $height);
        return $this;
    }

    public function scale(float $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function extension(string $extension): self
    {
        $this->extension = strtolower($extension);
        return $this;
    }

    public function aspectRatio(string|float $ratio): self
    {
        if (!$this->size) {
            throw new \LogicException("Defina o tamanho antes de configurar a proporção.");
        }

        if (is_string($ratio)) {
            $parts = explode(':', $ratio);
            if (count($parts) != 2) {
                throw new \InvalidArgumentException("Proporção inválida: {$ratio}");
            }
            $ratio = (float) $parts[0] / (float) $parts[1];
        }

        [$width, $height] = $this->size;
        $currentRatio = $width / $height;

        if ($currentRatio > $ratio) {
            // Ajusta largura
            $newWidth = (int) round($height * $ratio);
            $this->size($newWidth, $height);
        } else {
            // Ajusta altura
            $newHeight = (int) round($width / $ratio);
            $this->size($width, $newHeight);
        }

        return $this;
    }

    public function format(string $format, float $scale = 1)
    {
        $normalizedFormat = strtolower($format);

        if (!isset(self::NAMED_SIZES[$normalizedFormat])) {
            throw new \InvalidArgumentException("Formato de papel desconhecido: {$normalizedFormat}");
        }

        $size = array_map(fn($dim) => $dim * $scale, self::NAMED_SIZES[$normalizedFormat]);
        return $this->size(...$size, unit: 'mm');
    }

    public function a0(): self
    {
        return $this->format('A0');
    }
    public function a1(): self
    {
        return $this->format('A1');
    }
    public function a2(): self
    {
        return $this->format('A2');
    }
    public function a3(): self
    {
        return $this->format('A3');
    }
    public function a4(): self
    {
        return $this->format('A4');
    }
    public function a5(): self
    {
        return $this->format('A5');
    }
    public function a6(): self
    {
        return $this->format('A6');
    }
    public function letter(): self
    {
        return $this->format('Letter');
    }
    public function legal(): self
    {
        return $this->format('Legal');
    }
    public function tabloid(): self
    {
        return $this->format('Tabloid');
    }
    public function ledger(): self
    {
        return $this->format('Ledger');
    }

    public function jpeg(): self
    {
        return $this->extension('jpeg');
    }

    public function webp(): self
    {
        return $this->extension('webp');
    }

    public function png(): self
    {
        return $this->extension('png');
    }

    public function pdf(): self
    {
        return $this->extension('pdf');
    }

    public function margin(int $size, string $unit = 'mm'): self
    {
        return $this->margins($size, $size, $size, $size, $unit);
    }

    public function marginTop(int $size, string $unit = 'mm'): self
    {
        $this->margins['top'] = compact('size', 'unit');
        return $this;
    }

    public function marginRight(int $size, string $unit = 'mm'): self
    {
        $this->margins['right'] = compact('size', 'unit');
        return $this;
    }

    public function marginBottom(int $size, string $unit = 'mm'): self
    {
        $this->margins['bottom'] = compact('size', 'unit');
        return $this;
    }

    public function marginLeft(int $size, string $unit = 'mm'): self
    {
        $this->margins['left'] = compact('size', 'unit');
        return $this;
    }

    public function landscape(bool $landscape = true): self
    {
        $this->landscape = $landscape;
        return $this;
    }

    public function portrait(): self
    {
        return $this->landscape(false);
    }

    public function margins(int $top, int $right, int $bottom, int $left, string $unit = 'mm'): self
    {
        $this->margins = compact('top', 'right', 'bottom', 'left', 'unit');
        return $this;
    }

    public function pages(...$pages): static
    {
        array_walk($pages, function (&$page) {
            if (is_array($page)) {
                if (count($page) == 1) {
                    $page = (string) $page[0];
                }

                $page = $page[0] . '-' . $page[1];
            }

            $page = (string) $page;
        });

        $this->pages = join(',', $pages);

        return $this;
    }

    public function noSandbox(bool $noSandbox = true): self
    {
        $this->noSandbox = $noSandbox;
        return $this;
    }

    protected function prepareBrowsershot(): Browsershot
    {
        if ($this->url) {
            $browsershot = Browsershot::url($this->url);
        } else {
            $browsershot = Browsershot::html($this->buildHtml());
        }

        $browsershot
            ->setChromePath(config('services.browsershot.chrome_path', '/usr/bin/google-chrome'))
            ->deviceScaleFactor($this->scale)
            ->scale($this->scale)
            ->setScreenshotType($this->extension)
            ->landscape($this->landscape);

        if ($this->size) {
            if ($this->center) {
                $browsershot->clip(...$this->center, ...$this->size);
            } else {
                $browsershot->windowSize(...$this->size);
                $browsershot->paperSize(...$this->size, unit: 'px');
            }
        }

        if ($this->noSandbox) {
            $browsershot->noSandbox();
        }

        if ($this->margins) {
            $browsershot->margins(...array_values($this->margins));
        }

        if ($this->pages) {
            $browsershot->pages($this->pages);
        }

        if ($this->modifyBrowsershotUsing) {
            call_user_func($this->modifyBrowsershotUsing, $browsershot);
        }

        return $browsershot;
    }

    public function generate(): string
    {
        $browsershot = $this->prepareBrowsershot();

        return match ($this->extension) {
            'pdf' => $browsershot->pdf(),
            default => $browsershot->screenshot(),
        };
    }

    public function save(string $path, ?string $disk = null): bool
    {
        $driver = $disk ?? config('filesystems.default', 'local');
        return Storage::disk($driver)->put($path, $this->generate());
    }


    public function download(?string $fileName = null): StreamedResponse
    {
        $ext = '.' . $this->extension;
        $fileName = $fileName ?? 'document-' . now()->timestamp;

        if (!str_ends_with($fileName, $ext)) {
            $fileName .= $ext;
        }

        return response()->streamDownload(function () {
            echo $this->generate();
        }, $fileName);
    }

    public function toBase64(): string
    {
        return base64_encode($this->generate());
    }

    private function buildHtml(): string
    {
        if ($this->html === null) {
            throw new \LogicException("No HTML content defined.");
        }

        $html  = trim($this->html);
        $lang  = str_replace('_', '-', config('app.locale', 'en'));
        $lower = strtolower($html);

        if (str_contains($lower, '<html')) {
            return $html;
        }

        [$width, $height] = array_map(fn($dim) => $dim / $this->scale, $this->size ?? []);

        if (str_contains($lower, '<head') && str_contains($lower, '<body')) {
            return "<!DOCTYPE html>\n<html lang=\"{$lang}\">\n{$html}\n</html>";
        }

        if (str_contains($lower, '<body')) {
            return <<<HTML
            <!DOCTYPE html>
            <html lang="{$lang}">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            {$html}
            </html>
            HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="{$lang}">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="width: {$width}px; height: {$height}px; margin: 0;">
        {$html}
        </body>
        </html>
        HTML;
    }
}
