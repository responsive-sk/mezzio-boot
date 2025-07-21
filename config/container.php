<?php

declare(strict_types=1);

use Laminas\ServiceManager\ServiceManager;

// Load configuration
/** @var array<string, mixed> $config */
$config = require __DIR__ . '/config.php';
assert(is_array($config));
assert(isset($config['dependencies']) && is_array($config['dependencies']));

/** @var array<string, mixed> $dependencies */
$dependencies = $config['dependencies'];
assert(is_array($dependencies));

// Ensure services array exists
if (! isset($dependencies['services'])) {
    $dependencies['services'] = [];
}
assert(is_array($dependencies['services']));

$dependencies['services']['config'] = $config;

// Build container
// @phpstan-ignore-next-line
return new ServiceManager($dependencies);
