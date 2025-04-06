<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    private $imagePath = 'uploads/images/products';

    private function generateImageUrls($imageFilenames)
    {
        return collect($imageFilenames)->map(function ($filename) {
            return asset($this->imagePath . '/' . $filename);
        });
    }
    public function index(Request $request)
{
    $query = Product::query();

    if ($request->has('search')) {
        $search = $request->search;
        $query->where('name', 'LIKE', "%$search%")
            ->orWhere('category', 'LIKE', "%$search%")
            ->orWhere('brand', 'LIKE', "%$search%");
    }

    if ($request->has('product_type') && !empty($request->product_type)) {
        $productType = $request->product_type;
        $query->where('product_type', $productType);
    }

    $products = $query->get();

    $products->transform(function ($product) {
        $decodedImages = json_decode($product->images, true) ?? [];
        $product->images = $this->generateImageUrls($decodedImages);
        $product->image = isset($decodedImages[0]) ? asset('uploads/images/products/' . $decodedImages[0]) : null;
        return $product;
    });

    return response()->json([
        'message' => 'Products retrieved successfully.',
        'data' => $products,
    ]);
}

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->images = json_decode($product->images, true) ?? [];
        $product->image_urls = $this->generateImageUrls($product->images);

        return response()->json($product);
    }

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
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_type' => 'required|string|in:men,women,kids',
        ]);

        $product = Product::create($request->except('images'));

        $imageFilenames = [];
        if ($request->hasFile('images')) {
            $uploadPath = public_path($this->imagePath);
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            foreach ($request->file('images') as $image) {
                $filename = $image->hashName();
                $image->move($uploadPath, $filename);
                $imageFilenames[] = $filename;
            }
        }

        $product->update(['images' => json_encode($imageFilenames)]);

        return response()->json([
            'message' => 'Product created successfully',
        ], 201);
    }

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
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'old_images' => 'sometimes|array', // Expecting an array of image URLs to keep
            'rating' => 'nullable|numeric|min:0|max:5',
            'product_type' => 'sometimes|string|in:men,women,kids',
        ]);

        $product->update($request->except(['images', 'old_images']));

        $existingImages = json_decode($product->images, true) ?? [];
        $uploadPath = public_path($this->imagePath);
        $imagesToKeepFilenames = [];

        // Handle which existing images to keep
        if ($request->has('old_images') && is_array($request->old_images)) {
            foreach ($existingImages as $filename) {
                $imageUrl = asset($this->imagePath . '/' . $filename);
                if (in_array($imageUrl, $request->old_images)) {
                    $imagesToKeepFilenames[] = $filename;
                } else {
                    // Delete the image file if it's not in the old_images array
                    $oldImagePath = $uploadPath . '/' . $filename;
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                    }
                }
            }
        } else {
            // If no old_images are sent, we assume all existing should be removed
            foreach ($existingImages as $filename) {
                $oldImagePath = $uploadPath . '/' . $filename;
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }
        }

        // Handle new images
        $newImageFilenames = [];
        if ($request->hasFile('images')) {
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $filename = $image->hashName();
                    $image->move($uploadPath, $filename);
                    $newImageFilenames[] = $filename;
                }
            }
        }

        // Merge the images to keep with the newly uploaded images
        $allImages = array_merge($imagesToKeepFilenames, $newImageFilenames);

        $product->images = json_encode(array_unique($allImages));
        $product->save();

        return response()->json([
            'message' => 'Product updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete product images from the public directory
        $images = json_decode($product->images, true) ?? [];
        $uploadPath = public_path($this->imagePath);
        foreach ($images as $image) {
            $imagePath = $uploadPath . '/' . $image;
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }

        // Delete the product
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}