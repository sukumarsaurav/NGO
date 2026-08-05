<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(): View
    {
        return view('public.certificates.index', [
            'certificates' => Certificate::query()->where('is_published', true)->orderBy('sort_order')->get(),
        ]);
    }
}
