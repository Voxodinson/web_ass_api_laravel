<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    //Get all products with optional search & pagination.    
    public function index(Request $request)
    {
        $query = Product::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%$search%")
                  ->orWhere('category', 'LIKE', "%$search%")
                  ->orWhere('brand', 'LIKE', "%$search%");
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    //Get a single product.
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    //Create a new product.  
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'sizes' => 'required|array',
            'color' => 'required|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'rating' => 'nullable|numeric|min:0|max:5',
            'images' => 'required|array'
        ]);

        $product = Product::create($request->all());

        return response()->json($product, 201);
    }


    //Update a product.
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'sizes' => 'sometimes|array',
            'color' => 'sometimes|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'images' => 'sometimes|array',
            'rating' => 'nullable|numeric|min:0|max:5'
        ]);

        $product->update($request->all());

        return response()->json($product);
    }

    //Delete a product.
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
