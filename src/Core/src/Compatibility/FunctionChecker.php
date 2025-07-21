<?php

declare(strict_types=1);

namespace Light\Core\Compatibility;

use function exec;
use function explode;
use function function_exists;
use function implode;
use function in_array;
use function ini_get;
use function ob_get_clean;
use function ob_start;
use function shell_exec;
use function system;

/**
 * Function availability checker for shared hosting environments.
 *
 * Many shared hosting providers disable certain PHP functions for security.
 * This class provides safe fallbacks and detection.
 */
class FunctionChecker
{
    /** @var array<string> */
    private static array $disabledFunctions = [];

    private static bool $initialized = false;

    /**
     * Initialize the checker by reading disabled functions.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $disabled                = ini_get('disable_functions');
        self::$disabledFunctions = $disabled ? explode(',', $disabled) : [];
        self::$initialized       = true;
    }

    /**
     * Check if a function is available.
     *
     * @param string $function Function name to check
     * @return bool True if function is available
     */
    public static function isAvailable(string $function): bool
    {
        self::init();

        return function_exists($function) &&
               ! in_array($function, self::$disabledFunctions, true);
    }

    /**
     * Safe execution with fallbacks.
     *
     * @param string $command Command to execute
     * @return string|null Output or null if no exec functions available
     */
    public static function safeExec(string $command): ?string
    {
        if (self::isAvailable('exec')) {
            exec($command, $output);

            return implode("\n", $output);
        }

        if (self::isAvailable('shell_exec')) {
            $result = shell_exec($command);

            return $result !== false && $result !== null ? $result : null;
        }

        if (self::isAvailable('system')) {
            ob_start();
            system($command);

            return ob_get_clean() ?: '';
        }

        return null; // No exec function available
    }

    /**
     * Get list of disabled functions.
     *
     * @return array<string>
     */
    public static function getDisabledFunctions(): array
    {
        self::init();

        return self::$disabledFunctions;
    }

    /**
     * Check if any exec functions are available.
     */
    public static function hasExecCapability(): bool
    {
        return self::isAvailable('exec') ||
               self::isAvailable('shell_exec') ||
               self::isAvailable('system');
    }

    /**
     * Check if file_get_contents can access URLs.
     */
    public static function canAccessUrls(): bool
    {
        return self::isAvailable('file_get_contents') &&
               ini_get('allow_url_fopen') === '1';
    }

    /**
     * Check if cURL is available.
     */
    public static function hasCurl(): bool
    {
        return self::isAvailable('curl_exec') &&
               function_exists('curl_init');
    }
}
