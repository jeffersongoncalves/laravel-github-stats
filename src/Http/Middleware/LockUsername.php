<?php

namespace JeffersonGoncalves\GitHubStats\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('username') &&
            $request->get('username') !== config('github-stats.username')) {
            abort(403, 'This service is private.');
        }

        return $next($request);
    }
}
