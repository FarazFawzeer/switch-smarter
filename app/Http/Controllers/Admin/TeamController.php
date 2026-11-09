<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Route as RouteModel;
use App\Models\Shift;
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
        $view = $request->get('view', 'master');

        $roleCounts = [
            'Manager'    => User::where('type', 'Manager')->count(),
            'Engineer'   => User::where('type', 'Engineer')->count(),
            'Supervisor' => User::where('type', 'Supervisor')->count(),
            'Technician' => User::where('type', 'Technician')->count(),
        ];

        // Any column filter shows the Employee Master list (the organisation tree has no columns)
        $filterKeys = ['search', 'type', 'employee_id', 'name', 'reports_to', 'route_id', 'work_category', 'unassigned'];
        if (collect($filterKeys)->contains(fn ($k) => $request->filled($k))) {
            $view = 'master';
        }

        if ($view === 'master') {
            $query = User::whereIn('type', ['Manager', 'Engineer', 'Supervisor', 'Technician'])
                ->with(['supervisor:id,name', 'engineer:id,name', 'routes', 'shift']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', 'like', '%' . $request->input('employee_id') . '%');
            }
            if ($request->filled('name')) {
                $name = $request->input('name');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$name}%")->orWhere('email', 'like', "%{$name}%"));
            }
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            if ($request->filled('work_category')) {
                $query->whereIn('type', ['Supervisor', 'Technician'])->where('work_category', $request->input('work_category'));
            }
            if ($request->filled('reports_to')) {
                $boss = $request->input('reports_to');
                $query->where(function ($q) use ($boss) {
                    $q->whereHas('supervisor', fn ($s) => $s->where('name', 'like', "%{$boss}%"))
                        ->orWhere(fn ($w) => $w->where('type', 'Supervisor')->whereHas('engineer', fn ($e) => $e->where('name', 'like', "%{$boss}%")));
                });
            }
            if ($request->filled('route_id')) {
                $query->whereHas('routes', fn ($r) => $r->where('routes.id', $request->input('route_id')));
            }
            if ($request->boolean('unassigned')) {
                $query->where('type', 'Technician')->whereNull('supervisor_id');
            }

            // Default order is the hierarchy: managers, engineers, supervisors, then technicians
            $sortable = ['employee_id', 'name', 'type'];
            $sort = in_array($request->get('sort'), $sortable, true) ? $request->get('sort') : 'type';
            $dir = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
            if ($sort === 'type') {
                $query->orderByRaw("FIELD(type, 'Manager', 'Engineer', 'Supervisor', 'Technician') {$dir}")->orderBy('name');
            } else {
                $query->orderBy($sort, $dir);
            }

            $team = $query->paginate(25)->withQueryString();
            $routeOptions = RouteModel::orderBy('route_no')->pluck('route_no', 'id')->all();

            return view('admin.team.index', compact('team', 'view', 'roleCounts', 'sort', 'dir', 'routeOptions'))
                ->with('isFiltering', false);
        }

        $isFiltering = false;

        $managers = User::where('type', 'Manager')->get();

        $engineers = User::where('type', 'Engineer')
            ->with(['routes'])
            ->with(['supervisors' => function ($q) {
                $q->with(['routes'])
                    ->with(['technicians' => function ($tq) {
                        $tq->with(['routes', 'shift']);
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

        $team->load(['supervisor', 'engineer', 'routes', 'shift']);

        $reportees = collect();
        if ($team->type === 'Engineer') {
            $reportees = $team->supervisors()->with('routes')->withCount('technicians')->get();
        } elseif ($team->type === 'Supervisor') {
            $reportees = $team->technicians()->with(['routes', 'shift'])->get();
        }

        return view('admin.team.show', compact('team', 'reportees'));
    }

    public function create()
    {
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->get();
        $routes = RouteModel::orderBy('route_no')->get();
        $shifts = Shift::orderBy('start_time')->get();

        return view('admin.team.create', compact('engineers', 'supervisors', 'routes', 'shifts'));
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

        // Shift only applies to Technicians — ignore it for other roles even if sent
        $data['shift_id'] = $request->input('type') === 'Technician' ? $request->input('shift_id') : null;
        $data['work_category'] = in_array($request->input('type'), ['Technician', 'Supervisor'], true)
            ? $request->input('work_category', 'both') : 'both';

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
        $shifts = Shift::orderBy('start_time')->get();

        return view('admin.team.edit', compact('team', 'engineers', 'supervisors', 'routes', 'shifts'));
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

        // Shift only applies to Technicians — clear it if the role was changed away from Technician
        $data['shift_id'] = $request->input('type') === 'Technician' ? $request->input('shift_id') : null;
        $data['work_category'] = in_array($request->input('type'), ['Technician', 'Supervisor'], true)
            ? $request->input('work_category', 'both') : 'both';

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
            'shift_id'      => [
                'nullable',
                'required_if:type,Technician',
                Rule::exists('shifts', 'id'),
            ],
            'work_category' => ['nullable', Rule::in(array_keys(User::WORK_CATEGORIES))],
            'image_path'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'routes'        => ['nullable', 'array'],
            'routes.*'      => ['integer', Rule::exists('routes', 'id')],
        ];

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
            'shift_id.required_if'       => 'Please select a shift for this technician.',
            'shift_id.exists'            => 'The selected shift is invalid.',
            'image_path.max'             => 'Profile image must not exceed 2MB.',
            'routes.*.exists'            => 'One or more selected routes are invalid.',
        ]);
    }
}