<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('public.gallery.index', [
            'photos' => GalleryPhoto::query()->where('is_published', true)->orderBy('sort_order')->get(),
        ]);
    }
}
