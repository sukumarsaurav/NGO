<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A CMS page — About, Privacy Policy, Terms, or any ad-hoc page an admin
 * creates. See docs/modules/M10-public-site-cms.md's "CMS".
 */
class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        return view('public.pages.show', ['page' => $page]);
    }
}
