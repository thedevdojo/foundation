<?php

namespace Devdojo\Foundation\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Gate;

class ViewFoundationSetup
{
    public function handle($request, Closure $next)
    {
        if (app()->isLocal() || Gate::allows('viewFoundationSetup')) {
            return $next($request);
        }

        abort(403);
    }
}
