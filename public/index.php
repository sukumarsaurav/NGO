<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Locate the Laravel project root
|--------------------------------------------------------------------------
|
| On most deployments this file lives at project-root/public/index.php, one
| level below the project root ('..'). On Hostinger setups where public_html
| itself is the document root, this file may instead sit directly alongside
| a differently-nested project — so we check the standard location first,
| then a short list of fallbacks, and use whichever one actually has both
| vendor/autoload.php and bootstrap/app.php. Delete this search once the
| server layout is finalized and hardcode the single correct '..' (or
| whatever depth applies) — it exists only to survive the current
| uncertainty about exactly where composer/artisan ended up.
|
*/
$root = null;

foreach (['..', '.', '../..', '../app', '../../app', '../laravel', '../../laravel'] as $candidate) {
    $candidateRoot = __DIR__.'/'.$candidate;

    if (is_file($candidateRoot.'/vendor/autoload.php') && is_file($candidateRoot.'/bootstrap/app.php')) {
        $root = $candidateRoot;

        break;
    }
}

if ($root === null) {
    http_response_code(500);

    exit(
        "Could not locate the Laravel project root (vendor/autoload.php + bootstrap/app.php).\n".
        'index.php is at: '.__DIR__."\n".
        'Checked: '.implode(', ', ['..', '.', '../..', '../app', '../../app', '../laravel', '../../laravel'])."\n"
    );
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $root.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $root.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $root.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
