<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(): View
    {
        $partners = Partner::query()->where('is_published', true)->orderBy('sort_order')->get();

        return view('public.partners.index', [
            'partners' => $partners,
            // Only group by category once the client has actually set more
            // than one distinct category — otherwise every partner would sit
            // under a single empty-looking "Uncategorised" heading.
            'groupByCategory' => $partners->pluck('category')->filter()->unique()->count() > 1,
        ]);
    }
}
