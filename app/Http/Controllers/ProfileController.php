<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user(); // logged-in user
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'image_path' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Handle profile image upload
        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time() . '_' . Str::slug($user->name) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $filename);
            $user->image_path = 'uploads/users/' . $filename;
        }

        $user->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated successfully!');
    }


   
}
