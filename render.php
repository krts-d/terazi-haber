<?php
declare(strict_types=1);

/**
 * Renders one page template from public/ to standard output. build.php runs
 * this once per page, each in its own process, so pages can't affect each other:
 *
 *   php render.php news.php '{"side":"gov","page":2}'
 */

if (PHP_SAPI !== 'cli' || !isset($argv[1])) {
    exit(1);
}
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$template = basename($argv[1]);
$_GET = json_decode($argv[2] ?? '{}', true) ?: [];
chdir(__DIR__ . '/public');
require __DIR__ . '/public/' . $template;
