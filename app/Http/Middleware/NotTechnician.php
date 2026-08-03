<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NotTechnician
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->type === 'Technician') {
            return redirect()->route('technician.jobs.index');
        }

        return $next($request);
    }
}