<?php

declare(strict_types=1);

/**
 * DotKernel Light - Production Build Script.
 *
 * Compatible with slim4-paths v6.0 and var/ directory structure
 * Supports multiple build targets: production, shared-hosting, shared-hosting-minimal
 */

use ResponsiveSk\Slim4Paths\Paths;

// Ensure we're running from project root
if (!file_exists('composer.json')) {
    echo "Error: Must be run from project root directory\n";
    exit(1);
}

require_once 'vendor/autoload.php';

class ProductionBuilder
{
    private string $buildTarget;

    private string $buildDir;

    private string $packageName;

    private string $version;

    private Paths $paths;

    /** @var array<string> */
    private array $excludePatterns = [];

    // Colors for output
    private const RED = "\033[0;31m";
    private const GREEN = "\033[0;32m";
    private const YELLOW = "\033[1;33m";
    private const BLUE = "\033[0;34m";
    private const NC = "\033[0m";

    public function __construct(string $buildTarget = 'production')
    {
        $this->buildTarget = $buildTarget;
        $this->buildDir = $this->getBuildDir();
        $this->packageName = $this->getPackageName();
        $version = $_ENV['VERSION'] ?? date('Ymd_His');
        $this->version = is_string($version) ? $version : date('Ymd_His');

        // Initialize paths service with v6.0 API
        $this->initializePaths();
        $this->setupExcludePatterns();
    }

    private function initializePaths(): void
    {
        try {
            // Load paths configuration
            /** @var array{paths: array{base_path?: string, custom_paths?: array<string, string>}} $config */
            $config = require 'config/autoload/paths.global.php';
            $basePath = $config['paths']['base_path'] ?? dirname(__DIR__);

            // Create Paths instance with v6.0 API
            $this->paths = new Paths($basePath);

            // Apply custom paths from configuration
            if (isset($config['paths']['custom_paths'])) {
                foreach ($config['paths']['custom_paths'] as $name => $path) {
                    $this->paths->set($name, $path);
                }
            }

            $this->log('Paths service initialized with slim4-paths v6.0');
        } catch (Exception $e) {
            $this->error('Failed to initialize paths: ' . $e->getMessage());
            exit(1);
        }
    }

    private function getBuildDir(): string
    {
        $buildDir = $_ENV['BUILD_DIR'] ?? './build';
        $baseDir = is_string($buildDir) ? $buildDir : './build';

        return match ($this->buildTarget) {
            'shared-hosting-minimal' => "{$baseDir}/shared-hosting-minimal",
            'shared-hosting' => "{$baseDir}/shared-hosting",
            default => "{$baseDir}/production"
        };
    }

    private function getPackageName(): string
    {
        $packageName = $_ENV['PACKAGE_NAME'] ?? 'dotkernel-light';
        $baseName = is_string($packageName) ? $packageName : 'dotkernel-light';

        return match ($this->buildTarget) {
            'shared-hosting-minimal' => "{$baseName}-shared-hosting-minimal",
            'shared-hosting' => "{$baseName}-shared-hosting",
            default => "{$baseName}-production"
        };
    }

    private function setupExcludePatterns(): void
    {
        // Base patterns for all builds
        $this->excludePatterns = [
            '.git',
            '.gitignore',
            'node_modules',
            'tests',
            'test',
            'phpunit.xml*',
            'phpcs.xml*',
            'phpstan.neon*',
            '.phpunit.result.cache',
            '*.log',
            'var/cache/*',
            'var/sessions/*',
            'var/tmp/*',
            'config/autoload/*.local.php',
            '.env',
            'build',
            '.idea',
            '.vscode',
            '*.backup',
            'coverage-html',
            'coverage.txt',
            'clover.xml',
        ];

        // Additional patterns for minimal builds
        if ($this->buildTarget === 'shared-hosting-minimal') {
            $this->excludePatterns = array_merge($this->excludePatterns, [
                'docs',
                '*.md',
                'README*',
                'CHANGELOG*',
                'LICENSE*',
                'bin/build-*.php',
                'bin/build-*.sh',
                'bin/test-*.sh',
                'bin/dev-*.sh',
                'rector.php',
                'debug-templates.php',
            ]);
        }
    }

