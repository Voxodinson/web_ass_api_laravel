<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private $imagePath = 'uploads/images/companies';

    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%$search%")
                ->orWhere('email', 'LIKE', "%$search%")
                ->orWhere('phone', 'LIKE', "%$search%")
                ->orWhere('address', 'LIKE', "%$search%")
                ->orWhere('website', 'LIKE', "%$search%");
        }

        $perPage = $request->input('per_page', 10);
        $companies = $query->paginate($perPage);

        $companies->getCollection()->transform(function ($company) {
            $company->photo_url = $company->photo ? asset($this->imagePath . '/' . $company->photo) : null;
            return $company;
        });

        return response()->json([
            'message' => 'Companies retrieved successfully.',
            'data' => $companies->items(),
            'total' => $companies->total(),
            'per_page' => $companies->perPage(),
            'current_page' => $companies->currentPage(),
            'last_page' => $companies->lastPage()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|array',
            'phone.*' => 'required|string|max:15',
            'address' => 'required|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'store_locations' => 'nullable|string', // Changed to string
        ]);

        $company = new Company([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'website' => $request->website,
            'description' => $request->description,
            'store_locations' => $request->store_locations, // Store as string
        ]);

        if ($request->hasFile('photo')) {
            $uploadPath = public_path($this->imagePath);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $photo = $request->file('photo');
            $filename = $photo->hashName();
            $photo->move($uploadPath, $filename);
            $company->photo = $filename;
        }

        $company->save();

        return response()->json($company, 201);
    }

    public function show($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->photo_url = $company->photo ? asset($this->imagePath . '/' . $company->photo) : null;

        return response()->json($company);
    }

    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:companies,email,' . $id,
            'phone' => 'sometimes|array',
            'phone.*' => 'sometimes|string|max:15',
            'address' => 'sometimes|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'store_locations' => 'nullable|string', // Changed to string
        ]);

        $company->name = $request->name ?? $company->name;
        $company->email = $request->email ?? $company->email;
        $company->phone = $request->phone ?? $company->phone;
        $company->address = $request->address ?? $company->address;
        $company->website = $request->website ?? $company->website;
        $company->description = $request->description ?? $company->description;
        $company->store_locations = $request->store_locations ?? $company->store_locations; // Store as string

        if ($request->hasFile('photo')) {
            $uploadPath = public_path($this->imagePath);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete the old photo if it exists
            if ($company->photo) {
                $oldPhotoPath = public_path($this->imagePath . '/' . $company->photo);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }

            $photo = $request->file('photo');
            $filename = $photo->hashName();
            $photo->move($uploadPath, $filename);
            $company->photo = $filename;
        }

        $company->save();

        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        // Delete the photo if it exists
        if ($company->photo) {
            $photoPath = public_path($this->imagePath . '/' . $company->photo);
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}