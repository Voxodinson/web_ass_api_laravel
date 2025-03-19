<?php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
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
            'store_locations' => 'nullable|json',
        ]);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('company_photos', 'public/company');
        } else {
            $photoPath = null;
        }

        $company = Company::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'website' => $request->website,
            'description' => $request->description,
            'photo' => $photoPath,
            'store_locations' => $request->store_locations ? json_decode($request->store_locations, true) : null,
        ]);

        return response()->json($company, 201);
    }

    public function show($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->photo_url = $company->photo ? Storage::url($company->photo) : null;
        $company->store_locations = json_decode($company->store_locations);

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
            'store_locations' => 'nullable|json',
        ]);

        if ($request->hasFile('photo')) {
            if ($company->photo && Storage::disk('public')->exists($company->photo)) {
                Storage::disk('public')->delete($company->photo);
            }

            $photoPath = $request->file('photo')->store('company_photos', 'public');
        } else {
            $photoPath = $company->photo;
        }

        $company->update([
            'name' => $request->name ?? $company->name,
            'email' => $request->email ?? $company->email,
            'phone' => $request->phone ?? $company->phone,
            'address' => $request->address ?? $company->address,
            'website' => $request->website ?? $company->website,
            'description' => $request->description ?? $company->description,
            'photo' => $photoPath,
            'store_locations' => $request->store_locations ? json_decode($request->store_locations, true) : $company->store_locations,
        ]);

        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        if ($company->photo && Storage::disk('public')->exists($company->photo)) {
            Storage::disk('public')->delete($company->photo);
        }

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}
