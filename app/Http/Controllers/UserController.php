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
            'role' => 'required|in:admin,user',
            'address' => 'nullable|string',
            'dob' => 'nullable|date',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'address' => $validated['address'],
            'dob' => $validated['dob'],
        ]);

        // Handle profile image upload after user creation
        $profileUrl = null;
        if ($request->hasFile('profile')) {
            $uploadPath = public_path('uploads/images/users');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true); // Create directory if it doesn't exist
            }

            $profile = $request->file('profile');
            $filename = $profile->hashName();
            $profile->move($uploadPath, $filename);
            $profileUrl = asset('uploads/images/users/' . $filename);
            $user->profile = $profileUrl;
            $user->save(); // Save the profile URL to the user model
        }

        return response()->json([
            'message' => 'User created successfully',
            'status' => 'OK',
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
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

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
            'role' => 'nullable|in:admin,user',
            'address' => 'nullable|string',
            'dob' => 'nullable|date',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->fill($validated);

        // Handle profile image update
        if ($request->hasFile('profile')) {
            $uploadPath = public_path('uploads/images/users');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true); // Create directory if it doesn't exist
            }

            // Delete the old profile image if it exists
            if ($user->profile) {
                $oldProfilePath = public_path(str_replace(asset('/'), '', $user->profile));
                if (file_exists($oldProfilePath)) {
                    unlink($oldProfilePath);
                }
            }

            $profile = $request->file('profile');
            $filename = $profile->hashName();
            $profile->move($uploadPath, $filename);
            $user->profile = asset('uploads/images/users/' . $filename);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%")
                  ->orWhere('role', 'LIKE', "%$search%");
        }

        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $user->profile = $user->profile ? asset($user->profile) : null;
            return $user;
        });

        return response()->json([
            'message' => 'Users retrieved successfully',
            'status' => 'success',
            'data' => $users->items(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage()
        ]);
    }



    // Get a Single User
    public function show($id)
    {
        $user = User::findOrFail($id);
        $user->profile = $user->profile ? asset($user->profile) : null;
        return response()->json($user);
    }

    // Delete User
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile) {
            $oldProfilePath = public_path(str_replace(asset('/'), '', $user->profile));
            if (file_exists($oldProfilePath)) {
                unlink($oldProfilePath);
            }
        }
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
            'status' => 'OK',
        ]);
    }
}