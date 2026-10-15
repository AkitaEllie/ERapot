<?php

use Illuminate\Contracts\Console\Kernel;

/**
 * Prints one Laravel config value, for the shell scripts to read.
 *
 * They cannot parse .env themselves without tripping over its comments and
 * quoting, so they ask Laravel instead.
 *
 * Usage:  php scripts/config-value.php app.url
 * Exit:   0 on success, 1 when the key is missing.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$key = $argv[1] ?? '';

if ($key === '') {
    fwrite(STDERR, "usage: php scripts/config-value.php <key>\n");
    exit(1);
}

$value = config($key);

if ($value === null) {
    fwrite(STDERR, "config key not found: {$key}\n");
    exit(1);
}

fwrite(STDOUT, is_scalar($value) ? (string) $value : json_encode($value));
fwrite(STDOUT, "\n");
