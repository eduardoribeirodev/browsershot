# Laravel Browsershot Service

A fluent Laravel wrapper for [Spatie's Browsershot](https://github.com/spatie/browsershot) package that simplifies generating screenshots and PDFs from HTML content or Blade views.

## Features

- 🎨 Generate PDFs and screenshots from Blade views, HTML strings, or URLs
- 📐 Flexible sizing with unit support (px, mm, cm, in) and aspect ratio helpers
- 📄 Named paper format shortcuts (A0–A6, Letter, Legal, Tabloid, Ledger)
- 🔧 Fluent, chainable API
- 💾 Multiple output options (download, save to storage, base64)
- 🚀 Automatic HTML structure wrapping
- ⚡ High-resolution output with device scale factor support

## Installation

```bash
composer require eduardoribeirodev/browsershot
```

### Prerequisites

This package requires Puppeteer/Chromium to be installed. Follow [Spatie's Browsershot installation guide](https://spatie.be/docs/browsershot/v4/requirements) for your environment.

```bash
npm install puppeteer
```

## Configuration

Add the Chrome path to your `config/services.php`:

```php
'browsershot' => [
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium-browser'),
],
```

Set the path in your `.env` file:

```env
BROWSERSHOT_CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" # Mac
BROWSERSHOT_CHROME_PATH="/usr/bin/chromium-browser"                                     # Linux
BROWSERSHOT_CHROME_PATH="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"  # Windows
```

## Basic Usage

### Generate PDF from Blade View

```php
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;

$pdf = Browsershot::make(view('invoice', $data))
    ->pdf()
    ->generate();
```

### Generate Screenshot from HTML

```php
$screenshot = Browsershot::make('<h1>Hello World</h1>')
    ->png()
    ->generate();
```

### Download PDF in a Controller

```php
return Browsershot::make(view('report'))
    ->a4()
    ->pdf()
    ->download('monthly-report.pdf');
```

## API Reference

### Initialization

#### `make(View|string $content, array $data = []): self`

Creates a new instance. Accepts a Blade `View` object, a view name string, a URL, or a raw HTML string.

```php
// From a View object
Browsershot::make(view('documents.invoice', $data));

// From a view name (with optional data)
Browsershot::make('documents.invoice', $data);

// From a URL
Browsershot::make('https://example.com');

// From a raw HTML string
Browsershot::make('<div>Content here</div>');
```

---

### Size & Viewport

#### `size(int $width, int $height, string $unit = 'px'): self`

Sets the viewport/paper dimensions. Supported units: `px`, `mm`, `cm`, `in`.

```php
Browsershot::make($view)->size(1920, 1080)->generate();
Browsershot::make($view)->size(210, 297, 'mm')->generate(); // A4
```

#### `center(int $x, int $y): self`

Sets the clip origin. Must be combined with `size()`.

```php
Browsershot::make($view)->center(100, 50)->size(800, 600)->generate();
```

#### `clip(int $x, int $y, int $width, int $height): self`

Shorthand for setting both `center` and `size` at once.

```php
Browsershot::make($view)->clip(0, 0, 1200, 800)->generate();
```

#### `scale(float $scale): self`

Sets the device scale factor for high-resolution output.

```php
// 2× resolution (Retina)
Browsershot::make($view)->scale(2)->generate();
```

#### `aspectRatio(string|float $ratio): self`

Adjusts the current size to match a given aspect ratio. Must call `size()` first.

```php
Browsershot::make($view)
    ->size(1920, 1080)
    ->aspectRatio('16:9')   // string form
    ->generate();

Browsershot::make($view)
    ->size(1200, 900)
    ->aspectRatio(4 / 3)    // float form
    ->generate();
```

---

### Paper Format Shortcuts

These methods set the viewport to a standard paper size (in mm). An optional `$scale` multiplier can be passed to `format()`.

#### `format(string $format, float $scale = 1): self`

```php
Browsershot::make($view)->format('A4')->generate();
Browsershot::make($view)->format('Letter', 2)->generate(); // 2× scale
```

**Named shortcuts** (equivalent to calling `format()` with the matching name):

| Method | Dimensions (mm) |
|---|---|
| `a0()` | 841 × 1189 |
| `a1()` | 594 × 841 |
| `a2()` | 420 × 594 |
| `a3()` | 297 × 420 |
| `a4()` | 210 × 297 |
| `a5()` | 148 × 210 |
| `a6()` | 105 × 148 |
| `letter()` | 216 × 279 |
| `legal()` | 216 × 356 |
| `tabloid()` | 279 × 432 |
| `ledger()` | 432 × 279 |

```php
Browsershot::make($view)->a4()->pdf()->download('doc.pdf');
Browsershot::make($view)->letter()->landscape()->pdf()->generate();
```

---

### Orientation

#### `landscape(bool $landscape = true): self`

#### `portrait(): self`

```php
Browsershot::make($view)->a4()->landscape()->pdf()->generate();
Browsershot::make($view)->a4()->portrait()->pdf()->generate();
```

---

### Margins

#### `margin(int $size, string $unit = 'mm'): self`

Applies a uniform margin to all four sides.

```php
Browsershot::make($view)->a4()->margin(10)->pdf()->generate();
```

#### `margins(int $top, int $right, int $bottom, int $left, string $unit = 'mm'): self`

Sets each margin individually in one call.

```php
Browsershot::make($view)->margins(10, 15, 10, 15)->pdf()->generate();
```

#### `marginTop / marginRight / marginBottom / marginLeft`

Fluent per-side setters.

```php
Browsershot::make($view)
    ->marginTop(20)
    ->marginBottom(20)
    ->pdf()
    ->generate();
```

---

### Output Format

#### `pdf(): self`
#### `png(): self`
#### `jpeg(): self`
#### `webp(): self`
#### `extension(string $extension): self`

Sets the output file format. The named helpers are shorthands for `extension()`.

```php
Browsershot::make($view)->pdf()->generate();
Browsershot::make($view)->png()->generate();
Browsershot::make($view)->jpeg()->generate();
Browsershot::make($view)->webp()->generate();
Browsershot::make($view)->extension('png')->generate(); // equivalent
```

---

### PDF-Specific Options

#### `pages(...$pages): self`

Selects specific pages (or page ranges) to include in the PDF output.

```php
// Single pages
Browsershot::make($view)->pdf()->pages(1, 3, 5)->generate();

// Ranges (passed as arrays)
Browsershot::make($view)->pdf()->pages([1, 3], [5, 7])->generate();

// Mixed
Browsershot::make($view)->pdf()->pages(1, [3, 5], 8)->generate();
```

---

### Sandbox

#### `noSandbox(bool $noSandbox = true): self`

Disables Chrome's sandbox — required in most Docker/CI environments.

```php
Browsershot::make($view)->noSandbox()->generate();

// Re-enable (not usually needed)
Browsershot::make($view)->noSandbox(false)->generate();
```

---

### Advanced Customization

#### `modifyBrowsershotUsing(Closure $callback): self`

Provides direct access to the underlying `Spatie\Browsershot\Browsershot` instance for options not exposed by this wrapper.

```php
use Spatie\Browsershot\Browsershot;

Browsershot::make($view)
    ->modifyBrowsershotUsing(function (Browsershot $browsershot) {
        $browsershot->waitUntilNetworkIdle()->timeout(60);
    })
    ->pdf()
    ->generate();
```

---

### Output Methods

#### `generate(): string`

Returns the raw binary content.

```php
$content = Browsershot::make($view)->pdf()->generate();
Storage::put('file.pdf', $content);
```

#### `download(?string $fileName = null): StreamedResponse`

Streams the file as a browser download. The correct extension is appended automatically if missing.

```php
return Browsershot::make($view)->pdf()->download('invoice-2025.pdf');
return Browsershot::make($view)->pdf()->download('invoice'); // → invoice.pdf
return Browsershot::make($view)->pdf()->download();          // → document-{timestamp}.pdf
```

#### `save(string $path, ?string $disk = null): bool`

Saves to Laravel's filesystem. Uses the default disk when `$disk` is omitted.

```php
// Default disk
Browsershot::make($view)->pdf()->save('invoices/invoice-123.pdf');

// Specific disk
Browsershot::make($view)->pdf()->save('reports/report.pdf', 'public');
```

#### `toBase64(): string`

Returns the content as a base64-encoded string.

```php
$base64 = Browsershot::make($view)->png()->toBase64();
echo "<img src='data:image/png;base64,{$base64}' />";
```

---

## HTML Structure Handling

The service automatically wraps partial HTML in a complete document structure:

| Input | Output |
|---|---|
| `<h1>Title</h1>` (fragment) | Full document: `DOCTYPE` + `<html>` + `<head>` + `<body>` |
| `<head>…</head><body>…</body>` | Wraps with `DOCTYPE` + `<html>` |
| `<body>…</body>` | Adds `DOCTYPE`, `<html>`, and `<head>` |
| `<!DOCTYPE html><html>…</html>` | Used as-is |

---

## Advanced Examples

### A4 Invoice PDF

```php
public function generateInvoice(Invoice $invoice)
{
    return Browsershot::make('invoices.template', [
        'invoice'  => $invoice,
        'customer' => $invoice->customer,
        'items'    => $invoice->items,
    ])
        ->a4()
        ->margin(15)
        ->pdf()
        ->download("invoice-{$invoice->number}.pdf");
}
```

### High-Resolution Social Media Image

```php
public function generateSocialImage(Post $post)
{
    Browsershot::make(view('social.og-image', compact('post')))
        ->size(1200, 630) // Open Graph dimensions
        ->scale(2)        // Retina quality
        ->png()
        ->save("social/post-{$post->id}.png");

    return Storage::url("social/post-{$post->id}.png");
}
```

### Certificate with Clip Region

```php
public function generateCertificate(User $user, Course $course)
{
    return Browsershot::make('certificates.template', [
        'userName'       => $user->name,
        'courseName'     => $course->title,
        'completionDate' => now()->format('F d, Y'),
    ])
        ->size(1920, 1357)
        ->scale(2)
        ->pdf()
        ->download("certificate-{$user->id}-{$course->id}.pdf");
}
```

### Thumbnail as Base64

```php
public function generateThumbnail(string $htmlContent)
{
    $thumbnail = Browsershot::make($htmlContent)
        ->size(800, 600)
        ->jpeg()
        ->toBase64();

    return response()->json([
        'thumbnail' => "data:image/jpeg;base64,{$thumbnail}",
    ]);
}
```

### Batch Report Generation

```php
public function generateMonthlyReports(Collection $departments)
{
    $departments->each(function ($department) {
        Browsershot::make('reports.monthly', compact('department'))
            ->a4()
            ->landscape()
            ->pdf()
            ->save("reports/{$department->slug}-" . now()->format('Y-m') . ".pdf");
    });

    return "Generated {$departments->count()} reports";
}
```

### Custom Browsershot Options

```php
public function captureWithCustomOptions()
{
    return Browsershot::make('https://example.com')
        ->size(1920, 1080)
        ->png()
        ->modifyBrowsershotUsing(function ($b) {
            $b->waitUntilNetworkIdle()->timeout(30);
        })
        ->download('screenshot.png');
}
```

---

## Testing

```php
use EduardoRibeiroDev\Browsershot\Facades\Browsershot;
use Illuminate\Support\Facades\Storage;

test('can generate pdf from view', function () {
    Storage::fake('local');

    $result = Browsershot::make('test-view')
        ->a4()
        ->pdf()
        ->save('test.pdf');

    expect($result)->toBeTrue();
    Storage::assertExists('test.pdf');
});

test('can generate high resolution screenshot', function () {
    $content = Browsershot::make('<h1>Test</h1>')
        ->size(1920, 1080)
        ->scale(2)
        ->png()
        ->generate();

    expect($content)->toBeString()->not->toBeEmpty();
});
```

---

## Troubleshooting

### Chrome/Chromium Not Found

```bash
# Ubuntu/Debian
sudo apt-get install chromium-browser

# macOS
brew install chromium

# Update .env accordingly
BROWSERSHOT_CHROME_PATH=/usr/bin/chromium-browser
```

### Permission Issues in Docker

```php
Browsershot::make($view)->noSandbox()->generate();
```

### Memory Issues with Large PDFs

```php
ini_set('memory_limit', '512M');

Browsershot::make($largeView)->pdf()->generate();
```

---

## Performance Tips

1. **Cache generated content** when possible
2. **Use queues** for batch generation
3. **Optimize Blade views** — minimize CSS/JS complexity
4. **Set appropriate sizes** — don't generate larger than needed
5. **Choose the right format** — JPEG is smaller than PNG for photographic content

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Built on top of [Spatie Browsershot](https://github.com/spatie/browsershot)
- Developed by Eduardo Ribeiro

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/eduardoribeirodev/browsershot).