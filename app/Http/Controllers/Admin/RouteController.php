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
        $routes = RouteModel::withCount('contracts')
            ->with(['users' => fn ($q) => $q->select('users.id', 'users.name', 'users.type')])
            ->orderBy('route_no')
            ->get()
            ->map(fn ($route) => [
                'id'              => $route->id,
                'route_no'        => $route->route_no,
                'description'     => $route->description,
                'contracts_count' => $route->contracts_count,
                'people'          => $route->users->map(fn ($u) => $u->name . ' (' . $u->type . ')')->implode(', '),
            ]);

        return response()->json(['routes' => $routes]);
    }

    /**
     * Delete a route that was added by mistake. Refused while any project is linked to it,
     * so no contract ever loses its route silently.
     */
    public function destroy(RouteModel $route)
    {
        $linked = $route->contracts()->count();

        if ($linked > 0) {
            return response()->json([
                'success' => false,
                'message' => "Route {$route->route_no} is linked to {$linked} project(s) and cannot be deleted. Move those projects to another route first.",
            ], 422);
        }

        // team assignments (route_user) are removed by the database cascade
        $route->delete();

        return response()->json([
            'success' => true,
            'message' => "Route {$route->route_no} deleted.",
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