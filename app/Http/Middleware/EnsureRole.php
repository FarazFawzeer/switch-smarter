<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:Super Admin,Admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless(
            $request->user() && in_array($request->user()->type, $roles, true),
            403
        );

        return $next($request);
    }
}
