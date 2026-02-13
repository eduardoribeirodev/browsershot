# Laravel Browsershot Service

A fluent Laravel wrapper for [Spatie's Browsershot](https://github.com/spatie/browsershot) package that simplifies generating screenshots and PDFs from HTML content or Blade views.

## Features

- 🎨 Generate PDFs and screenshots from Blade views or HTML strings
- 📐 Flexible window sizing with aspect ratio support
- 🔧 Fluent, chainable API
- 💾 Multiple output options (download, save to storage, base64)
- 🚀 Automatic HTML structure wrapping
- ⚡ High-resolution output with device scale factor support

## Installation

```bash
composer require eduardoribeirodev/browsershot
```

### Prerequisites

This package requires Puppeteer/Chromium to be installed. Follow [Spatie's Browsershot installation guide](https://spatie.be/docs/browsershot/v3/requirements) for your environment.

## Configuration

Add the Chrome path to your `config/services.php`:

```php
'browsershot' => [
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium-browser'),
],
```

Set the path in your `.env` file:

```env
BROWSERSHOT_CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" // For Mac
BROWSERSHOT_CHROME_PATH="/usr/bin/chromium-browser" // For Linux
BROWSERSHOT_CHROME_PATH="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" // For Windows
BROWSERSHOT_CHROME_PATH=/path/to/chrome // Custom path
```

## Basic Usage

### Generate PDF from Blade View

```php
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;

$pdf = Browsershot::make(view('invoice', $data))
    ->format('pdf')
    ->generate();
```

### Generate Screenshot from HTML

```php
$screenshot = Browsershot::make('<h1>Hello World</h1>')
    ->format('png')
    ->generate();
```

### Download PDF in Controller

```php
Browsershot::make(view('report'))
    ->format('pdf')
    ->download('monthly-report.pdf');
```

## API Reference

### Initialization

#### `make(View|string $content)`

Creates a new instance of the service.

```php
// From Blade view
Browsershot::make(view('documents.invoice', $data));

// From HTML string
Browsershot::make('<div>Content here</div>');

// From HTML file
Browsershot::make(file_get_contents('template.html'));
```

### Window Configuration

#### `windowSize(int $width, int $height)`

Sets the viewport dimensions in pixels.

```php
Browsershot::make($view)
    ->windowSize(1920, 1080)
    ->generate();
```

#### `proportion(string $proportion, int $baseWidth = 780)`

Sets dimensions based on aspect ratio.

```php
// 16:9 aspect ratio with 780px base width
Browsershot::make($view)
    ->proportion('16:9')
    ->generate();

// Custom base width
Browsershot::make($view)
    ->proportion('4:3', 1024)
    ->generate();
```

**Common proportions:**
- `16:9` - Widescreen (default base: 1388x780px)
- `4:3` - Standard (default base: 1040x780px)
- `1:1` - Square (default base: 780x780px)
- `21:9` - Ultrawide (default base: 1820x780px)

#### `scale(int $scale)`

Sets the device scale factor for high-resolution output.

```php
// 2x resolution (Retina)
Browsershot::make($view)
    ->scale(2)
    ->generate();
```

### Output Format

#### `format(string $format)`

Sets the output format.

```php
// PDF
Browsershot::make($view)->format('pdf');

// PNG (default)
Browsershot::make($view)->format('png');

// JPEG
Browsershot::make($view)->format('jpg');
```

**Supported formats:** `png`, `jpg`, `jpeg`, `pdf`, `webp`

### Sandbox Configuration

#### `noSandbox(bool $noSandbox = true)`

Enables or disables Chrome's sandbox mode.

```php
// Disable sandbox (useful for Docker/CI environments)
Browsershot::make($view)
    ->noSandbox()
    ->generate();

// Enable sandbox
Browsershot::make($view)
    ->noSandbox(false)
    ->generate();
```

### Output Methods

#### `generate(): string`

Generates and returns the content as a binary string.

```php
$content = Browsershot::make($view)
    ->format('pdf')
    ->generate();

// Store manually
Storage::put('file.pdf', $content);
```

#### `download(?string $fileName = null): StreamedResponse`

Generates and triggers a browser download.

```php
// Auto-generated filename: arquivo-2025-02-13-143022.pdf
return Browsershot::make($view)
    ->format('pdf')
    ->download();

// Custom filename
return Browsershot::make($view)
    ->format('pdf')
    ->download('invoice-2025.pdf');

// Extension is automatically added if missing
return Browsershot::make($view)
    ->format('pdf')
    ->download('invoice'); // Results in 'invoice.pdf'
```

#### `save(string $path): bool`

Saves the generated file to Laravel storage.

```php
// Save to storage/app/invoices/invoice-123.pdf
Browsershot::make($view)
    ->format('pdf')
    ->save('invoices/invoice-123.pdf');

// Save to public disk
Storage::disk('public')->put(
    'reports/report.pdf',
    Browsershot::make($view)->format('pdf')->generate()
);
```

#### `toBase64(): string`

Returns the generated content as a base64-encoded string.

```php
$base64 = Browsershot::make($view)
    ->format('png')
    ->toBase64();

// Use in HTML
echo "<img src='data:image/png;base64,{$base64}' />";

// Store in database
$model->screenshot = $base64;
```

### Helper Methods

#### `getWindowSize(): array`

Returns the current window dimensions.

```php
$service = Browsershot::make($view)
    ->windowSize(1920, 1080);

[$width, $height] = $service->getWindowSize();
// $width = 1920, $height = 1080
```

## Advanced Examples

### Complete Invoice PDF Generation

```php
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;

public function generateInvoice(Invoice $invoice)
{
    return Browsershot::make(view('invoices.template', [
        'invoice' => $invoice,
        'customer' => $invoice->customer,
        'items' => $invoice->items,
    ]))
        ->proportion('21:29.7') // A4 paper ratio
        ->format('pdf')
        ->download("invoice-{$invoice->number}.pdf");
}
```

### High-Resolution Social Media Image

```php
public function generateSocialImage(Post $post)
{
    Browsershot::make(view('social.og-image', compact('post')))
        ->windowSize(1200, 630) // Open Graph dimensions
        ->scale(2) // Retina quality
        ->format('png')
        ->save("social/post-{$post->id}.png");

    return Storage::url("social/post-{$post->id}.png");
}
```

### Certificate Generation with Custom Styling

```php
public function generateCertificate(User $user, Course $course)
{
    $html = view('certificates.template', [
        'userName' => $user->name,
        'courseName' => $course->title,
        'completionDate' => now()->format('F d, Y'),
    ])->render();

    return Browsershot::make($html)
        ->windowSize(1920, 1357) // Certificate dimensions
        ->scale(2)
        ->format('pdf')
        ->download("certificate-{$user->id}-{$course->id}.pdf");
}
```

### Thumbnail Generation from HTML Content

```php
public function generateThumbnail(string $htmlContent)
{
    $thumbnail = Browsershot::make($htmlContent)
        ->windowSize(800, 600)
        ->format('jpg')
        ->toBase64();

    return response()->json([
        'thumbnail' => "data:image/jpeg;base64,{$thumbnail}"
    ]);
}
```

### Batch Report Generation

```php
use Illuminate\Support\Facades\Storage;

public function generateMonthlyReports(Collection $departments)
{
    $departments->each(function ($department) {
        Browsershot::make(view('reports.monthly', compact('department')))
            ->proportion('16:9')
            ->format('pdf')
            ->save("reports/{$department->slug}-" . now()->format('Y-m') . ".pdf");
    });

    return "Generated {$departments->count()} reports";
}
```

### Screenshot with Custom HTML Wrapper

```php
public function captureCustomContent()
{
    $customHtml = <<<HTML
    <head>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    </head>
    <body>
        <h1>Beautiful Screenshot</h1>
    </body>
    HTML;

    return Browsershot::make($customHtml)
        ->windowSize(1920, 1080)
        ->format('png')
        ->download('beautiful-screenshot.png');
}
```

## HTML Structure Handling

The service automatically wraps your HTML in a complete document structure if needed:

```php
// Input: '<h1>Title</h1>'
// Output: Full HTML document with DOCTYPE, html, head, and body tags

// Input: '<body>Content</body>'
// Output: Adds DOCTYPE, html, and head tags

// Input: '<!DOCTYPE html><html>...</html>'
// Output: Uses your complete structure as-is
```

This ensures proper rendering without requiring you to provide complete HTML every time.

## Controller Examples

### RESTful PDF Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice)
    {
        return Browsershot::make(view('invoices.pdf', compact('invoice')))
            ->format('pdf')
            ->download("invoice-{$invoice->number}.pdf");
    }

    public function preview(Invoice $invoice)
    {
        $base64 = Browsershot::make(view('invoices.pdf', compact('invoice')))
            ->format('png')
            ->windowSize(800, 1131) // A4 preview
            ->toBase64();

        return view('invoices.preview', compact('base64'));
    }
}
```

## Testing

```php
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;
use Illuminate\Support\Facades\Storage;

test('can generate pdf from view', function () {
    Storage::fake('local');

    $result = Browsershot::make(view('test-view'))
        ->format('pdf')
        ->save('test.pdf');

    expect($result)->toBeTrue();
    Storage::assertExists('test.pdf');
});

test('can generate high resolution screenshot', function () {
    $service = Browsershot::make('<h1>Test</h1>')
        ->windowSize(1920, 1080)
        ->scale(2);

    $content = $service->generate();

    expect($content)->toBeString();
    expect(strlen($content))->toBeGreaterThan(0);
});
```

## Troubleshooting

### Chrome/Chromium Not Found

```bash
# Ubuntu/Debian
sudo apt-get install chromium-browser

# macOS
brew install chromium

# Then update .env
BROWSERSHOT_CHROME_PATH=/usr/bin/chromium-browser
```

### Permission Issues in Docker

Add `--no-sandbox` flag:

```php
Browsershot::make($view)
    ->noSandbox()
    ->generate();
```

### Memory Issues with Large PDFs

Increase PHP memory limit in `php.ini` or runtime:

```php
ini_set('memory_limit', '512M');

Browsershot::make($largeView)
    ->format('pdf')
    ->generate();
```

## Performance Tips

1. **Cache generated content** when possible
2. **Use queues** for batch generation
3. **Optimize Blade views** - minimize CSS/JS complexity
4. **Set appropriate window sizes** - don't generate larger than needed
5. **Consider image format** - JPEG is smaller than PNG for photos

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Built on top of [Spatie Browsershot](https://github.com/spatie/browsershot)
- Developed by Eduardo Ribeiro

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/eduardoribeirodev/browsershot).