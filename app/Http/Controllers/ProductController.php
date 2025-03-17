<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Helper function to generate image URLs
    private function generateImageUrls($imageFilenames)
    {
        return collect($imageFilenames)->map(function($filename) {
            return asset("storage/products/$filename");
        });
    }

    public function index(Request $request)
    {
        $query = Product::query();

        // Search logic
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%$search%")
                  ->orWhere('category', 'LIKE', "%$search%")
                  ->orWhere('brand', 'LIKE', "%$search%");
        }

        $perPage = $request->input('per_page', 10);
        $products = $query->paginate($perPage);

        // Add image URLs to each product
        $products->getCollection()->transform(function ($product) {
            $product->image_urls = $this->generateImageUrls(json_decode($product->images, true));
            return $product;
        });

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Decode and generate image URLs
        $product->images = json_decode($product->images, true);
        $product->image_urls = $this->generateImageUrls($product->images);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        // Validation
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
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Create product
        $product = Product::create($request->except('images'));

        // Store images and create image filenames
        $imageFilenames = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = $image->hashName();
                $image->storeAs('products', $filename, 'public');
                $imageFilenames[] = $filename;
            }
        }

        // Store image filenames as JSON in the product's images field
        $product->update(['images' => json_encode($imageFilenames)]);

        // Return the product with image URLs
        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'image_urls' => $this->generateImageUrls($imageFilenames)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Validation
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
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5'
        ]);

        // Update the product's basic info
        $product->update($request->except('images'));

        // Handle image updates if any
        if ($request->hasFile('images')) {
            // Delete old images
            $oldImages = json_decode($product->images, true);
            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete('products/' . $oldImage);
            }

            // Store new images and filenames
            $newImageFilenames = [];
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $filename = $image->hashName();
                    $image->storeAs('products', $filename, 'public');
                    $newImageFilenames[] = $filename;
                }
            }

            // Update the product's image filenames
            $product->images = json_encode($newImageFilenames);
            $product->save();
        }

        // Return the updated product with new image URLs
        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
            'image_urls' => $this->generateImageUrls(json_decode($product->images, true))
        ]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete product images from storage
        $images = json_decode($product->images, true);
        foreach ($images as $image) {
            Storage::disk('public')->delete('products/' . $image);
        }

        // Delete the product
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
