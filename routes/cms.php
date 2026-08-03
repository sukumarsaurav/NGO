<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * See docs/modules/M10-public-site-cms.md's "Site map".
 */
Route::get('/', [HomeController::class, 'show'])->name('home');

Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PostController::class, 'show'])->name('blog.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:3,60')
    ->name('contact.store');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-causes.xml', [SitemapController::class, 'causes'])->name('sitemap.causes');
Route::get('/sitemap-campaigns.xml', [SitemapController::class, 'campaigns'])->name('sitemap.campaigns');
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog'])->name('sitemap.blog');

/*
 * CMS pages (About, Privacy Policy, Terms, or any ad-hoc page) — a generic
 * single-segment slug route, placed last among named routes so it only
 * catches what nothing more specific already claimed. Reserved slugs are
 * blocked at Filament validation, not here — see PageResource.
 */
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
