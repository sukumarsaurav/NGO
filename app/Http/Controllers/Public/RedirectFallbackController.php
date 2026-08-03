<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The last-resort route — only reached once every other route has failed to
 * match, so this costs one indexed query on a genuine 404 and nothing on a
 * hit. See docs/07-SEO.md §7: a published campaign's slug is frozen, and
 * changing it writes a row here rather than breaking the old link.
 */
class RedirectFallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $redirect = Redirect::query()->where('from_path', '/'.ltrim($request->path(), '/'))->first();

        if (! $redirect) {
            throw new NotFoundHttpException;
        }

        $redirect->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

        return redirect($redirect->to_path, $redirect->status_code === 301 ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
    }
}
