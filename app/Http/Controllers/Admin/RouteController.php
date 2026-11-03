<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route as RouteModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RouteController extends Controller
{
    /**
     * All routes with how many projects use each one (for the Manage Routes modal).
     */
    public function index()
    {
        $routes = RouteModel::query()
            ->with([
                'users' => fn ($q) => $q->select('users.id', 'users.name', 'users.type'),
                'contracts' => fn ($q) => $q->select('id', 'route_id', 'project_name')->withCount(['elevatorUnits', 'jobs']),
            ])
            ->orderBy('route_no')
            ->get()
            ->map(function ($route) {
                // "Empty" project = no units and no jobs — e.g. left behind after a wrong bulk
                // upload was cleaned up. It doesn't show in the master list (one row per unit)
                // but it still held the route, so the route looked "In use".
                $empty = $route->contracts->filter(fn ($c) => $c->elevator_units_count === 0 && $c->jobs_count === 0);

                return [
                    'id'              => $route->id,
                    'route_no'        => $route->route_no,
                    'description'     => $route->description,
                    'contracts_count' => $route->contracts->count(),
                    'empty_count'     => $empty->count(),
                    'projects'        => $route->contracts->map(fn ($c) => $c->project_name . ($c->elevator_units_count === 0 && $c->jobs_count === 0 ? ' (empty)' : ''))->implode(', '),
                    'deletable'       => $route->contracts->count() === $empty->count(),
                    'people'          => $route->users->map(fn ($u) => $u->name . ' (' . $u->type . ')')->implode(', '),
                ];
            });

        return response()->json(['routes' => $routes]);
    }

    /**
     * Delete a route that was added by mistake. Allowed when no REAL project uses it.
     * Empty projects (no units, no jobs) still pointing at it are deleted with it —
     * they are leftovers that can't be seen or used anywhere else.
     */
    public function destroy(RouteModel $route)
    {
        $contracts = $route->contracts()->withCount(['elevatorUnits', 'jobs'])->get();
        $real = $contracts->filter(fn ($c) => $c->elevator_units_count > 0 || $c->jobs_count > 0);

        if ($real->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Route {$route->route_no} is used by {$real->count()} project(s) with units ("
                    . $real->pluck('project_name')->take(3)->implode(', ') . ($real->count() > 3 ? '…' : '')
                    . '). Move those projects to another route first.',
            ], 422);
        }

        $emptyCount = $contracts->count();

        \Illuminate\Support\Facades\DB::transaction(function () use ($route, $contracts) {
            foreach ($contracts as $contract) {
                $contract->delete();
            }
            // team assignments (route_user) are removed by the database cascade
            $route->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Route {$route->route_no} deleted"
                . ($emptyCount ? " together with {$emptyCount} empty project(s) that had no units." : '.'),
        ]);
    }

    /**
     * Quick-create a route from a modal (used on Contract create/edit).
     * Optionally attaches an Engineer to it immediately.
     */
    public function quickStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_no'     => ['required', 'string', 'max:50', 'unique:routes,route_no'],
            'description'  => ['nullable', 'string', 'max:255'],
            'engineer_id'  => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('type', 'Engineer')),
            ],
        ], [
            'route_no.unique' => 'This route number already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $route = RouteModel::create([
            'route_no'    => $request->input('route_no'),
            'description' => $request->input('description'),
        ]);

        if ($request->filled('engineer_id')) {
            $route->users()->attach($request->input('engineer_id'));
        }

        $engineerName = $request->filled('engineer_id')
            ? User::find($request->input('engineer_id'))->name
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Route created successfully!',
            'route'   => [
                'id'            => $route->id,
                'route_no'      => $route->route_no,
                'description'   => $route->description,
                'engineer_name' => $engineerName,
            ],
        ]);
    }
}