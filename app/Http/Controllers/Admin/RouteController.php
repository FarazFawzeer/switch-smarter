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