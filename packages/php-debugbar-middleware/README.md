# PHP DebugBar Middleware

[![Latest Version](https://img.shields.io/packagist/v/responsive-sk/php-debugbar-middleware.svg)](https://packagist.org/packages/responsive-sk/php-debugbar-middleware)
[![PHP Version](https://img.shields.io/packagist/php-v/responsive-sk/php-debugbar-middleware.svg)](https://packagist.org/packages/responsive-sk/php-debugbar-middleware)
[![License](https://img.shields.io/packagist/l/responsive-sk/php-debugbar-middleware.svg)](https://packagist.org/packages/responsive-sk/php-debugbar-middleware)
[![Tests](https://github.com/responsive-sk/php-debugbar-middleware/workflows/Tests/badge.svg)](https://github.com/responsive-sk/php-debugbar-middleware/actions)

Modern PSR-15 middleware for [PHP DebugBar](http://phpdebugbar.com/) with automatic asset serving and zero configuration. Works seamlessly with **Mezzio**, **Slim 4**, **Symfony**, and any PSR-15 compatible framework.

## ✨ Features

- 🚀 **Zero Configuration** - Works out of the box
- 🎯 **Automatic Asset Serving** - No manual asset copying required
- 🔒 **Security First** - Path traversal protection and development-only activation
- ⚡ **High Performance** - Minimal overhead, production-safe
- 🎨 **Full Styling** - Complete CSS/JS with Font Awesome icons
- 🔧 **Framework Agnostic** - Works with any PSR-15 framework
- 📱 **Modern PHP** - Requires PHP 8.2+, strict types, comprehensive typing

## 📦 Installation

```bash
composer require --dev responsive-sk/php-debugbar-middleware
```

## 🚀 Quick Start

### Mezzio / Laminas

```php
// config/config.php
$configManager = new ConfigManager([
    ResponsiveSk\PhpDebugBarMiddleware\ConfigProvider::class,
    // ... your other config providers
]);

// config/pipeline.php
$app->pipe(ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware::class);
```

### Slim 4

```php
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware;
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarAssetsHandler;

$app = AppFactory::create();

// Add middleware
$app->add(DebugBarMiddleware::class);

// Add asset route
$app->get('/debugbar/{file:.+}', DebugBarAssetsHandler::class);
```

### Symfony (with PSR-15 Bridge)

```php
// config/services.yaml
services:
    ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware:
        tags: ['middleware']
```

### Manual Setup (Any PSR-15 Framework)

```php
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware;
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarAssetsHandler;

// Create middleware
$debugBarMiddleware = new DebugBarMiddleware();

// Add to your middleware stack
$middlewareStack->add($debugBarMiddleware);

// Add asset handler to your router
$router->get('/debugbar/{file:.+}', new DebugBarAssetsHandler());
```

## 🎛️ Configuration

### Environment-Based Activation

DebugBar automatically activates in development and deactivates in production:

```bash
# Development (DebugBar active)
APP_ENV=development
DEBUG=true

# Production (DebugBar inactive)
APP_ENV=production
DEBUG=false
```

### Custom Configuration

```php
// config/autoload/debugbar.local.php
return [
    'debugbar' => [
        'enabled' => true,
        'collectors' => [
            'messages' => true,
            'time' => true,
            'memory' => true,
            'exceptions' => true,
            'request' => true,
        ],
        'asset_path' => '/debugbar',
    ],
];
```

## 🔧 Framework-Specific Examples

### Mezzio Complete Setup

```php
// config/config.php
use Laminas\ConfigAggregator\ConfigAggregator;
use ResponsiveSk\PhpDebugBarMiddleware\ConfigProvider;

$aggregator = new ConfigAggregator([
    ConfigProvider::class,
    // ... other providers
]);

return $aggregator->getMergedConfig();
```

### Slim 4 with Container

```php
use DI\Container;
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware;

$container = new Container();
$app = AppFactory::createFromContainer($container);

// Register middleware
$container->set(DebugBarMiddleware::class, function() {
    return new DebugBarMiddleware();
});

$app->add(DebugBarMiddleware::class);
```

## 📊 What You Get

- **Request Timeline** - See exactly where time is spent
- **Memory Usage** - Track memory consumption
- **Exception Tracking** - Catch and display errors
- **Request Data** - Inspect GET/POST/COOKIE data
- **Custom Messages** - Add your own debug messages
- **Database Queries** - Monitor SQL performance (with additional collectors)

## 🛡️ Security

- **Development Only** - Automatically disabled in production
- **Path Traversal Protection** - Secure asset serving
- **No External Dependencies** - All assets served locally
- **Environment Detection** - Respects APP_ENV and DEBUG settings

## 🎨 Styling

DebugBar appears with full styling including:
- Font Awesome icons
- Responsive design
- Dark/light theme support
- Professional appearance
- Zero configuration required

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Static analysis
composer phpstan

# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Run all quality checks
composer quality
```

## 📈 Performance

- **Zero Production Impact** - Completely disabled in production
- **Minimal Development Overhead** - Optimized for development workflow
- **Efficient Asset Serving** - Direct file serving without processing
- **Memory Efficient** - Lazy loading and minimal memory footprint

## 🎨 Custom Branding

Want to use your own logo instead of the default? Check out our comprehensive [Branding Guide](docs/BRANDING.md) with examples for:

- **Custom Logo Integration** - Replace with your company logo
- **Theme Customization** - Match your brand colors
- **Framework-Specific Examples** - Ready-to-use configurations
- **Best Practices** - Design and performance guidelines

## 🚀 Roadmap & Future Enhancements

See our [Roadmap](docs/ROADMAP.md) for planned features and enhancement ideas:

- Database query collectors
- Advanced performance monitoring
- Mobile-responsive design
- Plugin system architecture
- Enterprise features

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

- Built by [Responsive.sk](https://responsive.sk)
- Based on [PHP DebugBar](http://phpdebugbar.com/) by Maxime Bouroumeau-Fuseau
- Inspired by the Laravel DebugBar package

## 🔗 Related Packages

- [php-debugbar/php-debugbar](https://github.com/php-debugbar/php-debugbar) - The core DebugBar library
- [responsive-sk/slim4-paths](https://github.com/responsive-sk/slim4-paths) - Path management for PHP applications
