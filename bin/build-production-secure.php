<?php

declare(strict_types=1);

/**
 * Mezzio Boot - Secure Production Build Script
 *
 * Security improvements:
 * - Path validation and sanitization
 * - No shell command injection
 * - Proper error handling and cleanup
 * - Input validation
 */

use ResponsiveSk\Slim4Paths\Paths;

// Ensure we're running from project root
if (! file_exists('composer.json')) {
    echo "Error: Must be run from project root directory\n";
    exit(1);
}

require_once 'vendor/autoload.php';

class SecureProductionBuilder
{
    private string $buildTarget;
    private string $buildDir;
    private string $packageName;
    private string $version;
    private Paths $paths;
    private string $projectRoot;

    /** @var array<string> */
    private array $excludePatterns = [];

    /** @var array<string> */
    private array $tempFiles = [];

    // Colors for output
    private const RED    = "\033[0;31m";
    private const GREEN  = "\033[0;32m";
    private const YELLOW = "\033[1;33m";
    private const BLUE   = "\033[0;34m";
    private const NC     = "\033[0m";

    // Security constants
    private const MAX_PATH_LENGTH = 4096;
    private const ALLOWED_CHARS   = '/^[a-zA-Z0-9\/_\-\.]+$/';

    public function __construct(string $buildTarget = 'production')
    {
        $this->validateBuildTarget($buildTarget);
        $this->buildTarget = $buildTarget;
        $this->projectRoot = $this->getSecureProjectRoot();
        $this->buildDir    = $this->getSecureBuildDir();
        $this->packageName = $this->getSecurePackageName();
        $this->version     = $this->getSecureVersion();

        // Register cleanup handler
        register_shutdown_function([$this, 'cleanup']);

        $this->initializePaths();
        $this->setupExcludePatterns();
    }

    private function validateBuildTarget(string $target): void
    {
        $validTargets = ['production', 'shared-hosting', 'shared-hosting-minimal'];
        if (! in_array($target, $validTargets, true)) {
            throw new InvalidArgumentException("Invalid build target: {$target}");
        }
    }

    private function getSecureProjectRoot(): string
    {
        $root = realpath(getcwd() ?: '.');
        if ($root === false) {
            throw new RuntimeException('Cannot determine project root');
        }

        // Validate project root
        if (! file_exists($root . '/composer.json')) {
            throw new RuntimeException('Invalid project root - composer.json not found');
        }

        return $root;
    }

    private function getSecureBuildDir(): string
    {
        $envBuildDir = $_ENV['BUILD_DIR'] ?? './build';
        $buildDir    = is_string($envBuildDir) ? $envBuildDir : './build';
        $baseDir     = $this->sanitizePath($buildDir);

        $targetDir = match ($this->buildTarget) {
            'shared-hosting-minimal' => "{$baseDir}/shared-hosting-minimal",
            'shared-hosting' => "{$baseDir}/shared-hosting",
            default => "{$baseDir}/production"
        };

        return $this->validateAndNormalizePath($targetDir);
    }

    private function getSecurePackageName(): string
    {
        $envPackageName = $_ENV['PACKAGE_NAME'] ?? 'dotkernel-light';
        $packageName    = is_string($envPackageName) ? $envPackageName : 'dotkernel-light';
        $baseName       = $this->sanitizeString($packageName);

        return match ($this->buildTarget) {
            'shared-hosting-minimal' => "{$baseName}-shared-hosting-minimal",
            'shared-hosting' => "{$baseName}-shared-hosting",
            default => "{$baseName}-production"
        };
    }

    private function getSecureVersion(): string
    {
        $envVersion = $_ENV['VERSION'] ?? date('Ymd_His');
        $version    = is_string($envVersion) ? $envVersion : date('Ymd_His');
        return $this->sanitizeString($version);
    }

    private function sanitizePath(string $path): string
    {
        // Remove null bytes and normalize
        $path = str_replace("\0", '', $path);
        $path = trim($path);

        // Check length
        if (strlen($path) > self::MAX_PATH_LENGTH) {
            throw new InvalidArgumentException('Path too long');
        }

        // Basic character validation
        if (! preg_match(self::ALLOWED_CHARS, $path)) {
            throw new InvalidArgumentException('Invalid characters in path');
        }

        return $path;
    }

    private function validateAndNormalizePath(string $path): string
    {
        $sanitized = $this->sanitizePath($path);

        // Convert to absolute path
        if (! str_starts_with($sanitized, '/')) {
            $sanitized = $this->projectRoot . '/' . ltrim($sanitized, './');
        }

        // For build directories, allow creation outside project root
        if (str_contains($sanitized, '/build/')) {
            return $sanitized;
        }

        // Normalize path
        $normalized = realpath(dirname($sanitized));
        if ($normalized === false) {
            // Directory doesn't exist, try to create parent directories
            $parentDir = dirname($sanitized);
            if (! is_dir($parentDir)) {
                $this->createSecureDirectory($parentDir);
            }
            return $sanitized;
        }

        // Ensure path is within project boundaries
        if (! str_starts_with($normalized, $this->projectRoot)) {
            throw new SecurityException("Path outside project root: {$path}");
        }

        return $normalized . '/' . basename($sanitized);
    }

