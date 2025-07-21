<?php

declare(strict_types=1);

use function assert;
use function file_exists;
use function is_array;
use function is_string;
use function printf;
use function unlink;

chdir(__DIR__ . '/../');

require 'vendor/autoload.php';

/** @var array<string, mixed> $config */
$config = include 'config/config.php';
assert(is_array($config));

if (! isset($config['config_cache_path'])) {
    echo 'No configuration cache path found' . PHP_EOL;
    exit(0);
}

assert(is_string($config['config_cache_path']));

if (! file_exists($config['config_cache_path'])) {
    printf(
        "Configured config cache file '%s' not found%s",
        $config['config_cache_path'],
        PHP_EOL
    );
    exit(0);
}

if (false === unlink($config['config_cache_path'])) {
    printf(
        "Error removing config cache file '%s'%s",
        $config['config_cache_path'],
        PHP_EOL
    );
    exit(1);
}

printf(
    "Removed configured config cache file '%s'%s",
    $config['config_cache_path'],
    PHP_EOL
);
exit(0);