    public function build(): void
    {
        $this->log("Starting DotKernel Light {$this->buildTarget} build...");

        try {
            $this->cleanBuild();
            $this->installProductionDependencies();
            $this->copyApplicationFiles();
            $this->createVarDirectoryStructure();
            $this->copyRuntimeData();
            $this->buildAssets();
            $this->createProductionConfigs();
            $this->setupWebFiles();
            $this->optimizeAutoloader();
            $this->cleanVendorForMinimal();
            $this->minimizeBinScripts();
            $this->setProductionPermissions();
            $this->validateBuild();
            $this->createPackage();
            $this->createDeploymentInstructions();
            $this->restoreDevelopment();

            $this->success("{$this->buildTarget} build completed successfully!");
            $this->displayBuildSummary();
        } catch (Exception $e) {
            $this->error('Build failed: ' . $e->getMessage());
            exit(1);
        }
    }

    private function cleanBuild(): void
    {
        $this->log('Cleaning previous build...');

        if (is_dir($this->buildDir)) {
            $this->removeDirectory($this->buildDir);
        }

        if (!mkdir($this->buildDir, 0o755, true)) {
            throw new RuntimeException("Failed to create build directory: {$this->buildDir}");
        }

        $this->success('Build directory cleaned');
    }

    private function installProductionDependencies(): void
    {
        $this->log('Installing production dependencies...');

        // Backup current composer.lock
        if (file_exists('composer.lock')) {
            copy('composer.lock', 'composer.lock.backup');
        }

        $result = $this->executeCommand('composer install --no-dev --optimize-autoloader --no-interaction');
        if ($result !== 0) {
            throw new RuntimeException('Failed to install production dependencies');
        }

        $this->success('Production dependencies installed');
    }

    private function copyApplicationFiles(): void
    {
        $this->log("Copying application files for {$this->buildTarget} build...");

        // Create rsync exclude arguments
        $excludeArgs = '';
        foreach ($this->excludePatterns as $pattern) {
            $excludeArgs .= " --exclude='" . $pattern . "'";
        }

        $command = "rsync -av {$excludeArgs} ./ {$this->buildDir}/";
        $result = $this->executeCommand($command);

        if ($result !== 0) {
            throw new RuntimeException('Failed to copy application files');
        }

        // Additional cleanup for minimal builds
        if ($this->buildTarget === 'shared-hosting-minimal') {
            $this->performMinimalCleanup();
        }

        $this->success('Application files copied');
    }

    private function createVarDirectoryStructure(): void
    {
        $this->log('Creating var/ directory structure (slim4-paths v6.0)...');

        // Create var/ structure using paths service
        $varDirs = [
            'var/data',
            'var/logs',
            'var/cache/config',
            'var/cache/twig',
            'var/cache/routes',
            'var/tmp',
            'var/sessions',
            'var/uploads',
        ];

        foreach ($varDirs as $dir) {
            $fullPath = $this->paths->buildPath($this->buildDir . '/' . $dir);
            if (!is_dir($fullPath)) {
                if (!mkdir($fullPath, 0o755, true)) {
                    throw new RuntimeException("Failed to create directory: {$fullPath}");
                }
            }

            // Create .gitkeep files to preserve empty directories
            $gitkeepFile = $fullPath . '/.gitkeep';
            if (!file_exists($gitkeepFile)) {
                touch($gitkeepFile);
            }
        }

        $this->success('Var directory structure created');
    }

    private function copyRuntimeData(): void
    {
        $this->log('Copying runtime data...');

        // Copy databases if they exist
        $storageDir = $this->paths->getPath('data', 'var/data');
        if (is_dir($storageDir)) {
            $buildStorageDir = $this->buildDir . '/var/data';
            $this->copyDirectory($storageDir, $buildStorageDir);
            $this->log('Runtime data copied');
        }

        // Copy any existing logs (but not all)
        $logsDir = $this->paths->getPath('logs', 'var/logs');
        if (is_dir($logsDir)) {
            $buildLogsDir = $this->buildDir . '/var/logs';
            // Only copy important logs, not all
            $this->copySelectiveLogs($logsDir, $buildLogsDir);
        }

        $this->success('Runtime data copied');
    }