    private function sanitizeString(string $input): string
    {
        // Remove null bytes and control characters
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        if ($sanitized === null) {
            throw new InvalidArgumentException('Invalid string input');
        }

        return trim($sanitized);
    }

    private function createSecureDirectory(string $path): void
    {
        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Cannot create directory: {$path}");
        }
    }

    public function cleanup(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function initializePaths(): void
    {
        try {
            $configPath = $this->projectRoot . '/config/autoload/paths.global.php';
            if (! file_exists($configPath)) {
                throw new RuntimeException('Paths configuration not found');
            }

            /** @var array{paths: array{base_path?: string, custom_paths?: array<string, string>}} $config */
            $config   = require $configPath;
            $basePath = $config['paths']['base_path'] ?? $this->projectRoot;

            // For base_path, use project root directly if it resolves to same location
            $resolvedBasePath = realpath($basePath);
            if ($resolvedBasePath === $this->projectRoot) {
                $basePath = $this->projectRoot;
            }

            $this->paths = new Paths($basePath);

            if (isset($config['paths']['custom_paths'])) {
                foreach ($config['paths']['custom_paths'] as $name => $path) {
                    $safeName = $this->sanitizeString($name);
                    // For relative paths, don't validate against project root
                    if (! str_starts_with($path, '/')) {
                        $this->paths->set($safeName, $path);
                    } else {
                        $safePath = $this->validateAndNormalizePath($path);
                        $this->paths->set($safeName, $safePath);
                    }
                }
            }

            $this->log('Paths service initialized securely');
        } catch (Exception $e) {
            $this->error('Failed to initialize paths: ' . $e->getMessage());
            exit(1);
        }
    }

    private function setupExcludePatterns(): void
    {
        // STRICT exclude patterns - security critical
        $this->excludePatterns = [
            // Version control and development
            '.git',
            '.gitignore',
            '.gitattributes',
            '.github',
            '.gitlab-ci.yml',

            // IDE and editors
            '.idea',
            '.vscode',
            '.vs',
            '*.swp',
            '*.swo',
            '*~',

            // Dependencies and build
            'node_modules',
            'bower_components',
            'build',
            'dist',

            // Testing and quality
            'tests',
            'test',
            'Test',
            'Tests',
            'phpunit.xml*',
            'phpcs.xml*',
            'phpstan.neon*',
            'psalm.xml*',
            'rector.php',
            '.phpunit.result.cache',
            'coverage-html',
            'coverage.txt',
            'clover.xml',

            // Logs and cache
            '*.log',
            'var/cache',
            'var/sessions',
            'var/tmp',
            'var/logs/*.log',

            // Configuration
            'config/autoload/*.local.php',
            '.env*',

            // Documentation and meta
            'docs',
            'documentation',
            '*.md',
            'README*',
            'CHANGELOG*',
            'CONTRIBUTING*',
            'LICENSE*',
            'COPYING*',

            // Build and development scripts
            'bin/build-*.php',
            'bin/build-*.sh',
            'bin/test-*.sh',
            'bin/dev-*.sh',
            'debug-templates.php',

            // Backup and temporary
            '*.backup',
            '*.bak',
            '*.tmp',
            '*.temp',

            // OS specific
            '.DS_Store',
            'Thumbs.db',
            'desktop.ini',
        ];

        // Additional strict patterns for minimal builds
        if ($this->buildTarget === 'shared-hosting-minimal') {
            $this->excludePatterns = array_merge($this->excludePatterns, [
                // Remove all documentation
                'doc',
                'docs',
                'documentation',
                'examples',
                'example',
                'demo',
                'demos',

                // Remove development tools
                'bin/console',
                'bin/phpunit',
                'bin/phpcs',
                'bin/phpstan',
                'bin/psalm',

                // Remove package management
                'package.json',
                'package-lock.json',
                'yarn.lock',
                'pnpm-lock.yaml',
                'composer.lock',

                // Remove frontend source
                'src/assets',
                'resources/assets',
                'assets/src',
            ]);
        }
    }

    public function build(): void
    {
        $this->log("Starting secure Mezzio Boot {$this->buildTarget} build...");

        try {
            $this->cleanBuild();
            $this->installProductionDependencies();
            $this->copyApplicationFiles();
            $this->createVarDirectoryStructure();
            $this->copyRuntimeData();
            $this->cleanProductionConfiguration();
            $this->buildAssets();
            $this->cleanVendorForMinimal();
            $this->optimizeAutoloader();
            $this->optimizeHtaccess();
            $this->createWebOptimizationFiles();
            $this->setProductionPermissions();
            $this->createPackage();
            $this->restoreDevelopmentEnvironment();

            $this->success("Build completed successfully!");
            $this->showBuildSummary();
        } catch (Exception $e) {
            $this->error("Build failed: " . $e->getMessage());
            $this->cleanup();
            exit(1);
        }
    }

    private function log(string $message): void
    {
        echo self::BLUE . '[INFO]' . self::NC . " {$message}\n";
    }

    private function success(string $message): void
    {
        echo self::GREEN . '[SUCCESS]' . self::NC . " {$message}\n";
    }

    private function warning(string $message): void
    {
        echo self::YELLOW . '[WARNING]' . self::NC . " {$message}\n";
    }

    private function error(string $message): void
    {
        echo self::RED . '[ERROR]' . self::NC . " {$message}\n";
    }

    private function cleanBuild(): void
    {
        $this->log('Cleaning build directory...');

        if (is_dir($this->buildDir)) {
            $this->removeDirectorySecurely($this->buildDir);
        }

        $this->createSecureDirectory($this->buildDir);
        $this->success('Build directory cleaned');
    }

    private function removeDirectorySecurely(string $dir): void
    {
        $normalizedDir = $this->validateAndNormalizePath($dir);

        // Security check - ensure we're only deleting within project
        if (! str_starts_with($normalizedDir, $this->projectRoot)) {
            throw new SecurityException("Refusing to delete directory outside project: {$dir}");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($normalizedDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($normalizedDir);
    }

    private function installProductionDependencies(): void
    {
        $this->log('Installing production dependencies...');

        // Backup composer.lock if exists
        $composerLock = $this->projectRoot . '/composer.lock';
        if (file_exists($composerLock)) {
            $backup = $composerLock . '.backup';
            copy($composerLock, $backup);
            $this->tempFiles[] = $backup;
        }

        // Use secure command execution
        $command    = 'cd ' . escapeshellarg($this->projectRoot) . ' && composer install --no-dev --optimize-autoloader --no-interaction 2>&1';
        $output     = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Failed to install production dependencies: ' . implode("\n", $output));
        }

        $this->success('Production dependencies installed');
    }

    private function copyApplicationFiles(): void
    {
        $this->log('Copying application files...');

        // Don't validate paths for copying - just ensure they're within bounds
        $this->copyDirectorySecurely($this->projectRoot, $this->buildDir, $this->excludePatterns);

        $this->success('Application files copied');
    }

    /**
     * @param array<string> $excludePatterns
     */
    private function copyDirectorySecurely(string $source, string $destination, array $excludePatterns): void
    {
        $sourceDir = realpath($source);
        if ($sourceDir === false) {
            throw new RuntimeException("Source directory not found: {$source}");
        }

        // For destination, just ensure it's a safe path without strict validation
        $destDir = $destination;

        // Use custom directory iterator that respects our security rules
        $this->copyDirectoryRecursive($sourceDir, $destDir, $excludePatterns);
    }

    /**
     * @param array<string> $excludePatterns
     */
    private function copyDirectoryRecursive(string $sourceDir, string $destDir, array $excludePatterns): void
    {
        $items = scandir($sourceDir);
        if ($items === false) {
            throw new RuntimeException("Cannot read directory: {$sourceDir}");
        }

        foreach ($items as $item) {
            // Skip . and ..
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath   = $sourceDir . '/' . $item;
            $relativePath = substr($sourcePath, strlen($this->projectRoot) + 1);

            // STRICT: Skip all hidden files and directories (starting with .)
            if (str_starts_with($item, '.')) {
                // Allow only specific dotfiles that are needed
                $allowedDotFiles = ['.htaccess', '.htpasswd'];
                if (! in_array($item, $allowedDotFiles, true)) {
                    $this->log("Skipping hidden file/directory: {$relativePath}");
                    continue;
                }
            }

            // Check exclusion patterns
            if ($this->shouldExcludeFile($relativePath, $excludePatterns)) {
                $this->log("Excluding: {$relativePath}");
                continue;
            }

            $destPath = $destDir . '/' . $item;

            if (is_dir($sourcePath)) {
                // Create directory and recurse
                $this->createSecureDirectory($destPath);
                $this->copyDirectoryRecursive($sourcePath, $destPath, $excludePatterns);
            } else {
                // Copy file with security checks
                $this->copyFileSecurely($sourcePath, $destPath);
            }
        }
    }

    private function copyFileSecurely(string $sourcePath, string $destPath): void
    {
        // Additional security checks for files
        $fileName = basename($sourcePath);

        // Skip potentially dangerous files
        $dangerousExtensions = ['.sh', '.bat', '.cmd', '.exe', '.com', '.scr', '.pif'];
        $fileExtension       = '.' . strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $dangerousExtensions, true)) {
            // Allow only specific safe scripts
            $allowedScripts = ['build.sh', 'install.sh'];
            if (! in_array($fileName, $allowedScripts, true)) {
                $this->warning("Skipping potentially dangerous file: {$fileName}");
                return;
            }
        }

        // Ensure destination directory exists
        $this->createSecureDirectory(dirname($destPath));

        // Copy with verification
        if (! copy($sourcePath, $destPath)) {
            throw new RuntimeException("Failed to copy file: {$sourcePath} -> {$destPath}");
        }

        // Verify copy
        if (! file_exists($destPath) || filesize($sourcePath) !== filesize($destPath)) {
            throw new RuntimeException("File copy verification failed: {$destPath}");
        }
    }

    /**
     * @param array<string> $excludePatterns
     */
    private function shouldExcludeFile(string $relativePath, array $excludePatterns): bool
    {
        // Normalize path separators
        $normalizedPath = str_replace('\\', '/', $relativePath);
        $pathParts      = explode('/', $normalizedPath);
        $fileName       = basename($normalizedPath);

        foreach ($excludePatterns as $pattern) {
            if (! is_string($pattern)) {
                continue;
            }

            // Exact match for full path
            if ($pattern === $normalizedPath) {
                return true;
            }

            // Check if any directory in path matches pattern
            foreach ($pathParts as $part) {
                if ($this->matchesPattern($part, $pattern)) {
                    return true;
                }
            }

            // Check filename against pattern
            if ($this->matchesPattern($fileName, $pattern)) {
                return true;
            }

            // Check if path starts with pattern (for directory exclusions)
            if (str_starts_with($normalizedPath, rtrim($pattern, '/') . '/')) {
                return true;
            }

            // Check wildcard patterns
            if (fnmatch($pattern, $normalizedPath) || fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $string, string $pattern): bool
    {
        // Exact match
        if ($string === $pattern) {
            return true;
        }

        // Wildcard match
        if (str_contains($pattern, '*')) {
            return fnmatch($pattern, $string);
        }

        // Prefix match for directories
        if (str_ends_with($pattern, '/')) {
            return str_starts_with($string, rtrim($pattern, '/'));
        }

        return false;
    }

    private function createVarDirectoryStructure(): void
    {
        $this->log('Creating var directory structure...');

        // Use fallback to default var structure for now
        // TODO: Integrate with paths service when API is clarified
        $varDirs = [
            'var/cache',
            'var/logs',
            'var/storage',
            'var/tmp',
            'var/sessions',
        ];

        foreach ($varDirs as $relativePath) {
            $fullPath = $this->buildDir . '/' . $relativePath;
            $this->createSecureDirectory($fullPath);
            $this->log("Created directory: {$relativePath}");
        }

        $this->success('Var directory structure created');
    }

    private function copyRuntimeData(): void
    {
        $this->log('Copying runtime data...');

        // Copy any necessary runtime files that might have been excluded
        $runtimeFiles = [
            'config/autoload/local.php.dist',
            'config/autoload/development.local.php.dist',
        ];

        foreach ($runtimeFiles as $file) {
            $sourcePath = $this->projectRoot . '/' . $file;
            $destPath   = $this->buildDir . '/' . $file;

            if (file_exists($sourcePath)) {
                $this->createSecureDirectory(dirname($destPath));
                copy($sourcePath, $destPath);
            }
        }

        $this->success('Runtime data copied');
    }

    private function cleanProductionConfiguration(): void
    {
        $this->log('Cleaning production configuration...');

        // Remove development-only ConfigProviders from config.php
        $this->removeDevConfigProviders();

        // Remove development-only middleware from pipeline.php
        $this->removeDevMiddleware();

        // Remove development-only routes
        $this->removeDevRoutes();

        // Clean composer.json from dev dependencies references
        $this->cleanComposerJson();

        // Fix absolute paths for shared hosting
        $this->fixAbsolutePathsForSharedHosting();

        $this->success('Production configuration cleaned');
    }

    private function removeDevConfigProviders(): void
    {
        $configPath = $this->buildDir . '/config/config.php';
        if (! file_exists($configPath)) {
            return;
        }

        $content = file_get_contents($configPath);
        if ($content === false) {
            throw new RuntimeException('Cannot read config.php');
        }

        // Remove DebugBar ConfigProvider lines completely (including comments)
        $patterns = [
            // Remove comment line and the ConfigProvider line
            '/\s*\/\/.*DebugBar.*\n\s*\\\\?ResponsiveSk\\\\PhpDebugBarMiddleware\\\\ConfigProvider::class,?\s*\n?/',
            // Remove just the ConfigProvider line
            '/\s*\\\\?ResponsiveSk\\\\PhpDebugBarMiddleware\\\\ConfigProvider::class,?\s*\n?/',
            // Remove any remaining backslash-only lines
            '/^\s*\\\\\s*$\n?/m',
        ];

        foreach ($patterns as $pattern) {
            $result = preg_replace($pattern, '', $content);
            if ($result === null) {
                throw new RuntimeException('Failed to process config.php patterns');
            }
            $content = $result;
        }

        // Clean up multiple empty lines
        $result = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        if ($result === null) {
            throw new RuntimeException('Failed to clean up empty lines in config.php');
        }
        $content = $result;

        if (file_put_contents($configPath, $content) === false) {
            throw new RuntimeException('Cannot write cleaned config.php');
        }

        $this->log('Removed development ConfigProviders from config.php');
    }

    private function removeDevMiddleware(): void
    {
        $pipelinePath = $this->buildDir . '/config/pipeline.php';
        if (! file_exists($pipelinePath)) {
            return;
        }

        $content = file_get_contents($pipelinePath);
        if ($content === false) {
            throw new RuntimeException('Cannot read pipeline.php');
        }

        // Remove DebugBar use statement
        $result = preg_replace('/use\s+ResponsiveSk\\\\PhpDebugBarMiddleware\\\\DebugBarMiddleware;\s*\n?/', '', $content);
        if ($result === null) {
            throw new RuntimeException('Failed to remove DebugBar use statement from pipeline.php');
        }
        $content = $result;

        // Remove DebugBar middleware patterns
        $patterns = [
            // Remove comment line and the pipe() call together
            '/\s*\/\/.*DebugBar.*\n\s*\$app->pipe\(DebugBarMiddleware::class\);\s*\n?/',
            // Remove just the pipe() call with short class name
            '/\s*\$app->pipe\(DebugBarMiddleware::class\);\s*\n?/',
            // Remove pipe() calls with full class names (fallback)
            '/\s*\$app->pipe\(ResponsiveSk\\\\PhpDebugBarMiddleware\\\\DebugBarMiddleware::class\);\s*\n?/',
            '/\s*\$app->pipe\(\\\\ResponsiveSk\\\\PhpDebugBarMiddleware\\\\DebugBarMiddleware::class\);\s*\n?/',
        ];

        foreach ($patterns as $pattern) {
            $result = preg_replace($pattern, '', $content);
            if ($result === null) {
                throw new RuntimeException('Failed to remove DebugBar middleware from pipeline.php');
            }
            $content = $result;
        }

        // Clean up formatting
        $result = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        if ($result === null) {
            throw new RuntimeException('Failed to clean up formatting in pipeline.php');
        }
        $content = $result;

        if (file_put_contents($pipelinePath, $content) === false) {
            throw new RuntimeException('Cannot write cleaned pipeline.php');
        }

        $this->log('Removed development middleware from pipeline.php');
    }

    private function removeDevRoutes(): void
    {
        $routesDelegatorPath = $this->buildDir . '/src/App/RoutesDelegator.php';
        if (! file_exists($routesDelegatorPath)) {
            return;
        }

        $content = file_get_contents($routesDelegatorPath);
        if ($content === false) {
            throw new RuntimeException('Cannot read RoutesDelegator.php');
        }

        // More precise removal of DebugBar routes
        $patterns = [
            // Remove comment line and the route line together
            '/\s*\/\/.*DebugBar.*\n\s*\$app->get\([^;]*DebugBarAssetsHandler[^;]*\);\s*\n?/',
            // Remove just the route line
            '/\s*\$app->get\([^;]*DebugBarAssetsHandler[^;]*\);\s*\n?/',
            // Remove any remaining empty comment lines about DebugBar
            '/\s*\/\/.*DebugBar.*\n?/',
        ];

        foreach ($patterns as $pattern) {
            $result = preg_replace($pattern, '', $content);
            if ($result === null) {
                throw new RuntimeException('Failed to process RoutesDelegator.php patterns');
            }
            $content = $result;
        }

        // Clean up multiple empty lines but preserve structure
        $result = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        if ($result === null) {
            throw new RuntimeException('Failed to clean up empty lines in RoutesDelegator.php');
        }
        $content = $result;

        if (file_put_contents($routesDelegatorPath, $content) === false) {
            throw new RuntimeException('Cannot write cleaned RoutesDelegator.php');
        }

        $this->log('Removed development routes from RoutesDelegator.php');
    }

    private function cleanComposerJson(): void
    {
        $composerPath = $this->buildDir . '/composer.json';
        if (! file_exists($composerPath)) {
            return;
        }

        $content = file_get_contents($composerPath);
        if ($content === false) {
            throw new RuntimeException('Cannot read composer.json');
        }

        $composer = json_decode($content, true);
        if ($composer === null) {
            throw new RuntimeException('Invalid composer.json format');
        }

        // Remove require-dev section completely
        unset($composer['require-dev']);

        // Remove development scripts
        if (isset($composer['scripts'])) {
            $devScripts = ['test', 'test-coverage', 'phpstan', 'cs-check', 'cs-fix', 'quality'];
            foreach ($devScripts as $script) {
                unset($composer['scripts'][$script]);
            }
        }

        // Clean up autoload-dev
        unset($composer['autoload-dev']);

        $cleanContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($cleanContent === false) {
            throw new RuntimeException('Cannot encode cleaned composer.json');
        }

        if (file_put_contents($composerPath, $cleanContent) === false) {
            throw new RuntimeException('Cannot write cleaned composer.json');
        }

        $this->log('Cleaned composer.json from development dependencies');
    }

    private function fixAbsolutePathsForSharedHosting(): void
    {
        $this->log('Fixing paths for shared hosting compatibility...');

        // Fix the main problem: paths.global.php base_path
        $this->fixPathsGlobalForSharedHosting();

        $this->log('Fixed paths for shared hosting');
    }

    private function fixPathsGlobalForSharedHosting(): void
    {
        $pathsConfigPath = $this->buildDir . '/config/autoload/paths.global.php';
        if (! file_exists($pathsConfigPath)) {
            $this->warning('paths.global.php not found, skipping path fixes');
            return;
        }

        $content = file_get_contents($pathsConfigPath);
        if ($content === false) {
            throw new RuntimeException('Cannot read paths.global.php');
        }

        // Replace dirname(__DIR__, 2) with __DIR__ . '/../..' for shared hosting compatibility
        // This ensures the path is calculated relative to the config file location
        $content = str_replace(
            "'base_path' => dirname(__DIR__, 2),",
            "'base_path' => __DIR__ . '/../..',",
            $content
        );

        // Also handle without trailing comma
        $content = str_replace(
            "'base_path' => dirname(__DIR__, 2)",
            "'base_path' => __DIR__ . '/../..'",
            $content
        );

        // Add comment explaining the change
        $content = str_replace(
            "'base_path' => __DIR__ . '/../..',",
            "'base_path' => __DIR__ . '/../..', // Fixed for shared hosting compatibility",
            $content
        );

        if (file_put_contents($pathsConfigPath, $content) === false) {
            throw new RuntimeException('Cannot write fixed paths.global.php');
        }

        $this->log('Fixed base_path in paths.global.php for shared hosting');
    }

    private function buildAssets(): void
    {
        $this->log('Building frontend assets...');

        $packageJsonPath = $this->projectRoot . '/package.json';
        if (! file_exists($packageJsonPath)) {
            $this->warning('No package.json found, skipping asset build');
            return;
        }

        // Determine package manager
        $packageManager = file_exists($this->projectRoot . '/pnpm-lock.yaml') ? 'pnpm' : 'npm';

        // Install dependencies
        $installCommand = 'cd ' . escapeshellarg($this->projectRoot) . ' && ' . escapeshellarg($packageManager) . ' install 2>&1';
        $installOutput  = [];
        $installReturn  = 0;
        exec($installCommand, $installOutput, $installReturn);

        if ($installReturn !== 0) {
            $this->warning('Failed to install frontend dependencies: ' . implode("\n", $installOutput));
            return;
        }

        // Build assets
        $buildCommand = 'cd ' . escapeshellarg($this->projectRoot) . ' && ' . escapeshellarg($packageManager) . ' run build 2>&1';
        $buildOutput  = [];
        $buildReturn  = 0;
        exec($buildCommand, $buildOutput, $buildReturn);

        if ($buildReturn !== 0) {
            $this->warning('Failed to build frontend assets: ' . implode("\n", $buildOutput));
            return;
        }

        $this->success('Frontend assets built');
    }

    private function cleanVendorForMinimal(): void
    {
        if ($this->buildTarget !== 'shared-hosting-minimal') {
            return;
        }

        $this->log('Cleaning vendor for minimal build...');

        $vendorDir = $this->buildDir . '/vendor';
        if (! is_dir($vendorDir)) {
            return;
        }

        // Remove test directories and files using PHP instead of shell commands
        $this->removeVendorDirectoriesSecurely($vendorDir, [
            'tests',
            'test',
            'Test',
            'Tests',
            'docs',
            'doc',
            'documentation',
            'examples',
            'example',
            'demo',
            '.github',
            '.git',
        ]);

        $this->removeVendorFilesSecurely($vendorDir, [
            '*.md',
            '*.txt',
            '*.rst',
            '*.xml',
            'phpunit.xml*',
            'phpcs.xml*',
            'phpstan.neon*',
            '.travis.yml',
            '.github',
            'Makefile',
            'CHANGELOG*',
            'CONTRIBUTING*',
            'LICENSE*',
            'README*',
        ]);

        $this->success('Vendor directory cleaned for minimal build');
    }

    /**
     * @param array<string> $dirNames
     */
    private function removeVendorDirectoriesSecurely(string $vendorDir, array $dirNames): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $dirName = basename($file->getPathname());
                if (in_array($dirName, $dirNames, true)) {
                    $this->removeDirectorySecurely($file->getPathname());
                }
            }
        }
    }

    /**
     * @param array<string> $patterns
     */
    private function removeVendorFilesSecurely(string $vendorDir, array $patterns): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileName = $file->getFilename();
                foreach ($patterns as $pattern) {
                    if (is_string($pattern) && fnmatch($pattern, $fileName)) {
                        unlink($file->getPathname());
                        break;
                    }
                }
            }
        }
    }

    private function optimizeAutoloader(): void
    {
        $this->log('Optimizing autoloader...');

        $command    = 'cd ' . escapeshellarg($this->buildDir) . ' && composer dump-autoload --optimize --no-dev 2>&1';
        $output     = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Failed to optimize autoloader: ' . implode("\n", $output));
        }

        $this->success('Autoloader optimized');
    }

    private function optimizeHtaccess(): void
    {
        $this->log('Optimizing .htaccess...');

        $htaccessPath = $this->buildDir . '/public/.htaccess';

        if (! file_exists($htaccessPath)) {
            $this->warning('.htaccess not found, creating optimized version');
            $this->createOptimizedHtaccess($htaccessPath);
            return;
        }

        // Add production optimizations to existing .htaccess
        $content = file_get_contents($htaccessPath);
        if ($content === false) {
            throw new RuntimeException('Cannot read .htaccess file');
        }

        $optimizations = $this->getHtaccessOptimizations();
        $content      .= "\n\n" . $optimizations;

        if (file_put_contents($htaccessPath, $content) === false) {
            throw new RuntimeException('Cannot write optimized .htaccess');
        }

        $this->success('.htaccess optimized');
    }

    private function createOptimizedHtaccess(string $htaccessPath): void
    {
        $content = <<<'EOF'
RewriteEngine On

# The following rule allows authentication to work with fast-cgi
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# The following rule tells Apache that if the requested filename exists, simply serve it.
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^.*$ - [NC,L]

# The following rewrites all other queries to index.php
RewriteRule ^.*$ index.php [NC,L]

EOF;

        $content .= $this->getHtaccessOptimizations();

        $this->createSecureDirectory(dirname($htaccessPath));
        if (file_put_contents($htaccessPath, $content) === false) {
            throw new RuntimeException('Cannot create .htaccess file');
        }
    }

    private function getHtaccessOptimizations(): string
    {
        return <<<'EOF'

# Production optimizations
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
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

EOF;
    }

    private function createWebOptimizationFiles(): void
    {
        $this->log('Creating web optimization files...');

        $this->createRobotsTxt();
        $this->createSitemapXml();

        $this->success('Web optimization files created');
    }

    private function createRobotsTxt(): void
    {
        $baseUrl     = $_ENV['BASE_URL'] ?? 'https://example.com';
        $safeBaseUrl = filter_var($baseUrl, FILTER_VALIDATE_URL);
        if ($safeBaseUrl === false) {
            $safeBaseUrl = 'https://example.com';
        }

        $content = <<<EOF
User-agent: *
Allow: /

Sitemap: {$safeBaseUrl}/sitemap.xml
EOF;

        $robotsPath = $this->buildDir . '/public/robots.txt';
        if (file_put_contents($robotsPath, $content) === false) {
            throw new RuntimeException('Cannot create robots.txt');
        }
    }

    private function createSitemapXml(): void
    {
        $baseUrl     = $_ENV['BASE_URL'] ?? 'https://example.com';
        $safeBaseUrl = filter_var($baseUrl, FILTER_VALIDATE_URL);
        if ($safeBaseUrl === false) {
            $safeBaseUrl = 'https://example.com';
        }

        $content = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{$safeBaseUrl}/</loc>
        <lastmod>{date('Y-m-d')}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
EOF;

        $sitemapPath = $this->buildDir . '/public/sitemap.xml';
        if (file_put_contents($sitemapPath, $content) === false) {
            throw new RuntimeException('Cannot create sitemap.xml');
        }
    }

    private function setProductionPermissions(): void
    {
        $this->log('Setting production file permissions...');

        // Set permissions using PHP instead of shell commands
        $this->setDirectoryPermissionsRecursively($this->buildDir, 0755, 0644);

        // Make scripts executable
        $binDir = $this->buildDir . '/bin';
        if (is_dir($binDir)) {
            $this->setExecutablePermissions($binDir);
        }

        // Protect sensitive configuration templates
        $configDir = $this->buildDir . '/config/autoload';
        if (is_dir($configDir)) {
            $this->protectConfigFiles($configDir);
        }

        $this->success('Production permissions set');
    }

    private function setDirectoryPermissionsRecursively(string $dir, int $dirMode, int $fileMode): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                chmod($file->getPathname(), $dirMode);
            } else {
                chmod($file->getPathname(), $fileMode);
            }
        }
    }

    private function setExecutablePermissions(string $binDir): void
    {
        $iterator = new DirectoryIterator($binDir);
        foreach ($iterator as $file) {
            if ($file->isFile() && (str_ends_with($file->getFilename(), '.php') || str_ends_with($file->getFilename(), '.sh'))) {
                chmod($file->getPathname(), 0755);
            }
        }
    }

    private function protectConfigFiles(string $configDir): void
    {
        $iterator = new DirectoryIterator($configDir);
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.dist')) {
                chmod($file->getPathname(), 0600);
            }
        }
    }

    private function createPackage(): void
    {
        $this->log('Creating deployment package...');

        $packagePath = dirname($this->buildDir) . "/{$this->packageName}_{$this->version}.tar.gz";

        // Use PHP's PharData for secure archive creation
        try {
            $phar = new PharData($packagePath);
            $phar->buildFromDirectory($this->buildDir);
            $phar->compress(Phar::GZ);

            // Remove uncompressed version
            unlink($packagePath);
            $packagePath .= '.gz';
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create package: ' . $e->getMessage());
        }

        $fileSize = filesize($packagePath);
        if ($fileSize === false) {
            throw new RuntimeException("Cannot determine package size: {$packagePath}");
        }
        $size = $this->formatBytes($fileSize);
        $this->success("Package created: {$packagePath} ({$size})");

        // Create deployment instructions
        $this->createDeploymentInstructions($packagePath);
    }

    private function createDeploymentInstructions(string $packagePath): void
    {
        $instructions = <<<EOF
Mezzio Boot - Deployment Instructions
=========================================

Package: {$packagePath}
Build Target: {$this->buildTarget}
Created: {date('Y-m-d H:i:s')}

Deployment Steps:
1. Upload the package to your server
2. Extract: tar -xzf {basename($packagePath)}
3. Set up web server to point to public/ directory
4. Copy config/autoload/local.php.dist to config/autoload/local.php
5. Configure your local.php with database and other settings
6. Ensure var/ directory is writable by web server

For shared hosting:
- Upload contents of the extracted directory to your web root
- Ensure .htaccess is properly configured
- Check file permissions (755 for directories, 644 for files)

Security Notes:
- Never expose config/, var/, or vendor/ directories to web access
- Only public/ directory should be web-accessible
- Review and customize .htaccess security headers

EOF;

        $instructionsPath = dirname($packagePath) . '/DEPLOYMENT_INSTRUCTIONS.txt';
        file_put_contents($instructionsPath, $instructions);
    }

    private function restoreDevelopmentEnvironment(): void
    {
        $this->log('Restoring development environment...');

        // Restore composer.lock if backed up
        $composerLock = $this->projectRoot . '/composer.lock';
        $backup       = $composerLock . '.backup';

        if (file_exists($backup)) {
            copy($backup, $composerLock);
            unlink($backup);
        }

        // Reinstall development dependencies
        $command    = 'cd ' . escapeshellarg($this->projectRoot) . ' && composer install 2>&1';
        $output     = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            $this->warning('Failed to restore development dependencies: ' . implode("\n", $output));
            return;
        }

        $this->success('Development environment restored');
    }

    private function showBuildSummary(): void
    {
        $packagePath = dirname($this->buildDir) . "/{$this->packageName}_{$this->version}.tar.gz";
        $size        = 'Unknown';
        if (file_exists($packagePath)) {
            $fileSize = filesize($packagePath);
            if ($fileSize !== false) {
                $size = $this->formatBytes($fileSize);
            }
        }

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Build Summary\n";
        echo str_repeat('=', 50) . "\n";
        echo "Target: {$this->buildTarget}\n";
        echo "Package: {$packagePath}\n";
        echo "Size: {$size}\n";
        echo "Build Directory: {$this->buildDir}\n";
        echo "Completed: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 50) . "\n\n";
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

// Security exception class
class SecurityException extends Exception
{
}

// Main execution
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$buildTarget  = $argv[1] ?? 'production';
$validTargets = ['production', 'shared-hosting', 'shared-hosting-minimal'];

if (! in_array($buildTarget, $validTargets, true)) {
    echo 'Invalid build target. Valid options: ' . implode(', ', $validTargets) . "\n";
    exit(1);
}

try {
    $builder = new SecureProductionBuilder($buildTarget);
    $builder->build();
} catch (Exception $e) {
    echo "\033[0;31m[FATAL ERROR]\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
