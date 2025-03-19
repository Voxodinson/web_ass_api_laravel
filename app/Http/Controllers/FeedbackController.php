<?php
namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);  // Default per page value is 10
        $page = $request->get('page', 1);  // Default to the first page if not provided

        $feedbacks = Feedback::with('user:id,name,profile')->paginate($perPage);

        $feedbacks->getCollection()->transform(function ($feedback) {
            $userProfile = json_decode($feedback->user->profile, true);
            return [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'user_name' => $feedback->user->name,
                'user_photo' => $userProfile,
            ];
        });

        return response()->json([
            'message' => 'Feedbacks retrieved successfully.',
            'data' => $feedbacks->getCollection(),
            'total' => $feedbacks->total(),
            'per_page' => $feedbacks->perPage(),
            'last_page' => $feedbacks->lastPage(),
            'page' => $feedbacks->currentPage(),
        ]);
    }

    
    public function show($id)
    {
        $feedback = Feedback::with('user:id,name,profile')->findOrFail($id);
        $userProfile = json_decode($feedback->user->profile, true);

        return response()->json([
            'message' => 'Feedback retrieved successfully.',
            'data' => [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'user_name' => $feedback->user->name,
                'user_photo' => $userProfile['profile'] ?? null,
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
