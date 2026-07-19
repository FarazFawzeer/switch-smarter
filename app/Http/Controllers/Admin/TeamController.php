<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $isFiltering = $request->filled('search') || $request->filled('type');

        // Role counts — shown as a quick stats strip regardless of view mode
        $roleCounts = [
            'Manager'    => User::where('type', 'Manager')->count(),
            'Engineer'   => User::where('type', 'Engineer')->count(),
            'Supervisor' => User::where('type', 'Supervisor')->count(),
            'Technician' => User::where('type', 'Technician')->count(),
        ];

        if ($isFiltering) {
            $query = User::whereIn('type', ['Manager', 'Engineer', 'Supervisor', 'Technician'])
                ->with(['supervisor:id,name', 'engineer:id,name']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            $team = $query->latest()->paginate(15)->withQueryString();

            return view('admin.team.index', compact('team', 'isFiltering', 'roleCounts'));
        }

        $managers = User::where('type', 'Manager')->get();

        $engineers = User::where('type', 'Engineer')
            ->with(['supervisors' => function ($q) {
                $q->withCount('technicians');
            }])
            ->withCount('supervisors')
            ->get();

        $unassignedTechnicianCount = User::where('type', 'Technician')->whereNull('supervisor_id')->count();

        return view('admin.team.index', compact('managers', 'engineers', 'unassignedTechnicianCount', 'isFiltering', 'roleCounts'));
    }

    public function show(User $team)
    {
        abort_unless(
            in_array($team->type, ['Manager', 'Engineer', 'Supervisor', 'Technician']),
            404
        );

        $team->load(['supervisor', 'engineer']);

        // Load who reports to this person, depending on their role
        $reportees = collect();
        if ($team->type === 'Engineer') {
            $reportees = $team->supervisors()->withCount('technicians')->get();
        } elseif ($team->type === 'Supervisor') {
            $reportees = $team->technicians()->get();
        }

        return view('admin.team.show', compact('team', 'reportees'));
    }

    public function create()
    {
        $engineers = User::where('type', 'Engineer')->select('id', 'name')->get();
        $supervisors = User::where('type', 'Supervisor')->select('id', 'name')->get();

        return view('admin.team.create', compact('engineers', 'supervisors'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.]+$/u'],
            'email'         => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password'      => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
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
        ], [
            'name.regex'                 => 'Name may only contain letters, spaces, hyphens, and periods.',
            'password.regex'             => 'Password must include at least one uppercase letter, one lowercase letter, and one number.',
            'engineer_id.required_if'    => 'Please select the engineer this supervisor reports to.',
            'supervisor_id.required_if'  => 'Please select the supervisor this technician reports to.',
            'engineer_id.exists'         => 'The selected engineer is invalid.',
            'supervisor_id.exists'       => 'The selected supervisor is invalid.',
            'image_path.max'             => 'Profile image must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'type', 'engineer_id', 'supervisor_id']);
        $data['password'] = Hash::make($request->password);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('team', $filename, 'public');
            $data['image_path'] = 'team/' . $filename;
        }

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Team member created successfully!',
            'user'    => $user,
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
            \Storage::disk('public')->delete($team->image_path);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team member deleted successfully!'
        ]);
    }
}