    private function buildAssets(): void
    {
        $this->log('Building frontend assets...');

        // Check if package.json exists
        if (!file_exists('package.json')) {
            $this->log('No package.json found, skipping asset build');

            return;
        }

        // Check if pnpm is available (user preference)
        $packageManager = $this->commandExists('pnpm') ? 'pnpm' : 'npm';

        // Install dependencies
        $result = $this->executeCommand("{$packageManager} install");
        if ($result !== 0) {
            $this->warning('Failed to install frontend dependencies');

            return;
        }

        // Build assets
        $result = $this->executeCommand("{$packageManager} run build");
        if ($result !== 0) {
            $this->warning('Failed to build frontend assets');

            return;
        }

        $this->success('Frontend assets built');
    }

    private function createProductionConfigs(): void
    {
        $this->log('Creating production configuration templates...');

        $configTemplates = [
            'config/autoload/database.local.php.dist',
            'config/autoload/session.local.php.dist',
        ];

        foreach ($configTemplates as $template) {
            if (!file_exists($this->buildDir . '/' . $template)) {
                $this->warning("Configuration template not found: {$template}");
            }
        }

        $this->success('Production configuration templates ready');
    }

    private function setupWebFiles(): void
    {
        $this->log('Setting up web files (robots.txt, .htaccess, sitemap.xml)...');

        $this->setupRobotsTxt();
        $this->optimizeHtaccess();
        $this->generateSitemap();

        $this->success('Web files configured');
    }

    private function setupRobotsTxt(): void
    {
        $robotsDistPath = $this->buildDir . '/public/robots.txt.dist';
        $robotsPath = $this->buildDir . '/public/robots.txt';

        if (!file_exists($robotsDistPath)) {
            $this->warning('robots.txt.dist not found, creating default robots.txt');
            $this->createDefaultRobotsTxt($robotsPath);

            return;
        }

        // Create production robots.txt
        $robotsContent = $this->createProductionRobotsTxt();
        file_put_contents($robotsPath, $robotsContent);

        // Remove .dist file for production (we know it exists from check above)
        unlink($robotsDistPath);

        $this->log('Production robots.txt created');
    }

    private function createProductionRobotsTxt(): string
    {
        $sitemapUrl = $this->getSitemapUrl();

        return <<<EOF
            # Production robots.txt for DotKernel Light
            # Generated automatically during build process

            User-agent: *
            Allow: /

            # Disallow sensitive directories
            Disallow: /config/
            Disallow: /src/
            Disallow: /var/
            Disallow: /vendor/
            Disallow: /bin/
            Disallow: /.git/

            # Allow assets
            Allow: /css/
            Allow: /js/
            Allow: /images/
            Allow: /fonts/

            # Sitemap
            Sitemap: {$sitemapUrl}

            # Crawl delay (optional - adjust as needed)
            Crawl-delay: 1

            EOF;
    }

    private function optimizeHtaccess(): void
    {
        $htaccessPath = $this->buildDir . '/public/.htaccess';

        if (!file_exists($htaccessPath)) {
            $this->warning('.htaccess not found, creating optimized version');
            $this->createOptimizedHtaccess($htaccessPath);

            return;
        }

        // Read current .htaccess
        $currentContent = file_get_contents($htaccessPath);
        if ($currentContent === false) {
            throw new RuntimeException("Failed to read .htaccess file: {$htaccessPath}");
        }

        // Add production optimizations
        $optimizedContent = $this->addHtaccessOptimizations($currentContent);

        file_put_contents($htaccessPath, $optimizedContent);

        $this->log('Optimized .htaccess for production');
    }

