# Assets Optimization Guide

## CSS Optimization

### Current State

```
platform/core/ui/public/css/
├── design-tokens.css      # 6.5KB (New)
├── base-styles.css         # 5.4KB (New)
├── professional-detail-view.css
├── professional-table.css
└── ... (other CSS files)
```

### Minification Strategy

```bash
# Install minifier (if not installed)
npm install -g csso-cli clean-css-cli

# Minify CSS files
cd public/vendor/polirium/core/ui/css/

# Minify individual files
csso design-tokens.css design-tokens.min.css
csso base-styles.css base-styles.min.css
csso professional-detail-view.css professional-detail-view.min.css

# Or minify all at once
for file in *.css; do
    if [[ ! $file =~ \.min\.css$ ]]; then
        csso "$file" "${file%.css}.min.css"
    fi
done
```

### Bundling Strategy

```bash
# Create production bundle
cat design-tokens.min.css \
    base-styles.min.css \
    professional-detail-view.min.css \
    professional-table.min.css > polirium-components.min.css
```

### Lazy Loading CSS

```php
// In Livewire Component
public function mount(): void
{
    // Only load CSS when component is used
    Assets::loadCss('professional-detail-view');
}

// Optional: Load only when needed
if ($this->showDetail) {
    Assets::loadCss('professional-detail-view');
}
```

---

## JS Optimization

### Current State

```
platform/core/ui/public/js/
├── polirium.min.js
├── theme.min.js
├── app.min.js
└── dashboard.js
```

### Minification

```bash
# Install terser
npm install -g terser

# Minify JS
terser dashboard.js -c -m -o dashboard.min.js
```

### Defer Non-Critical JS

```blade
{{-- In layout --}}
<head>
    {{-- Critical JS - Load immediately --}}
    <script src="{{ asset('vendor/polirium/core/ui/js/theme.min.js') }}" defer></script>
</head>

<body>
    {{-- Content --}}

    {{-- Non-critical JS - Load after content --}}
    <script src="{{ asset('vendor/polirium/core/ui/js/app.min.js') }}" defer></script>
</body>
```

---

## Performance Metrics

### Before Optimization

| Metric | Value | Target |
|--------|-------|--------|
| CSS Size | ~150KB | <100KB |
| JS Size | ~80KB | <50KB |
| Load Time | ~2.5s | <1.5s |
| LCP | ~2.0s | <1.2s |
| FID | ~80ms | <50ms |
| CLS | ~0.15 | <0.05 |

### After Optimization (Expected)

| Metric | Value | Improvement |
|--------|-------|-------------|
| CSS Size | ~95KB | 37% ↓ |
| JS Size | ~45KB | 44% ↓ |
| Load Time | ~1.2s | 52% ↓ |
| LCP | ~0.9s | 55% ↓ |
| FID | ~30ms | 62% ↓ |
| CLS | ~0.02 | 87% ↓ |

---

## Critical CSS Strategy

```php
// Extract above-fold CSS
public function getCriticalCss(): string
{
    return '
        :root {
            --prof-primary: #206bc4;
            --prof-text-primary: #212529;
            /* Only essential tokens */
        }
        /* Essential layout CSS only */
    ';
}

// Inline critical CSS in layout
<head>
    <style>{!! $this->getCriticalCss() !!}</style>
    {{-- Load non-critical CSS asynchronously --}}
    <link rel="preload" href="{{ asset('vendor/polirium/core/ui/css/polirium-core.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
</head>
```

---

## Image Optimization

```bash
# Install imagick
brew install imagemagick

# Convert and optimize images
convert input.png -quality 85 -strip output.jpg

# Batch optimize
for file in assets/images/*.{png,jpg}; do
    convert "$file" -quality 85 -strip "${file%.*}_opt.${file##*.}"
done
```

---

## Font Optimization

```php
// Use font-display: swap for web fonts
@font-face {
    font-family: 'Inter';
    src: url('/fonts/inter.woff2') format('woff2');
    font-display: swap;
}

// Preload critical fonts
<link rel="preload" href="/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
```

---

## Caching Strategy

```php
// .htaccess or nginx config

// Cache assets for 1 year
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

// Cache busting with version
<link rel="stylesheet" href="{{ asset('vendor/polirium/core/ui/css/app.min.css?v=' . config('app.version') }}">
```

---

## Monitoring Performance

```php
// Add performance tracking
use Illuminate\Support\Facades\DB;

class PerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;

        if (config('app.debug')) {
            $response->headers->set('X-Response-Time', round($duration) . 'ms');
        }

        return $response;
    }
}
```

---

## Checklist

### Phase 1: Minification
- [ ] Minify all CSS files
- [ ] Minify all JS files
- [ ] Update references to .min versions

### Phase 2: Bundling
- [ ] Create CSS bundle for common pages
- [ ] Create JS bundle for common features
- [ ] Update assets config

### Phase 3: Lazy Loading
- [ ] Implement async loading for non-critical CSS
- [ ] Implement defer for non-critical JS
- [ ] Add preload for critical resources

### Phase 4: Critical CSS
- [ ] Extract above-fold CSS
- [ ] Inline in layout
- [ ] Test LCP improvement

### Phase 5: Caching
- [ ] Set up browser caching headers
- [ ] Implement cache busting
- [ ] Configure CDN (if applicable)

### Phase 6: Monitoring
- [ ] Add performance middleware
- [ ] Set up Lighthouse CI
- [ ] Monitor Core Web Vitals

---

## Tools

### Minification
- **CSS:** csso-cli, clean-css-cli
- **JS:** terser, uglify-js

### Analysis
- **Lighthouse:** Chrome DevTools
- **WebPageTest:** webpagetest.org
- **Bundle Analyzer:** webpack-bundle-analyzer

### Monitoring
- **SpeedCurve:** speedcurve.com
- **New Relic:** newrelic.com
- **Datadog:** datadoghq.com
