<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'type', 'updated_at', 'image_path')
            ->whereIn('type', ['Super Admin', 'Admin', 'Tour Assistant', 'Staff'])
            ->latest()
            ->paginate(10);

        return view('admin.users', compact('users'));
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.]+$/u'],
            'email'      => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password'   => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
            'type'       => ['required', 'string', Rule::in(['Super Admin', 'Admin', 'Tour Assistant', 'Staff'])],
            'image_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.regex'     => 'Name may only contain letters, spaces, hyphens, and periods.',
            'password.regex' => 'Password must include at least one uppercase letter, one lowercase letter, and one number.',
            'image_path.max' => 'Profile image must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'type']);
        $data['password'] = Hash::make($request->password);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('users', $filename, 'public');
            $data['image_path'] = 'users/' . $filename;
        }

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully!',
            'user'    => $user,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->type === 'Super Admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete a super admin.'
            ]);
        }

        if ($user->image_path) {
            \Storage::disk('public')->delete($user->image_path);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!'
        ]);
    }
}