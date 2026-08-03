<?php

declare(strict_types=1);

use App\Http\Controllers\Public\RedirectFallbackController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/portal.php';
require __DIR__.'/verify.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/donations.php';
require __DIR__.'/monthly-giving.php';
require __DIR__.'/campaigns.php';

// cms.php's single-segment `/{slug}` CMS-page route must load after every
// other route file — Laravel takes the first match, so anything more
// specific (registered above) always wins.
require __DIR__.'/cms.php';

// Resolved only after every other route has failed to match — costs one
// query on a genuine miss. See docs/07-SEO.md §7 ("a published slug is
// frozen... the old one 301s to the new one").
Route::fallback(RedirectFallbackController::class);
