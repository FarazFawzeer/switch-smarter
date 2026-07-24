<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Route as RouteModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->get('view', 'master'); // 'organization' or 'master'

        $roleCounts = [
            'Manager'    => User::where('type', 'Manager')->count(),
            'Engineer'   => User::where('type', 'Engineer')->count(),
            'Supervisor' => User::where('type', 'Supervisor')->count(),
            'Technician' => User::where('type', 'Technician')->count(),
        ];

        if ($view === 'master') {
            $query = User::whereIn('type', ['Manager', 'Engineer', 'Supervisor', 'Technician'])
                ->with(['supervisor:id,name', 'engineer:id,name', 'routes']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            $sortable = ['employee_id', 'name', 'type'];
            $sort = $request->get('sort', 'employee_id');
            $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy(in_array($sort, $sortable) ? $sort : 'employee_id', $dir);

            $team = $query->paginate(20)->withQueryString();

            return view('admin.team.index', compact('team', 'view', 'roleCounts', 'sort', 'dir'));
        }

        // ===== Organization view (unchanged logic) =====
        $isFiltering = $request->filled('search') || $request->filled('type');

        if ($isFiltering) {
            $query = User::whereIn('type', ['Manager', 'Engineer', 'Supervisor', 'Technician'])
                ->with(['supervisor:id,name', 'engineer:id,name', 'routes']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            $team = $query->latest()->paginate(15)->withQueryString();

            return view('admin.team.index', compact('team', 'view', 'roleCounts'))->with('isFiltering', true);
        }

        $managers = User::where('type', 'Manager')->get();

        $engineers = User::where('type', 'Engineer')
            ->with(['routes'])
            ->with(['supervisors' => function ($q) {
                $q->with(['routes'])
                    ->with(['technicians' => function ($tq) {
                        $tq->with(['routes']);
                    }])
                    ->withCount('technicians');
            }])
            ->withCount('supervisors')
            ->get();

        $unassignedTechnicianCount = User::where('type', 'Technician')->whereNull('supervisor_id')->count();

        return view('admin.team.index', compact('managers', 'engineers', 'unassignedTechnicianCount', 'view', 'roleCounts'))
            ->with('isFiltering', false);
    }

    public function show(User $team)
    {
        abort_unless(
            in_array($team->type, ['Manager', 'Engineer', 'Supervisor', 'Technician']),
            404
        );

        $team->load(['supervisor', 'engineer', 'routes']);

        $reportees = collect();
        if ($team->type === 'Engineer') {
            $reportees = $team->supervisors()->with('routes')->withCount('technicians')->get();
        } elseif ($team->type === 'Supervisor') {
            $reportees = $team->technicians()->with('routes')->get();
        }

        return view('admin.team.show', compact('team', 'reportees'));
    }

    public function create()
    {
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->get();
        $routes = RouteModel::orderBy('route_no')->get();

        return view('admin.team.create', compact('engineers', 'supervisors', 'routes'));
    }

    public function store(Request $request)
    {
        $validator = $this->validateTeamMember($request);

        $routeIds = array_filter($request->input('routes', []));
        $newRouteNo = trim($request->input('new_route_no', ''));

        if ($newRouteNo !== '') {
            $newRoute = RouteModel::firstOrCreate(['route_no' => $newRouteNo]);
            $routeIds[] = $newRoute->id;
        }

        if ($request->input('type') === 'Technician' && count($routeIds) > 1) {
            $validator->errors()->add('routes', 'A technician can only be assigned one route.');
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'employee_id', 'email', 'contact_no', 'type', 'engineer_id', 'supervisor_id']);
        $data['password'] = Hash::make($request->password);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('team', $filename, 'public');
            $data['image_path'] = 'team/' . $filename;
        }

        $user = DB::transaction(function () use ($data, $routeIds) {
            $user = User::create($data);

            if (! empty($routeIds)) {
                $user->routes()->sync($routeIds);
            }

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'Team member created successfully!',
            'user'    => $user,
        ]);
    }

    public function edit(User $team)
    {
        abort_unless(
            in_array($team->type, ['Manager', 'Engineer', 'Supervisor', 'Technician']),
            404
        );

        $team->load('routes');
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->get();
        $routes = RouteModel::orderBy('route_no')->get();

        return view('admin.team.edit', compact('team', 'engineers', 'supervisors', 'routes'));
    }

    public function update(Request $request, User $team)
    {
        $validator = $this->validateTeamMember($request, $team->id);

        $routeIds = array_filter($request->input('routes', []));
        $newRouteNo = trim($request->input('new_route_no', ''));

        if ($newRouteNo !== '') {
            $newRoute = RouteModel::firstOrCreate(['route_no' => $newRouteNo]);
            $routeIds[] = $newRoute->id;
        }

        if ($request->input('type') === 'Technician' && count($routeIds) > 1) {
            $validator->errors()->add('routes', 'A technician can only be assigned one route.');
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'employee_id', 'email', 'contact_no', 'type', 'engineer_id', 'supervisor_id']);

        if ($request->hasFile('image_path')) {
            if ($team->image_path) {
                Storage::disk('public')->delete($team->image_path);
            }
            $file = $request->file('image_path');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('team', $filename, 'public');
            $data['image_path'] = 'team/' . $filename;
        }

        DB::transaction(function () use ($team, $data, $routeIds) {
            $team->update($data);
            $team->routes()->sync($routeIds);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Team member updated successfully!',
            'redirect' => route('admin.team.show', $team->id),
        ]);
    }

    public function destroy(User $team)
    {
        if ($team->type === 'Engineer' && $team->supervisors()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Reassign this engineer\'s supervisors before deleting.'
            ]);
        }

        if ($team->type === 'Supervisor' && $team->technicians()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Reassign this supervisor\'s technicians before deleting.'
            ]);
        }

        if ($team->image_path) {
            Storage::disk('public')->delete($team->image_path);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team member deleted successfully!'
        ]);
    }

    /**
     * Shared validation for create and update. $ignoreUserId excludes the
     * current user's own email/employee_id from the uniqueness check on update.
     */
    private function validateTeamMember(Request $request, ?int $ignoreUserId = null)
    {
        $isUpdate = $ignoreUserId !== null;

        $rules = [
            'name'          => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.]+$/u'],
            'employee_id'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'employee_id')->ignore($ignoreUserId),
            ],
            'contact_no'    => ['nullable', 'string', 'max:20'],
            'email'         => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'type'          => ['required', 'string', Rule::in(['Manager', 'Engineer', 'Supervisor', 'Technician'])],
            'engineer_id'   => [
                'nullable',
                'required_if:type,Supervisor',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('type', 'Engineer')),
            ],
            'supervisor_id' => [
                'nullable',
                'required_if:type,Technician',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('type', 'Supervisor')),
            ],
            'image_path'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'routes'        => ['nullable', 'array'],
            'routes.*'      => ['integer', Rule::exists('routes', 'id')],
        ];

        // Password is only required (and validated) when creating a new team member
        if (! $isUpdate) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'];
        }

        return Validator::make($request->all(), $rules, [
            'name.regex'                 => 'Name may only contain letters, spaces, hyphens, and periods.',
            'employee_id.required'       => 'Employee ID is required.',
            'employee_id.unique'         => 'This Employee ID is already in use.',
            'password.regex'             => 'Password must include at least one uppercase letter, one lowercase letter, and one number.',
            'engineer_id.required_if'    => 'Please select the engineer this supervisor reports to.',
            'supervisor_id.required_if'  => 'Please select the supervisor this technician reports to.',
            'engineer_id.exists'         => 'The selected engineer is invalid.',
            'supervisor_id.exists'       => 'The selected supervisor is invalid.',
            'image_path.max'             => 'Profile image must not exceed 2MB.',
            'routes.*.exists'            => 'One or more selected routes are invalid.',
        ]);
    }
}
