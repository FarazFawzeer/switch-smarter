<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OnlyTechnician
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            $request->user() && $request->user()->type === 'Technician',
            403
        );

        return $next($request);
    }
}