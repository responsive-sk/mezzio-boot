# DebugBar Branding Customization

This package allows developers to easily customize the DebugBar with their own brand logo and colors.

## Quick Logo Replacement

### Method 1: Using CustomDebugBarStyles Class

```php
use ResponsiveSk\PhpDebugBarMiddleware\CustomDebugBarStyles;

// Replace the logo in your middleware configuration
$customCss = CustomDebugBarStyles::getMinimalCss();
// Inject this CSS into your DebugBar
```

### Method 2: Override CSS in Your Application

Create a custom CSS file or add to your existing styles:

```css
/* Your Brand Logo for DebugBar */
a.phpdebugbar-restore-btn {
    width: 32px !important;
    height: 32px !important;
    background: transparent !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

a.phpdebugbar-restore-btn:after {
    background: transparent url("data:image/svg+xml,YOUR_ENCODED_SVG_HERE") no-repeat center center !important;
    background-size: 28px 28px !important;
    background-color: transparent !important;
    width: 28px !important;
    height: 28px !important;
    position: static !important;
    left: auto !important;
    top: auto !important;
}
```

## Creating Your Custom Logo

### Step 1: Prepare Your SVG

1. Create or optimize your logo as SVG
2. Recommended size: 32x32px or 48x48px viewBox
3. Use simple colors that work on both light and dark themes
4. Keep file size small (under 2KB)

Example SVG structure:
```xml
<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 48 48">
    <path d="your-path-data" fill="#your-color"/>
    <!-- Add more paths as needed -->
</svg>
```

### Step 2: URL Encode Your SVG

```php
$logoSvg = '<svg xmlns="http://www.w3.org/2000/svg">...</svg>';
$encodedLogo = rawurlencode($logoSvg);
```

### Step 3: Create Custom CSS

```php
$customCss = "
a.phpdebugbar-restore-btn:after {
    background: transparent url(\"data:image/svg+xml,{$encodedLogo}\") no-repeat center center !important;
    background-size: 28px 28px !important;
}
";
```

## Advanced Branding Options

### Full Theme Customization

```css
/* Custom header gradient */
div.phpdebugbar-header {
    background: linear-gradient(135deg, #your-primary 0%, #your-secondary 100%) !important;
}

/* Custom active tab color */
a.phpdebugbar-tab.phpdebugbar-active {
    border-bottom-color: #your-accent !important;
}

/* Custom important badge color */
a.phpdebugbar-tab span.phpdebugbar-badge.phpdebugbar-important {
    background: #your-error-color !important;
}

/* Custom text colors */
div.phpdebugbar-header {
    color: #your-text-color !important;
}
```

### Company-Specific Examples

#### Tech Startup (Blue Theme)
```css
a.phpdebugbar-restore-btn:after {
    background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%23007acc'/%3E%3Ctext x='16' y='20' text-anchor='middle' fill='white' font-size='14' font-weight='bold'%3ES%3C/text%3E%3C/svg%3E") no-repeat center center !important;
}

div.phpdebugbar-header {
    background: linear-gradient(135deg, #007acc 0%, #005a9e 100%) !important;
}
```

#### Creative Agency (Purple Theme)
```css
a.phpdebugbar-restore-btn:after {
    background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Cpolygon points='16,2 28,14 16,26 4,14' fill='%23663399'/%3E%3C/svg%3E") no-repeat center center !important;
}

div.phpdebugbar-header {
    background: linear-gradient(135deg, #663399 0%, #441166 100%) !important;
}
```

#### E-commerce (Green Theme)
```css
a.phpdebugbar-restore-btn:after {
    background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Crect x='4' y='8' width='24' height='16' rx='2' fill='%2328a745'/%3E%3Ccircle cx='12' cy='16' r='3' fill='white'/%3E%3Ccircle cx='20' cy='16' r='3' fill='white'/%3E%3C/svg%3E") no-repeat center center !important;
}
```

## Integration Examples

### Mezzio/Laminas Integration

```php
// config/autoload/debugbar.local.php
return [
    'debugbar' => [
        'custom_css' => file_get_contents(__DIR__ . '/../../assets/debugbar-brand.css'),
    ],
];
```

### Slim 4 Integration

```php
// In your middleware setup
$debugBarMiddleware = new DebugBarMiddleware();
$debugBarMiddleware->addCustomCss($yourCustomCss);
$app->add($debugBarMiddleware);
```

### Symfony Integration

```yaml
# config/packages/debugbar.yaml
debugbar:
    branding:
        logo_svg: '%kernel.project_dir%/assets/logo.svg'
        primary_color: '#your-color'
```

## Best Practices

### Logo Design
- **Size**: 28x28px to 32x32px optimal
- **Colors**: Use colors that work on both light and dark backgrounds
- **Simplicity**: Avoid complex details that don't scale well
- **Format**: SVG preferred for crisp rendering at any size

### Performance
- **File Size**: Keep SVG under 2KB
- **Optimization**: Use SVGO to optimize your SVG
- **Caching**: CSS is cached by browser, no performance impact

### Accessibility
- **Contrast**: Ensure sufficient color contrast
- **Alt Text**: Consider adding title attributes for screen readers
- **Theme Compatibility**: Test with both light and dark DebugBar themes

## Troubleshooting

### Logo Not Appearing
1. Check SVG syntax and encoding
2. Verify CSS specificity with `!important`
3. Test SVG in browser first
4. Check browser developer tools for CSS conflicts

### Logo Positioning Issues
1. Use flexbox centering as shown in examples
2. Set `position: static` to override default positioning
3. Adjust `background-size` for proper scaling

### Color Issues
1. Use hex colors instead of named colors
2. Test with both DebugBar themes
3. Avoid transparent fills that might be invisible

## Contributing

If you create a great branding example, consider contributing it back to the project! Submit a PR with your example in the `examples/branding/` directory.
