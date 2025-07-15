<?php

declare(strict_types=1);

/**
 * Shared Hosting Deployment Script
 * 
 * Creates a deployment package optimized for shared hosting environments
 * with limited functionality and permissions.
 */

use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function unlink;

echo "🚀 Preparing deployment for shared hosting...\n\n";

class SharedHostingDeployer
{
    private string $buildDir = 'build-shared-hosting';
    private string $sourceDir;
    
    public function __construct()
    {
        $this->sourceDir = __DIR__;
    }
    
    public function deploy(): void
    {
        echo "📋 Checking compatibility...\n";
        $this->checkCompatibility();
        
        echo "🧹 Cleaning previous build...\n";
        $this->cleanPreviousBuild();
        
        echo "📁 Creating minimal build...\n";
        $this->createMinimalBuild();
        
        echo "⚙️  Generating configuration...\n";
        $this->generateConfiguration();
        
        echo "📄 Creating setup instructions...\n";
        $this->createSetupInstructions();
        
        echo "\n✅ Shared hosting package ready in: {$this->buildDir}/\n";
        echo "📖 See SHARED_HOSTING_SETUP.txt for upload instructions\n";
    }
    
    private function checkCompatibility(): void
    {
        $issues = [];
        
        // Check PHP version - STRICT: 8.2+ only
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $issues[] = "PHP " . PHP_VERSION . " is too old. REQUIRED: 8.2.0+";
        }
        
