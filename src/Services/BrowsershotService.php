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
     */
    public static function make(View|string $content): self
    {
        $instance = (new static);
        
        if ($content instanceof View) {
            $instance->html($content->render());
        } else {
            $instance->html($content);
        }

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
        
        // Verifica se já possui tag <html>
        if (preg_match('/<html[\s>]/i', $html)) {
            return $html;
        }

        // Verifica se possui <head> mas não <html>
        $hasHead = preg_match('/<head[\s>]/i', $html);
        $hasBody = preg_match('/<body[\s>]/i', $html);

        // Se já tem head e body, apenas envolve em <html>
        if ($hasHead && $hasBody) {
            return "<!DOCTYPE html>\n<html lang=\"pt-BR\">\n{$html}\n</html>";
        }

        // Se tem apenas body, adiciona head completo
        if ($hasBody) {
            return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
{$html}
</html>
HTML;
        }

        $height = $this->getWindowSize()[1];

        // Se não tem nenhuma estrutura HTML, cria completa
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
</head>
<body style="height: {$height}px;">
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
    public function proportion(string $proportion, ?int $baseWidth = null): self
    {
        if (!$baseWidth) {
            $baseWidth = $this->windowSize[0];
        }
        
        $parts = explode(':', $proportion);
        $minDim = min($parts);
        
        $width = intval(($parts[0] / $minDim) * $baseWidth);
        $height = intval(($parts[1] / $minDim) * $baseWidth);
        
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
            ->windowSize($this->windowSize[0], $this->windowSize[1])
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
    public function save(string $path): bool
    {
        $content = $this->generate();
        return Storage::put($path, $content);
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