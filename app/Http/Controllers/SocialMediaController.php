<?php

namespace App\Http\Controllers;

use App\Models\SocialMedia;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    private $imagePath = 'uploads/images/social_media';

    public function index()
    {
        $socialMedias = SocialMedia::paginate(10);

        $socialMedias->getCollection()->transform(function ($socialMedia) {
            if ($socialMedia->photo) {
                $socialMedia->image = asset($this->imagePath . '/' . $socialMedia->photo);
            } else {
                $socialMedia->image = null;
            }
            return $socialMedia;
        });

        return response()->json([
            'message' => 'Social Media platforms retrieved successfully.',
            'data' => $socialMedias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'required|url',
        ]);

        $socialMedia = new SocialMedia([
            'name' => $request->name,
            'link_url' => $request->link_url,
        ]);

        if ($request->hasFile('photo')) {
            $uploadPath = public_path($this->imagePath);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $photo = $request->file('photo');
            $filename = $photo->hashName();
            $photo->move($uploadPath, $filename);
            $socialMedia->photo = $filename;
        }

        $socialMedia->save();

        return response()->json([
            'message' => 'Social Media platform created successfully.',
            'data' => $socialMedia
        ], 201);
    }

    public function show($id)
    {
        $socialMedia = SocialMedia::findOrFail($id);

        if ($socialMedia->photo) {
            $socialMedia->photo_url = asset($this->imagePath . '/' . $socialMedia->photo);
        } else {
            $socialMedia->photo_url = null;
        }

        return response()->json([
            'message' => 'Social Media platform retrieved successfully.',
            'data' => $socialMedia
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'required|url',
        ]);

        $socialMedia = SocialMedia::findOrFail($id);
        $socialMedia->name = $request->name;
        $socialMedia->link_url = $request->link_url;

        if ($request->hasFile('photo')) {
            $uploadPath = public_path($this->imagePath);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete the old photo if it exists
            if ($socialMedia->photo) {
                $oldPhotoPath = public_path($this->imagePath . '/' . $socialMedia->photo);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }

            $photo = $request->file('photo');
            $filename = $photo->hashName();
            $photo->move($uploadPath, $filename);
            $socialMedia->photo = $filename;
        }

        $socialMedia->save();

        return response()->json([
            'message' => 'Social Media platform updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $socialMedia = SocialMedia::findOrFail($id);

        // Delete the photo if it exists
        if ($socialMedia->photo) {
            $photoPath = public_path($this->imagePath . '/' . $socialMedia->photo);
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $socialMedia->delete();

        return response()->json([
            'message' => 'Social Media platform deleted successfully.'
        ]);
    }
}