        // Check required extensions
        $required = ['json', 'mbstring'];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Missing required extension: {$ext}";
            }
        }
        
        // Check disabled functions
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $disabledList = explode(',', $disabled);
            $critical = ['file_get_contents', 'file_put_contents', 'mkdir'];
            foreach ($critical as $func) {
                if (in_array($func, $disabledList)) {
                    $issues[] = "Critical function disabled: {$func}";
                }
            }
        }
        
        if (!empty($issues)) {
            echo "⚠️  Compatibility issues found:\n";
            foreach ($issues as $issue) {
                echo "   - {$issue}\n";
            }
            echo "\n";
        } else {
            echo "✅ No compatibility issues found\n";
        }
    }
    
    private function cleanPreviousBuild(): void
    {
        if (is_dir($this->buildDir)) {
            $this->removeDirectory($this->buildDir);
        }
        mkdir($this->buildDir, 0755, true);
    }
    
    private function createMinimalBuild(): void
    {
        // Essential directories (excluding vendor - will be optimized separately)
        $essential = [
            'config' => 'config',
            'public' => 'public',
            'var' => 'var',
        ];

        // Copy src directory with all modules
        echo "  - Copying src/ (all modules)\n";
        $this->copyDirectory($this->sourceDir . '/src', $this->buildDir . '/src');

        foreach ($essential as $source => $target) {
            $sourcePath = $this->sourceDir . '/' . $source;
            $targetPath = $this->buildDir . '/' . $target;

            if (is_dir($sourcePath)) {
                echo "  - Copying {$source}/\n";
                $this->copyDirectory($sourcePath, $targetPath);
            }
        }

        // Create optimized vendor directory
        echo "  - Creating optimized vendor/\n";
        $this->createOptimizedVendor();
        
        // Essential files
        $files = [
            'README.md',
            'LICENSE',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                copy($file, $this->buildDir . '/' . $file);
            }
        }

        // Create minimal composer.json for production
        $this->createMinimalComposerJson();
    }
    
    private function generateConfiguration(): void
    {
        // Generate optimized .htaccess
        $htaccess = <<<'HTACCESS'
# Shared Hosting .htaccess with fallbacks
RewriteEngine On

# Try modern rewrite first
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Fallback for older Apache
<IfModule !mod_rewrite.c>
    ErrorDocument 404 /index.php
</IfModule>

# Security headers (if supported)
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP settings (if allowed)
<IfModule mod_php.c>
    php_value memory_limit 256M
    php_value max_execution_time 60
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>

# Deny access to sensitive files
<Files "composer.*">
    Require all denied
</Files>
<Files "*.md">
    Require all denied
</Files>
HTACCESS;
        
        file_put_contents($this->buildDir . '/public/.htaccess', $htaccess);
        
        // Create var structure
        $varDirs = [
            'var/data',
            'var/cache/config',
            'var/cache/twig',
            'var/logs',
            'var/tmp',
            'var/sessions',
        ];
        
        foreach ($varDirs as $dir) {
            $fullPath = $this->buildDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // Add .gitignore to keep directories
            $gitignore = "*\n!.gitignore\n";
            file_put_contents($fullPath . '/.gitignore', $gitignore);
        }
    }
    
    private function createSetupInstructions(): void
    {
        $instructions = <<<'INSTRUCTIONS'
# Ultra-Minimal Shared Hosting Setup Instructions

## REQUIREMENTS
- PHP 8.2+ (STRICT REQUIREMENT)
- Basic file upload capability (FTP/cPanel)
- mod_rewrite support (for clean URLs)

## Upload Instructions

1. **Upload files via FTP/cPanel File Manager:**
   - Upload ALL files from this build directory
   - Maintain the directory structure exactly as provided
   - Ensure public/ directory is your web root (or copy contents to web root)

2. **Set permissions (if possible):**
   ```
   chmod 755 var/
   chmod 755 var/data/
   chmod 755 var/cache/
   chmod 755 var/logs/
   chmod 755 var/tmp/
   chmod 755 var/sessions/
   ```

3. **Verify setup:**
   - Visit your website
   - Check that var/ directories are created automatically
   - Look for any error messages

## Troubleshooting

### "Permission denied" errors:
- Contact hosting provider to set correct permissions
- Some shared hosts don't allow chmod - this is usually OK

### "Function disabled" errors:
- Check with hosting provider about disabled PHP functions
- Most essential functions should work

### Memory limit errors:
- Contact hosting provider to increase memory_limit
- Or use a better hosting provider

### Rewrite errors:
- Ensure .htaccess is uploaded to public/ directory
- Check if mod_rewrite is enabled
- Some hosts require different rewrite rules

## File Structure

Your uploaded files should look like:
```
public_html/          (or your web root)
├── index.php
├── .htaccess
├── config/
├── src/
├── var/
│   ├── data/
│   ├── cache/
│   ├── logs/
│   └── tmp/
└── vendor/
```

## Support

If you encounter issues:
1. Check var/logs/ for error messages
2. Verify PHP version is 7.4+
3. Contact your hosting provider for assistance
4. Consider upgrading to a better hosting provider

This package is optimized for shared hosting but some providers
have severe limitations that may prevent proper operation.
INSTRUCTIONS;
        
        file_put_contents($this->buildDir . '/SHARED_HOSTING_SETUP.txt', $instructions);
    }
    
    private function copyDirectory(string $source, string $target): void
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }
    
    private function createOptimizedVendor(): void
    {
        $vendorSource = $this->sourceDir . '/vendor';
        $vendorTarget = $this->buildDir . '/vendor';

        if (!is_dir($vendorSource)) {
            echo "    ⚠️  Vendor directory not found\n";
            return;
        }

        mkdir($vendorTarget, 0755, true);

        // Copy only essential vendor files
        $this->copyVendorSelectively($vendorSource, $vendorTarget);

        // Copy essential composer autoload files (skip problematic ones)
        $autoloadFiles = [
            'composer/autoload_classmap.php',
            'composer/autoload_files.php',
            'composer/autoload_namespaces.php',
            // Skip autoload_psr4.php - will be regenerated
            // Skip autoload_static.php - will be regenerated
            'composer/ClassLoader.php',
            'composer/InstalledVersions.php',
            'composer/installed.php',
            'composer/installed.json',
            'composer/LICENSE',
        ];

        foreach ($autoloadFiles as $file) {
            $source = $vendorSource . '/' . $file;
            $target = $vendorTarget . '/' . $file;

            if (file_exists($source)) {
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($source, $target);
            }
        }

        echo "    ✅ Optimized vendor created\n";

        // Regenerate autoload files without polyfills
        $this->regenerateAutoload();
    }

    private function copyVendorSelectively(string $source, string $target): void
    {
        // ULTRA-MINIMAL: Only essential runtime packages for PHP 8.2+
        // NO polyfills, NO dev tools, NO extras
        $allowedPackages = [
            // Core Mezzio framework
            'mezzio/mezzio',
            'mezzio/mezzio-fastroute',
            'mezzio/mezzio-helpers',
            'mezzio/mezzio-router',           // NEEDED by RouteCollector
            'mezzio/mezzio-template',         // NEEDED by TemplateRendererInterface

            // Essential Laminas components (REQUIRED by config)
            'laminas/laminas-config-aggregator',  // NEEDED by config/config.php
            'laminas/laminas-servicemanager',     // NEEDED by config/container.php
            'laminas/laminas-stdlib',             // NEEDED by FastRouteRouter
            'laminas/laminas-diactoros',
            'laminas/laminas-httphandlerrunner',
            'laminas/laminas-stratigility',

            // PSR interfaces
            'psr/http-message',
            'psr/http-server-handler',
            'psr/http-server-middleware',
            'psr/http-factory',               // NEEDED by Diactoros
            'psr/container',
            'psr/log',                        // NEEDED by dot-errorhandler

            // Router
            'nikic/fast-route',
            'fig/http-message-util',

            // Our packages
            'dotkernel/dot-errorhandler',
            'dotkernel/dot-log',              // NEEDED by ServiceManager config
            'responsive-sk/slim4-paths',

            // Utility packages
            'webmozart/assert',               // NEEDED by Mezzio
        ];

        // Directories to exclude completely (dev tools)
        $excludeDirs = [
            'test', 'tests', 'Test', 'Tests', 'testing',
            'doc', 'docs', 'documentation', 'Documentation',
            'example', 'examples', 'demo', 'demos', 'sample', 'samples',
            'benchmark', 'benchmarks', 'perf', 'performance',
            '.git', '.github', '.gitlab', '.gitignore',
            'build', 'builds', 'dist',
            // Dev tools packages
            'phpunit', 'phpstan', 'psalm', 'phan',
            'rector', 'php-cs-fixer', 'friendsofphp',
            'squizlabs', 'slevomat', 'dealerdirect',
            'sebastian', 'theseer', 'webmozart',
            'mockery', 'hamcrest', 'fakerphp',
            'symfony/var-dumper', 'symfony/debug',
            'monolog', 'whoops',
        ];

        // Files to exclude
        $excludeFiles = [
            'phpunit.xml', 'phpunit.xml.dist',
            'phpstan.neon', 'phpstan.neon.dist',
            'psalm.xml', 'psalm.xml.dist',
            'rector.php',
            '.php-cs-fixer.dist.php', '.php_cs', '.php_cs.dist',
            'CHANGELOG.md', 'CHANGELOG', 'HISTORY.md',
            'README.md', 'README', 'readme.md',
            'CONTRIBUTING.md', 'CONTRIBUTORS.md',
            'UPGRADE.md', 'UPGRADING.md',
            'TODO.md', 'TODO',
            '.travis.yml', '.github', '.gitlab-ci.yml',
            'Makefile', 'makefile',
            'Dockerfile', 'docker-compose.yml',
            '.editorconfig', '.gitignore', '.gitattributes',
        ];

        // Only copy allowed packages
        $vendorDirs = scandir($source);
        foreach ($vendorDirs as $vendor) {
            if ($vendor === '.' || $vendor === '..') {
                continue;
            }

            $vendorPath = $source . '/' . $vendor;
            if (!is_dir($vendorPath)) {
                continue;
            }

            // Skip composer directory - will be copied separately
            if ($vendor === 'composer') {
                continue;
            }

            $packages = scandir($vendorPath);
            foreach ($packages as $package) {
                if ($package === '.' || $package === '..') {
                    continue;
                }

                $packageName = $vendor . '/' . $package;
                $packagePath = $vendorPath . '/' . $package;

                if (!is_dir($packagePath)) {
                    continue;
                }

                // Only copy allowed packages
                if (in_array($packageName, $allowedPackages, true)) {
                    echo "    - Including: {$packageName}\n";
                    $this->copyPackageMinimal($packagePath, $target . '/' . $vendor . '/' . $package);
                } else {
                    echo "    - Skipping: {$packageName}\n";
                }
            }
        }
    }

    private function copyPackageMinimal(string $source, string $target): void
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        // Essential files to copy from each package (PHP 8.2+ - no polyfills needed)
        $essentialFiles = [
            'composer.json',
            'LICENSE', 'LICENSE.md', 'LICENSE.txt',
        ];

        // Essential directories (only src and lib, skip everything else)
        // Exception: polyfill packages need all files
        $packageName = basename($source);
        if (strpos($packageName, 'polyfill-') !== false) {
            // Copy entire polyfill package
            $this->copyDirectory($source, $target);
            return;
        }

        $essentialDirs = ['src', 'lib'];

        // Copy essential files
        foreach ($essentialFiles as $file) {
            $sourcePath = $source . '/' . $file;
            if (file_exists($sourcePath)) {
                copy($sourcePath, $target . '/' . $file);
            }
        }

        // Copy essential directories with minimal content
        foreach ($essentialDirs as $dir) {
            $sourcePath = $source . '/' . $dir;
            if (is_dir($sourcePath)) {
                $this->copyDirectoryMinimal($sourcePath, $target . '/' . $dir);
            }
        }
    }

    private function copyDirectoryMinimal(string $source, string $target): void
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;

            // Skip test directories and files
            if (preg_match('/\b(test|tests|spec|specs|example|examples|demo|demos)\b/i', $relativePath)) {
                continue;
            }

            // Copy PHP files and essential bootstrap files
            if ($item->isFile()) {
                $filename = $item->getFilename();
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                // Allow PHP files and essential bootstrap files
                if (!in_array($extension, ['php'], true) &&
                    !in_array($filename, ['bootstrap.php', 'autoload.php'], true)) {
                    continue;
                }
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function createMinimalComposerJson(): void
    {
        $minimalComposer = [
            'name' => 'mezzio/light-shared-hosting',
            'description' => 'Ultra-minimal Mezzio application for shared hosting',
            'type' => 'project',
            'require' => [
                'php' => '^8.2',
                'mezzio/mezzio' => '^3.20',
                'mezzio/mezzio-fastroute' => '^3.12',
                'mezzio/mezzio-helpers' => '^5.18',
                'laminas/laminas-diactoros' => '^3.3',
                'laminas/laminas-httphandlerrunner' => '^2.10',
                'laminas/laminas-stratigility' => '^3.11',
                'psr/http-message' => '^2.0',
                'psr/http-server-handler' => '^1.0',
                'psr/http-server-middleware' => '^1.0',
                'psr/container' => '^2.0',
                'nikic/fast-route' => '^1.3',
                'fig/http-message-util' => '^1.1',
                'dotkernel/dot-errorhandler' => '^4.2',
                'responsive-sk/slim4-paths' => '^6.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'Light\\App\\' => 'src/App/src/',
                    'Light\\Core\\' => 'src/Core/src/'
                ]
            ],
            'config' => [
                'optimize-autoloader' => true,
                'sort-packages' => true
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true
        ];

        file_put_contents(
            $this->buildDir . '/composer.json',
            json_encode($minimalComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Create minimal composer.lock (empty for now)
        $minimalLock = [
            '_readme' => ['This file locks the dependencies for production deployment'],
            'content-hash' => md5(json_encode($minimalComposer)),
            'packages' => [],
            'packages-dev' => [],
            'aliases' => [],
            'minimum-stability' => 'stable',
            'stability-flags' => [],
            'prefer-stable' => true,
            'prefer-lowest' => false,
            'platform' => ['php' => '^8.2'],
            'platform-dev' => [],
            'plugin-api-version' => '2.3.0'
        ];

        file_put_contents(
            $this->buildDir . '/composer.lock',
            json_encode($minimalLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function regenerateAutoload(): void
    {
        echo "    - Creating clean autoload files...\n";

        $vendorDir = $this->buildDir . '/vendor';
        $originalVendor = $this->sourceDir . '/vendor';

        // Create simple autoload.php
        $autoloadContent = <<<'PHP'
<?php

// autoload.php @generated by ultra-minimal deployment

require_once __DIR__ . '/composer/autoload_real.php';

return ComposerAutoloaderInit::getLoader();
PHP;

        file_put_contents($vendorDir . '/autoload.php', $autoloadContent);

        // Create minimal autoload_static.php without polyfills
        $staticContent = <<<'PHP'
<?php

// autoload_static.php @generated by ultra-minimal deployment

namespace Composer\Autoload;

class ComposerStaticInit18fadace00cbfa835c1efb3ff56ea35e
{
    public static $files = array (
        // No bootstrap files for PHP 8.2+
    );

    public static $prefixLengthsPsr4 = array (
        'W' =>
        array (
            'Webmozart\\Assert\\' => 17,
        ),
        'R' =>
        array (
            'ResponsiveSk\\Slim4Paths\\' => 24,
        ),
        'P' =>
        array (
            'Psr\\Http\\Server\\' => 16,
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Factory\\' => 17,
            'Psr\\Log\\' => 8,
            'Psr\\Container\\' => 14,
        ),
        'M' =>
        array (
            'Mezzio\\Template\\' => 16,
            'Mezzio\\Router\\' => 15,
            'Mezzio\\Helper\\' => 14,
            'Mezzio\\' => 7,
        ),
        'L' =>
        array (
            'Light\\Page\\' => 11,
            'Light\\Core\\' => 11,
            'Light\\App\\' => 10,
            'Laminas\\Stratigility\\' => 21,
            'Laminas\\Stdlib\\' => 16,
            'Laminas\\ServiceManager\\' => 24,
            'Laminas\\HttpHandlerRunner\\' => 26,
            'Laminas\\Diactoros\\' => 18,
            'Laminas\\ConfigAggregator\\' => 25,
        ),
        'F' =>
        array (
            'Fig\\Http\\Message\\' => 17,
            'FastRoute\\' => 10,
        ),
        'D' =>
        array (
            'Dotkernel\\Dot\\ErrorHandler\\' => 28,
            'Dot\\Log\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Webmozart\\Assert\\' =>
        array (
            0 => __DIR__ . '/..' . '/webmozart/assert/src',
        ),
        'ResponsiveSk\\Slim4Paths\\' =>
        array (
            0 => __DIR__ . '/..' . '/responsive-sk/slim4-paths/src',
        ),
        'Psr\\Http\\Server\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/http-server-handler/src',
            1 => __DIR__ . '/..' . '/psr/http-server-middleware/src',
        ),
        'Psr\\Http\\Message\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
            1 => __DIR__ . '/..' . '/psr/http-factory/src',
        ),
        'Psr\\Log\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/log/src',
        ),
        'Psr\\Container\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/container/src',
        ),
        'Mezzio\\Template\\' =>
        array (
            0 => __DIR__ . '/..' . '/mezzio/mezzio-template/src',
        ),
        'Mezzio\\Router\\' =>
        array (
            0 => __DIR__ . '/..' . '/mezzio/mezzio-router/src',
            1 => __DIR__ . '/..' . '/mezzio/mezzio-fastroute/src',
        ),
        'Mezzio\\Helper\\' =>
        array (
            0 => __DIR__ . '/..' . '/mezzio/mezzio-helpers/src',
        ),
        'Mezzio\\' =>
        array (
            0 => __DIR__ . '/..' . '/mezzio/mezzio/src',
        ),
        'Light\\Page\\' =>
        array (
            0 => __DIR__ . '/../..' . '/src/Page/src',
        ),
        'Light\\Core\\' =>
        array (
            0 => __DIR__ . '/../..' . '/src/Core/src',
        ),
        'Light\\App\\' =>
        array (
            0 => __DIR__ . '/../..' . '/src/App/src',
        ),
        'Laminas\\Stratigility\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-stratigility/src',
        ),
        'Laminas\\Stdlib\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-stdlib/src',
        ),
        'Laminas\\ServiceManager\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-servicemanager/src',
        ),
        'Laminas\\HttpHandlerRunner\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-httphandlerrunner/src',
        ),
        'Laminas\\Diactoros\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-diactoros/src',
        ),
        'Laminas\\ConfigAggregator\\' =>
        array (
            0 => __DIR__ . '/..' . '/laminas/laminas-config-aggregator/src',
        ),
        'Fig\\Http\\Message\\' =>
        array (
            0 => __DIR__ . '/..' . '/fig/http-message-util/src',
        ),
        'FastRoute\\' =>
        array (
            0 => __DIR__ . '/..' . '/nikic/fast-route/src',
        ),
        'Dotkernel\\Dot\\ErrorHandler\\' =>
        array (
            0 => __DIR__ . '/..' . '/dotkernel/dot-errorhandler/src',
        ),
        'Dot\\Log\\' =>
        array (
            0 => __DIR__ . '/..' . '/dotkernel/dot-log/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit18fadace00cbfa835c1efb3ff56ea35e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit18fadace00cbfa835c1efb3ff56ea35e::$prefixDirsPsr4;
        }, null, ClassLoader::class);
    }
}
PHP;

        file_put_contents($vendorDir . '/composer/autoload_static.php', $staticContent);

        // Create minimal autoload_psr4.php
        $psr4Content = <<<'PHP'
