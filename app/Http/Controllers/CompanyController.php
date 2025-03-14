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

        return response()->json($companies);
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
        ]);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('company_photos', 'public');
        } else {
            $photoPath = null;
        }

        $company = Company::create(array_merge($request->all(), ['photo' => $photoPath]));

        return response()->json($company, 201);
    }

    public function show($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        if ($company->photo) {
            $company->photo_url = Storage::url($company->photo);
        } else {
            $company->photo_url = null;
        }

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
        ]);

        if ($request->hasFile('photo')) {
            if ($company->photo && Storage::disk('public')->exists($company->photo)) {
                Storage::disk('public')->delete($company->photo);
            }

            $photoPath = $request->file('photo')->store('company_photos', 'public');
        } else {
            $photoPath = $company->photo;
        }

        $company->update(array_merge($request->all(), ['photo' => $photoPath]));

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
