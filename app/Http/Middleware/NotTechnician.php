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
        'Storekeeper',
    ];

    /** A Storekeeper only works in the Stores pages (and their own profile). */
    public const STOREKEEPER_ROUTES = ['admin.stores.*', 'admin.profile.*', 'logout'];

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->type === 'Technician') {
            return redirect()->route('technician.jobs.index');
        }

        abort_unless(
            $request->user() && in_array($request->user()->type, self::OFFICE_ROLES, true),
            403
        );

        if ($request->user()->type === 'Storekeeper' && ! $request->routeIs(...self::STOREKEEPER_ROUTES)) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->route('admin.stores.inventory.index');
        }

        return $next($request);
    }
}
