<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * See docs/07-SEO.md §2: disallows /portal/, /admin/, /manager/, /verify/,
 * and the donation outcome pages; points at the sitemap index.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /portal/',
            'Disallow: /admin/',
            'Disallow: /manager/',
            'Disallow: /verify/',
            'Disallow: /donate/success',
            'Disallow: /donate/failed',
            'Disallow: /donate/pending',
            '',
            'Sitemap: '.route('sitemap.index'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
