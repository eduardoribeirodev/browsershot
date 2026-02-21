<?php

namespace EduardoRibeiroDev\Browsershot\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrowsershotService
{
    private string $html;
    private array $windowSize = [1920, 1080];
    private int $deviceScaleFactor = 1;
    private string $format = 'png';
    private bool $noSandbox = true;

    /**
     * Cria uma nova instância do serviço
     * @param View|string $content Pode ser uma view, string de nome de view, HTML bruto ou URL
     */
    public static function make(View|string $content, array $data = []): self
    {
        $html = null;

        if ($content instanceof View) {
            $html = $content->render();
        } else if (view()->exists($content)) {
            $html = view($content, $data)->render();
        } else if (filter_var($content, FILTER_VALIDATE_URL)) {
            $html = file_get_contents($content);
        } else {
            $html = $content;
        }

        $instance = (new static);
        $instance->html($html);

        return $instance;
    }

    /**
     * Define o HTML a ser renderizado
     */
    protected function html(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    /**
     * Envolve o HTML em uma estrutura completa caso necessário
     */
    private function getWrappedHtml(): string
    {
        $html = trim($this->html);
        $lang = str_replace('_', '-', config('app.locale'));

        // Verifica se já possui tag <html>
        if (preg_match('/<html[\s>]/i', $html)) {
            return $html;
        }

        // Verifica se possui <head> mas não <html>
        $hasHead = preg_match('/<head[\s>]/i', $html);
        $hasBody = preg_match('/<body[\s>]/i', $html);

        // Se já tem head e body, apenas envolve em <html>
        if ($hasHead && $hasBody) {
            return "<!DOCTYPE html>\n<html lang=\"{$lang}\">\n{$html}\n</html>";
        }

        // Se tem apenas body, adiciona head completo
        if ($hasBody) {
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

        [$width, $height] = $this->getWindowSize();

        // Se não tem nenhuma estrutura HTML, cria completa
        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
    <meta charset="UTF-8">
</head>
<body style="width: {$width}px; height: {$height}px;">
{$html}
</body>
</html>
HTML;
    }

    /**
     * Define o tamanho da janela
     */
    public function windowSize(int $width, int $height): self
    {
        $this->windowSize = [$width, $height];
        return $this;
    }

    /**
     * Define o tamanho baseado em proporção (ex: '16:9')
     */
    public function proportion(int $widthRatio, int $heightRatio): self
    {
        $baseWidth = $this->windowSize[0];
        $minDim = min($widthRatio, $heightRatio);

        $width = intval(($widthRatio / $minDim) * $baseWidth);
        $height = intval(($heightRatio / $minDim) * $baseWidth);

        return $this->windowSize($width, $height);
    }

    /**
     * Define o fator de escala do dispositivo
     */
    public function scale(int $scale): self
    {
        $this->deviceScaleFactor = $scale;
        return $this;
    }

    /**
     * Define o formato de saída (png, jpg, pdf, etc)
     */
    public function format(string $format): self
    {
        $this->format = strtolower($format);
        return $this;
    }

    /**
     * Habilita/desabilita o sandbox
     */
    public function noSandbox(bool $noSandbox = true): self
    {
        $this->noSandbox = $noSandbox;
        return $this;
    }

    /**
     * Gera o conteúdo (PDF ou Screenshot)
     */
    public function generate(): string
    {
        $browsershot = Browsershot::html($this->getWrappedHtml())
            ->windowSize(...$this->windowSize)
            ->setChromePath(config('services.browsershot.chrome_path'))
            ->deviceScaleFactor($this->deviceScaleFactor);

        if ($this->noSandbox) {
            $browsershot->noSandbox();
        }

        if ($this->format === 'pdf') {
            return $browsershot->pdf();
        }

        return $browsershot->format($this->format)->screenshot();
    }

    /**
     * Gera e retorna um download direto
     */
    public function download(?string $fileName = null): StreamedResponse
    {
        if (!$fileName) {
            $fileName = 'arquivo-' . now()->format('Y-m-d-His') . '.' . $this->format;
        }

        if (!str_ends_with($fileName, $this->format)) {
            $fileName .= '.' . $this->format;
        }

        $content = $this->generate();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName);
    }

    /**
     * Salva o arquivo no storage
     */
    public function save(string $path, ?string $disk = null): bool
    {
        $driver = $disk ?? config('filesystems.default', 'local');
        $content = $this->generate();
        return Storage::disk($driver)->put($path, $content);
    }

    /**
     * Retorna o conteúdo em base64
     */
    public function toBase64(): string
    {
        return base64_encode($this->generate());
    }

    /**
     * Retorna as dimensões da janela atual
     */
    public function getWindowSize(): array
    {
        return $this->windowSize;
    }
}
