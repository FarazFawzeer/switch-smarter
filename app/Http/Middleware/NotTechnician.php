<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NotTechnician
{
    /**
     * Office roles allowed into the admin area. Anything else
     * (including a user with no type at all) is refused.
     */
    public const OFFICE_ROLES = [
        'Super Admin',
        'Admin',
        'Manager',
        'Engineer',
        'Supervisor',
        'Tour Assistant',
        'Staff',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->type === 'Technician') {
            return redirect()->route('technician.jobs.index');
        }

        abort_unless(
            $request->user() && in_array($request->user()->type, self::OFFICE_ROLES, true),
            403
        );

        return $next($request);
    }
}
