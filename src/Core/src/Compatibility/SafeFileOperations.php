<?php

declare(strict_types=1);

namespace Light\Core\Compatibility;

use Exception;

use function chmod;
use function file_put_contents;
use function is_dir;
use function mkdir;

use const LOCK_EX;

/**
 * Safe file operations for shared hosting environments.
 *
 * Provides fallbacks for file operations that might fail on shared hosting
 * due to permission restrictions or disabled functions.
 */
class SafeFileOperations
{
    /**
     * Safely create directory with fallbacks.
     *
     * @param string $path Directory path to create
     * @param int $mode Directory permissions (ignored on some shared hosts)
     * @return bool True if directory exists or was created
     */
    public static function createDirectory(string $path, int $mode = 0o755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        try {
            // Try recursive creation first
            return mkdir($path, $mode, true);
        } catch (Exception $e) {
            // Fallback: try without recursive flag
            try {
                return @mkdir($path, $mode);
            } catch (Exception $e) {
                // Last resort: try with default permissions
                return @mkdir($path);
            }
        }
    }

    /**
     * Safely write file with fallbacks.
     *
     * @param string $file File path
     * @param string $content Content to write
     * @param bool $useLock Whether to use file locking
     * @return bool True if write was successful
     */
    public static function safeWrite(string $file, string $content, bool $useLock = true): bool
    {
        try {
            $flags = $useLock ? LOCK_EX : 0;

            return file_put_contents($file, $content, $flags) !== false;
        } catch (Exception $e) {
            // Fallback: try without lock
            try {
                return @file_put_contents($file, $content) !== false;
            } catch (Exception $e) {
                return false;
            }
        }
    }

    /**
     * Safely change file permissions.
     *
     * @param string $path File or directory path
     * @param int $mode Permissions mode
     * @return bool True if successful (or if chmod is not available)
     */
    public static function safeChmod(string $path, int $mode): bool
    {
        try {
            return chmod($path, $mode);
        } catch (Exception $e) {
            // Ignore chmod errors on shared hosting - they're often not allowed
            return true;
        }
    }

    /**
     * Create var directory structure safely.
     *
     * @param string $basePath Base application path
     * @return bool True if all directories exist or were created
     */
    public static function createVarStructure(string $basePath): bool
    {
        $varDirs = [
            'var',
            'var/data',
            'var/cache',
            'var/cache/config',
            'var/cache/twig',
            'var/cache/routes',
            'var/logs',
            'var/tmp',
            'var/sessions',
        ];

        $success = true;
        foreach ($varDirs as $dir) {
            $fullPath = $basePath . '/' . $dir;
            if (! self::createDirectory($fullPath)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Create .gitignore files for var directories.
     *
     * @param string $basePath Base application path
     * @return bool True if successful
     */
    public static function createVarGitignores(string $basePath): bool
    {
        $gitignoreContent = "*\n!.gitignore\n";

        $dirs = [
            'var/cache',
            'var/logs',
            'var/tmp',
            'var/sessions',
        ];

        $success = true;
        foreach ($dirs as $dir) {
            $gitignorePath = $basePath . '/' . $dir . '/.gitignore';
            if (! self::safeWrite($gitignorePath, $gitignoreContent, false)) {
                $success = false;
            }
        }

        return $success;
    }
}
