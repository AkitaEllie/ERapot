<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

/**
 * Reports this application's configured database connection and whether it is
 * reachable, without exposing the password.
 *
 * Called by install.sh and install.bat. It reads Laravel's own config rather
 * than parsing .env, because .env may contain comments and quoting that a naive
 * parser gets wrong.
 *
 * Usage:  php scripts/check-database.php
 * Exit:   0 reachable, 1 unreachable.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.default');
$config = config("database.connections.{$connection}");

$database = $config['database'] ?? '(belum diatur)';
$host = $config['host'] ?? '127.0.0.1';
$port = $config['port'] ?? '3306';
$username = $config['username'] ?? 'root';

try {
    DB::connection($connection)->getPdo();
    fwrite(STDOUT, "ok\n");
    fwrite(STDOUT, "target={$username}@{$host}:{$port}/{$database}\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "unreachable\n");
    fwrite(STDERR, "target={$username}@{$host}:{$port}/{$database}\n");
    exit(1);
}