<?php

// autoload_psr4.php @generated by ultra-minimal deployment

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'ResponsiveSk\\Slim4Paths\\' => array($vendorDir . '/responsive-sk/slim4-paths/src'),
    'Psr\\Http\\Server\\' => array($vendorDir . '/psr/http-server-middleware/src', $vendorDir . '/psr/http-server-handler/src'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-message/src', $vendorDir . '/psr/http-factory/src'),
    'Psr\\Log\\' => array($vendorDir . '/psr/log/src'),
    'Psr\\Container\\' => array($vendorDir . '/psr/container/src'),
    'Mezzio\\Template\\' => array($vendorDir . '/mezzio/mezzio-template/src'),
    'Mezzio\\Router\\' => array($vendorDir . '/mezzio/mezzio-router/src', $vendorDir . '/mezzio/mezzio-fastroute/src'),
    'Mezzio\\Helper\\' => array($vendorDir . '/mezzio/mezzio-helpers/src'),
    'Mezzio\\' => array($vendorDir . '/mezzio/mezzio/src'),
    'Light\\Page\\' => array($baseDir . '/src/Page/src'),
    'Light\\Core\\' => array($baseDir . '/src/Core/src'),
    'Light\\App\\' => array($baseDir . '/src/App/src'),
    'Laminas\\Stratigility\\' => array($vendorDir . '/laminas/laminas-stratigility/src'),
    'Laminas\\Stdlib\\' => array($vendorDir . '/laminas/laminas-stdlib/src'),
    'Laminas\\ServiceManager\\' => array($vendorDir . '/laminas/laminas-servicemanager/src'),
    'Laminas\\HttpHandlerRunner\\' => array($vendorDir . '/laminas/laminas-httphandlerrunner/src'),
    'Laminas\\Diactoros\\' => array($vendorDir . '/laminas/laminas-diactoros/src'),
    'Laminas\\ConfigAggregator\\' => array($vendorDir . '/laminas/laminas-config-aggregator/src'),
    'Fig\\Http\\Message\\' => array($vendorDir . '/fig/http-message-util/src'),
    'FastRoute\\' => array($vendorDir . '/nikic/fast-route/src'),
    'Dotkernel\\Dot\\ErrorHandler\\' => array($vendorDir . '/dotkernel/dot-errorhandler/src'),
    'Dot\\Log\\' => array($vendorDir . '/dotkernel/dot-log/src'),
    'Webmozart\\Assert\\' => array($vendorDir . '/webmozart/assert/src'),
);
PHP;

        file_put_contents($vendorDir . '/composer/autoload_psr4.php', $psr4Content);

        // Create empty autoload files
        file_put_contents($vendorDir . '/composer/autoload_namespaces.php', "<?php\nreturn array();\n");
        file_put_contents($vendorDir . '/composer/autoload_classmap.php', "<?php\nreturn array();\n");
        // Create autoload_files.php with Mezzio constants
        $autoloadFilesContent = <<<'PHP'
<?php
return array(
    'mezzio_constants' => $vendorDir . '/mezzio/mezzio/src/constants.php',
);

PHP;
        file_put_contents($vendorDir . '/composer/autoload_files.php', $autoloadFilesContent);

        // Create simple autoload_real.php
        $autoloadRealContent = <<<'PHP'
<?php

// autoload_real.php @generated by ultra-minimal deployment

class ComposerAutoloaderInit
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit', 'loadClassLoader'));

        $map = require __DIR__ . '/autoload_namespaces.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }

        $loader->register(true);

        return $loader;
    }
}
PHP;

        file_put_contents($vendorDir . '/composer/autoload_real.php', $autoloadRealContent);

        echo "    ✅ Clean autoload files created\n";
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

// Run deployment
$deployer = new SharedHostingDeployer();
$deployer->deploy();
