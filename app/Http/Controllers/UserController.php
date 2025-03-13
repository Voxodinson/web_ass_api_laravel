<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Register User
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile image upload
        $profileUrl = null;
        if ($request->hasFile('profile')) {
            $profilePath = $request->file('profile')->store('public/profiles');
            $profileUrl = Storage::url($profilePath);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'profile' => $profileUrl,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'status' => 'success',
            'data' => $user,
        ], 201);
    }

    // Login User and Generate Token
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('authToken')->plainTextToken;
        $expiresAt = now()->addHours(5);

        return response()->json([
            'message' => 'Login successful',
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
                'expires_at' => $expiresAt
            ]
        ]);
    }

    // Logout User

    public function logout(Request $request)
{
    // Ensure the user is authenticated
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    // Revoke all tokens for the authenticated user
    $user->tokens->each(function ($token) {
        $token->delete();
    });

    return response()->json(['message' => 'Logged out successfully'], 200);
}

    

    // Update User
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:users,name,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'profile' => 'nullable',
        ]);

        if ($request->has('name')) {
            $user->name = $validated['name'];
        }

        if ($request->has('email')) {
            $user->email = $validated['email'];
        }

        if ($request->has('password')) {
            $user->password = bcrypt($validated['password']);
        }

        if ($request->hasFile('profile')) {
            // Delete old profile image if exists
            if ($user->profile) {
                $oldImagePath = str_replace('/storage/', 'public/', $user->profile);
                Storage::delete($oldImagePath);
            }

            // Upload new profile image
            $profilePath = $request->file('profile')->store('public/profiles');
            $user->profile = Storage::url($profilePath);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'status' => 'success',
            'data' => $user,
        ]);
    }

    // Get All Users (Paginated)
    public function index()
    {
        $users = User::paginate(10);

        return response()->json([
            'message' => 'Users retrieved successfully',
            'status' => 'success',
            'data' => $users->items(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
        ]);
    }

    // Get a Single User
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'message' => 'User retrieved successfully',
            'status' => 'success',
            'data' => $user,
        ]);
    }

    // Delete User
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile) {
            Storage::delete(str_replace('/storage/', 'public/', $user->profile));
        }
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'status' => 'success',
        ]);
    }
}