    private function addHtaccessOptimizations(string $currentContent): string
    {
        $optimizations = <<<EOF

            # Production optimizations added by build system
            # Security headers
            <IfModule mod_headers.c>
                # Security headers
                Header always set X-Content-Type-Options nosniff
                Header always set X-Frame-Options DENY
                Header always set X-XSS-Protection "1; mode=block"
                Header always set Referrer-Policy "strict-origin-when-cross-origin"
                Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"

                # Cache control for static assets
                <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
                    Header set Cache-Control "public, max-age=31536000, immutable"
                </FilesMatch>

                # Cache control for HTML
                <FilesMatch "\.(html|htm)$">
                    Header set Cache-Control "public, max-age=3600"
                </FilesMatch>
            </IfModule>

            # Compression
            <IfModule mod_deflate.c>
                AddOutputFilterByType DEFLATE text/plain
                AddOutputFilterByType DEFLATE text/html
                AddOutputFilterByType DEFLATE text/xml
                AddOutputFilterByType DEFLATE text/css
                AddOutputFilterByType DEFLATE application/xml
                AddOutputFilterByType DEFLATE application/xhtml+xml
                AddOutputFilterByType DEFLATE application/rss+xml
                AddOutputFilterByType DEFLATE application/javascript
                AddOutputFilterByType DEFLATE application/x-javascript
                AddOutputFilterByType DEFLATE application/json
            </IfModule>

            # Browser caching
            <IfModule mod_expires.c>
                ExpiresActive On
                ExpiresByType text/css "access plus 1 year"
                ExpiresByType application/javascript "access plus 1 year"
                ExpiresByType image/png "access plus 1 year"
                ExpiresByType image/jpg "access plus 1 year"
                ExpiresByType image/jpeg "access plus 1 year"
                ExpiresByType image/gif "access plus 1 year"
                ExpiresByType image/ico "access plus 1 year"
                ExpiresByType image/svg+xml "access plus 1 year"
                ExpiresByType font/woff "access plus 1 year"
                ExpiresByType font/woff2 "access plus 1 year"
            </IfModule>

            # Security - Hide sensitive files
            <FilesMatch "(^#.*#|\.(bak|config|dist|fla|inc|ini|log|psd|sh|sql|sw[op])|~)$">
                Order allow,deny
                Deny from all
                Satisfy All
            </FilesMatch>

            # Prevent access to sensitive directories
            RedirectMatch 404 /\.git
            RedirectMatch 404 /\.svn
            RedirectMatch 404 /config/
            RedirectMatch 404 /src/
            RedirectMatch 404 /var/
            RedirectMatch 404 /vendor/
            RedirectMatch 404 /bin/

            EOF;

        return $currentContent . $optimizations;
    }

    private function createOptimizedHtaccess(string $htaccessPath): void
    {
        $content = <<<EOF
            RewriteEngine On

            # The following rule allows authentication to work with fast-cgi
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

            # The following rule tells Apache that if the requested filename exists, simply serve it.
            RewriteCond %{REQUEST_FILENAME} -s [OR]
            RewriteCond %{REQUEST_FILENAME} -l [OR]
            RewriteCond %{REQUEST_FILENAME} -d
            RewriteRule ^.*$ - [NC,L]

            # The following rewrites all other queries to index.php
            RewriteCond %{REQUEST_URI}::$1 ^(/.+)(.+)::\2$
            RewriteRule ^(.*) - [E=BASE:%1]
            RewriteRule ^(.*)$ %{ENV:BASE}index.php [NC,L]

            EOF;

        $optimizedContent = $this->addHtaccessOptimizations($content);
        file_put_contents($htaccessPath, $optimizedContent);
    }

    private function generateSitemap(): void
    {
        $sitemapPath = $this->buildDir . '/public/sitemap.xml';

        $sitemap = $this->createBasicSitemap();
        file_put_contents($sitemapPath, $sitemap);

        $this->log('Basic sitemap.xml generated');
    }

