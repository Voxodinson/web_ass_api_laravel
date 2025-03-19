<?php

namespace App\Http\Controllers;

use App\Models\SocialMedia;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    public function index()
    {
        $socialMedias = SocialMedia::paginate(10);

        return response()->json([
            'message' => 'Social Media platforms retrieved successfully.',
            'data' => $socialMedias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|string',
            'link_url' => 'required|url',
        ]);

        $socialMedia = SocialMedia::create([
            'name' => $request->name,
            'photo' => $request->photo,
            'link_url' => $request->link_url,
        ]);

        return response()->json([
            'message' => 'Social Media platform created successfully.',
            'data' => $socialMedia
        ], 201);
    }

    public function show($id)
    {
        $socialMedia = SocialMedia::findOrFail($id);

        return response()->json([
            'message' => 'Social Media platform retrieved successfully.',
            'data' => $socialMedia
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|string',
            'link_url' => 'required|url',
        ]);

        $socialMedia = SocialMedia::findOrFail($id);
        $socialMedia->update([
            'name' => $request->name,
            'photo' => $request->photo,
            'link_url' => $request->link_url,
        ]);

        return response()->json([
            'message' => 'Social Media platform updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $socialMedia = SocialMedia::findOrFail($id);
        $socialMedia->delete();

        return response()->json([
            'message' => 'Social Media platform deleted successfully.'
        ]);
    }
}