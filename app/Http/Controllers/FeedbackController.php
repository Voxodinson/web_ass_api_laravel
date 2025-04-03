<?php
namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
    
        $feedbacks = Feedback::with('user:id,name,profile')
            ->paginate($perPage);
    
        $transformedFeedbacks = $feedbacks->getCollection()->map(function ($feedback) {
            $userName = $feedback->user ? $feedback->user->name : null;
            $userProfile = $feedback->user ? json_decode($feedback->user->profile, true) : null;
            $image = null;
    
            if ($feedback->user && $feedback->user->profile) {
                // Check if profile data looks like JSON
                if (is_array(json_decode($feedback->user->profile, true))) {
                    // It's likely JSON, try to get the 'profile' key
                    $image = $userProfile['profile'] ?? $userProfile['avatar'] ?? $userProfile['avatar_url'] ?? null;
                } else {
                    // It's likely a direct string URL
                    $image = $feedback->user->profile;
                }
            } else {
                Log::warning('No user or profile data for feedback ID: ' . $feedback->id);
            }
    
            return [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'user_name' => $userName,
                'image' => $image,
            ];
        });
    
        return response()->json([
            'message' => 'Feedbacks retrieved successfully.',
            'data' => $transformedFeedbacks,
            'total' => $feedbacks->total(),
            'per_page' => $feedbacks->perPage(),
            'last_page' => $feedbacks->lastPage(),
            'page' => $feedbacks->currentPage(),
        ]);
    }


    public function show($id)
    {
        $feedback = Feedback::with('user:id,name,profile')->findOrFail($id);
        $userName = $feedback->user ? $feedback->user->name : null;
        $userProfile = $feedback->user ? json_decode($feedback->user->profile, true) : null;
        $userPhoto = $userProfile['profile'] ?? null;

        return response()->json([
            'message' => 'Feedback retrieved successfully.',
            'data' => [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'user_name' => $userName,
                'user_photo' => $userPhoto,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $user_id = Auth::id();

        $feedback = Feedback::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => $user_id,
        ]);

        return response()->json([
            'message' => 'Feedback created successfully.',
            'data' => $feedback
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $feedback = Feedback::findOrFail($id);
        $feedback->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Feedback updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return response()->json([
            'message' => 'Feedback deleted successfully.'
        ]);
    }
}