    private function createBasicSitemap(): string
    {
        $baseUrl = $this->getBaseUrl();
        $lastmod = date('Y-m-d');

        return <<<EOF
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                <url>
                    <loc>{$baseUrl}</loc>
                    <lastmod>{$lastmod}</lastmod>
                    <changefreq>weekly</changefreq>
                    <priority>1.0</priority>
                </url>
                <url>
                    <loc>{$baseUrl}/page/about</loc>
                    <lastmod>{$lastmod}</lastmod>
                    <changefreq>monthly</changefreq>
                    <priority>0.8</priority>
                </url>
                <url>
                    <loc>{$baseUrl}/page/who-we-are</loc>
                    <lastmod>{$lastmod}</lastmod>
                    <changefreq>monthly</changefreq>
                    <priority>0.8</priority>
                </url>
            </urlset>
            EOF;
    }

    private function createDefaultRobotsTxt(string $robotsPath): void
    {
        $content = $this->createProductionRobotsTxt();
        file_put_contents($robotsPath, $content);
    }

    private function getBaseUrl(): string
    {
        // Priority: Environment variable > Build config > Default
        $baseUrl = getenv('BASE_URL') ?: ($_ENV['BASE_URL'] ?? null);

        if ($baseUrl === null) {
            $config = $this->getBuildConfig();
            $baseUrl = $config['base_url'];
        }

        // Remove trailing slash
        if (!is_string($baseUrl)) {
            throw new RuntimeException('Base URL must be a string');
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function getBuildConfig(): array
    {
        /** @var array<string, mixed>|null $config */
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $configFile = 'config/build.php';

        if (!file_exists($configFile)) {
            /** @var array<string, mixed> $config */
            $config = ['base_url' => 'https://yourdomain.com'];

            return $config;
        }

        $loadedConfig = require $configFile;
        if (!is_array($loadedConfig)) {
            throw new RuntimeException('Build config must return an array');
        }
        /** @var array<string, mixed> $config */
        $config = $loadedConfig;

        // Apply environment-specific overrides if available
        $environment = getenv('BUILD_ENV') ?: ($_ENV['BUILD_ENV'] ?? 'production');
        if (isset($config['environments'][$environment]) && is_array($config['environments'][$environment])) {
            /** @var array<string, mixed> $envConfig */
            $envConfig = $config['environments'][$environment];
            $config = array_merge($config, $envConfig);
        }

        return $config;
    }

    private function getSitemapUrl(): string
    {
        return $this->getBaseUrl() . '/sitemap.xml';
    }

    private function optimizeAutoloader(): void
    {
        $this->log('Optimizing autoloader...');

        $currentDir = getcwd();
        if ($currentDir === false) {
            throw new RuntimeException('Failed to get current directory');
        }

        chdir($this->buildDir);

        $result = $this->executeCommand('composer dump-autoload --optimize --no-dev');

        chdir($currentDir);

        if ($result !== 0) {
            throw new RuntimeException('Failed to optimize autoloader');
        }

        $this->success('Autoloader optimized');
    }

    // Helper methods
    private function log(string $message): void
    {
        echo self::BLUE . '[' . date('Y-m-d H:i:s') . ']' . self::NC . " {$message}\n";
    }

    private function error(string $message): void
    {
        echo self::RED . '[ERROR]' . self::NC . " {$message}\n";
    }

    private function success(string $message): void
    {
        echo self::GREEN . '[SUCCESS]' . self::NC . " {$message}\n";
    }

    private function warning(string $message): void
    {
        echo self::YELLOW . '[WARNING]' . self::NC . " {$message}\n";
    }

    private function executeCommand(string $command): int
    {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Command failed: {$command}");
            foreach ($output as $line) {
                echo "  {$line}\n";
            }
        }

        return $returnCode;
    }

    private function commandExists(string $command): bool
    {
        $result = shell_exec("which {$command}");

        return !empty($result);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        $files = array_diff(scandir($source), ['.', '..']);
        foreach ($files as $file) {
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    private function copySelectiveLogs(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        // Only copy recent error logs, not all logs
        $files = glob($source . '/error-log-*.log');
        if ($files === false) {
            return;
        }

        $recentFiles = array_slice($files, -3); // Last 3 error logs

        foreach ($recentFiles as $file) {
            $filename = basename($file);
            copy($file, $destination . '/' . $filename);
        }
    }

    private function performMinimalCleanup(): void
    {
        $this->log('Performing additional cleanup for minimal build...');

        // Remove development files
        $devFiles = [
            $this->buildDir . '/.DS_Store',
            $this->buildDir . '/Thumbs.db',
        ];

        foreach ($devFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Remove empty directories
        $this->removeEmptyDirectories($this->buildDir);
    }

    private function removeEmptyDirectories(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeEmptyDirectories($path);
                // Try to remove if empty
                @rmdir($path);
            }
        }
    }

    private function cleanVendorForMinimal(): void
    {
        if ($this->buildTarget !== 'shared-hosting-minimal') {
            return;
        }

        $this->log('Cleaning vendor for minimal production build...');

        $vendorDir = $this->buildDir . '/vendor';
        if (!is_dir($vendorDir)) {
            return;
        }

        // Remove test directories
        $this->removeVendorDirectories($vendorDir, ['test', 'tests', 'Test', 'Tests']);

        // Remove documentation
        $this->removeVendorFiles($vendorDir, ['*.md', 'README*', 'CHANGELOG*', 'LICENSE*', 'CONTRIBUTING*']);

        // Remove documentation directories
        $this->removeVendorDirectories($vendorDir, ['doc', 'docs', 'examples', '.github']);

        // Remove development files
        $this->removeVendorFiles($vendorDir, ['phpunit.xml*', 'phpcs.xml*', 'phpstan.neon*', '.travis.yml']);

        $this->success('Vendor cleaned for minimal production');
    }

    /**
     * @param array<string> $dirNames
     */
    private function removeVendorDirectories(string $vendorDir, array $dirNames): void
    {
        foreach ($dirNames as $dirName) {
            $command = "find '{$vendorDir}' -type d -name '" . $dirName . "' -exec rm -rf {} + 2>/dev/null || true";
            $this->executeCommand($command);
        }
    }

    /**
     * @param array<string> $patterns
     */
    private function removeVendorFiles(string $vendorDir, array $patterns): void
    {
        foreach ($patterns as $pattern) {
            $command = "find '{$vendorDir}' -name '" . $pattern . "' -delete 2>/dev/null || true";
            $this->executeCommand($command);
        }
    }

    private function minimizeBinScripts(): void
    {
        if ($this->buildTarget !== 'shared-hosting-minimal') {
            return;
        }

        $this->log('Minimizing bin scripts for shared hosting...');

        $binDir = $this->buildDir . '/bin';
        if (!is_dir($binDir)) {
            return;
        }

        // Keep only essential scripts
        $essentialScripts = [
            'clear-config-cache.php',
            'composer-post-install-script.php',
        ];

        // Remove all scripts first
        $files = glob($binDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Copy back only essential scripts
        foreach ($essentialScripts as $script) {
            $sourcePath = 'bin/' . $script;
            $destPath = $binDir . '/' . $script;

            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
                chmod($destPath, 0o755);
                $this->log("Kept essential script: {$script}");
            }
        }

        $this->success('Bin scripts minimized for shared hosting');
    }

    private function setProductionPermissions(): void
    {
        $this->log('Setting production file permissions...');

        // Set directory permissions
        $this->executeCommand("find '{$this->buildDir}' -type d -exec chmod 755 {} \\;");

        // Set file permissions
        $this->executeCommand("find '{$this->buildDir}' -type f -exec chmod 644 {} \\;");

        // Make scripts executable
        $binDir = $this->buildDir . '/bin';
        if (is_dir($binDir)) {
            $this->executeCommand("chmod +x '{$binDir}'/*.php");
            $this->executeCommand("chmod +x '{$binDir}'/*.sh 2>/dev/null || true");
        }

        // Protect sensitive configuration templates
        $configDir = $this->buildDir . '/config/autoload';
        if (is_dir($configDir)) {
            $this->executeCommand("chmod 600 '{$configDir}'/*.dist 2>/dev/null || true");
        }

        $this->success('Production permissions set');
    }

    private function validateBuild(): void
    {
        $this->log('Validating build...');

        $errors = 0;

        // Check required files
        $requiredFiles = [
            'public/index.php',
            'config/config.php',
            'vendor/autoload.php',
            'config/autoload/paths.global.php',
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($this->buildDir . '/' . $file)) {
                $this->error("Required file missing: {$file}");
                $errors++;
            }
        }

        // Check required directories (var/ structure)
        $requiredDirs = [
            'config/autoload',
            'src',
            'vendor',
            'var',
            'var/data',
            'var/logs',
            'var/cache',
            'var/tmp',
        ];

        foreach ($requiredDirs as $dir) {
            if (!is_dir($this->buildDir . '/' . $dir)) {
                $this->error("Required directory missing: {$dir}");
                $errors++;
            }
        }

        if ($errors === 0) {
            $this->success('Build validation passed');
        } else {
            throw new RuntimeException("Build validation failed with {$errors} errors");
        }
    }

    private function createPackage(): void
    {
        $this->log('Creating deployment package...');

        $packageFile = "{$this->packageName}_{$this->version}.tar.gz";
        $packagePath = getcwd() . '/' . $packageFile;

        // Create tarball
        $currentDir = getcwd();
        if ($currentDir === false) {
            throw new RuntimeException('Failed to get current directory');
        }

        chdir($this->buildDir);

        $result = $this->executeCommand("tar -czf '{$packagePath}' .");

        chdir($currentDir);

        if ($result !== 0) {
            throw new RuntimeException('Failed to create package');
        }

        // Create checksum
        $checksum = hash_file('sha256', $packagePath);
        file_put_contents($packagePath . '.sha256', $checksum . '  ' . basename($packagePath) . "\n");

        $this->success("Package created: {$packagePath}");
        $this->success("Checksum created: {$packagePath}.sha256");
    }

    private function createDeploymentInstructions(): void
    {
        $this->log('Creating deployment instructions...');

        $instructionsFile = $this->buildDir . '/DEPLOYMENT_INSTRUCTIONS.txt';
        $packageFile = "{$this->packageName}_{$this->version}.tar.gz";

        if ($this->buildTarget === 'shared-hosting-minimal') {
            $instructions = $this->getSharedHostingInstructions($packageFile);
        } else {
            $instructions = $this->getProductionInstructions($packageFile);
        }

        file_put_contents($instructionsFile, $instructions);
        $this->success('Deployment instructions created');
    }

    private function getSharedHostingInstructions(string $packageFile): string
    {
        return <<<EOF
            DotKernel Light - Shared Hosting Deployment Instructions
            ========================================================

            Package: {$packageFile}
            Created: " . date('Y-m-d H:i:s') . "
            Build Type: Shared Hosting Minimal
            Compatible with: slim4-paths v6.0

            Prerequisites:
            - PHP 8.2+ with required extensions (mbstring, json, openssl)
            - Shared hosting with Apache
            - MySQL/MariaDB database access
            - File manager or FTP access

            Deployment Steps:

            1. Upload and extract package:
               - Upload {$packageFile} to your hosting account
               - Extract to your desired directory (e.g., /home/username/light/)

            2. Set Document Root:
               - In your hosting control panel, set document root to: /home/username/light/public/
               - This ensures only the public directory is web-accessible

            3. Configure database:
               cp config/autoload/database.local.php.dist config/autoload/database.local.php
               # Edit database.local.php with your hosting database credentials

            4. Configure sessions:
               cp config/autoload/session.local.php.dist config/autoload/session.local.php
               # Edit session settings if needed

            5. Set permissions (if possible):
               chmod 755 var/
               chmod 755 var/data/ var/logs/ var/cache/ var/tmp/

            Directory Structure (var/ based - slim4-paths v6.0):
            var/
            ├── data/           # Application data
            ├── logs/           # Log files
            ├── cache/          # Cache files
            │   ├── config/     # Config cache
            │   ├── twig/       # Twig cache
            │   └── routes/     # Route cache
            ├── tmp/            # Temporary files
            └── sessions/       # Session files

            Verification:
            - Visit your domain to see the application
            - Check that CSS/JS assets load correctly
            - Verify var/ directories are writable

            Troubleshooting:
            - Internal Server Error: Check document root points to public/ directory
            - Missing assets: Verify public/ directory uploaded correctly
            - Database errors: Check database.local.php configuration
            - Permission errors: Ensure var/ directories are writable

            Support:
            - Documentation: docs/
            - Minimal bin scripts included for essential operations
            - Compatible with slim4-paths v6.0 API

            EOF;
    }

    private function getProductionInstructions(string $packageFile): string
    {
        return <<<EOF
            DotKernel Light - Production Deployment Instructions
            ===================================================

            Package: {$packageFile}
            Created: " . date('Y-m-d H:i:s') . "
            Build Type: Production
            Compatible with: slim4-paths v6.0

            Prerequisites:
            - PHP 8.2+ with required extensions
            - Web server (Nginx/Apache)
            - Database server (MySQL/PostgreSQL)
            - Redis server (optional, for sessions)

            Deployment Steps:

            1. Extract package to web directory:
               tar -xzf {$packageFile} -C /var/www/light

            2. Copy and configure database:
               cp config/autoload/database.local.php.dist config/autoload/database.local.php
               # Edit database.local.php with your database settings

            3. Copy and configure sessions:
               cp config/autoload/session.local.php.dist config/autoload/session.local.php
               # Edit session.local.php with your session settings

            4. Set proper permissions:
               chown -R www-data:www-data /var/www/light
               chmod 755 /var/www/light/var /var/www/light/var/*

            5. Configure web server to point to /var/www/light/public

            Directory Structure (var/ based - slim4-paths v6.0):
            var/
            ├── data/           # Application data
            ├── logs/           # Log files
            ├── cache/          # Cache files
            ├── tmp/            # Temporary files
            └── sessions/       # Session files

            Health Check:
            - URL: http://your-domain.com/
            - Should display the application homepage

            Support:
            - Documentation: docs/
            - Compatible with slim4-paths v6.0 API
            - Uses modern var/ directory structure

            EOF;
    }

    private function restoreDevelopment(): void
    {
        $this->log('Restoring development environment...');

        // Restore composer.lock if it was backed up
        if (file_exists('composer.lock.backup')) {
            rename('composer.lock.backup', 'composer.lock');
        }

        // Reinstall development dependencies
        $result = $this->executeCommand('composer install');
        if ($result !== 0) {
            $this->warning('Failed to restore development dependencies');

            return;
        }

        $this->success('Development environment restored');
    }

    private function displayBuildSummary(): void
    {
        $packageFile = "{$this->packageName}_{$this->version}.tar.gz";
        $packagePath = getcwd() . '/' . $packageFile;
        $fileSize = file_exists($packagePath) ? filesize($packagePath) : false;
        $size = $fileSize !== false ? $this->formatBytes($fileSize) : 'Unknown';

        echo "\n";
        echo "=== Build Summary ===\n";
        echo "Build type: {$this->buildTarget}\n";
        echo "Build directory: {$this->buildDir}\n";
        echo "Package: {$packageFile}\n";
        echo "Package size: {$size}\n";
        echo "Instructions: DEPLOYMENT_INSTRUCTIONS.txt\n";
        echo "Compatible with: slim4-paths v6.0\n";
        echo "Directory structure: var/ based\n";
        echo "===================\n";
        echo "\n";
        echo "Ready for deployment!\n";
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$buildTarget = $argv[1] ?? 'production';
$validTargets = ['production', 'shared-hosting', 'shared-hosting-minimal'];

if (!in_array($buildTarget, $validTargets, true)) {
    echo 'Invalid build target. Valid options: ' . implode(', ', $validTargets) . "\n";
    exit(1);
}

try {
    $builder = new ProductionBuilder($buildTarget);
    $builder->build();
} catch (Exception $e) {
    echo "\033[0;31m[FATAL ERROR]\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
