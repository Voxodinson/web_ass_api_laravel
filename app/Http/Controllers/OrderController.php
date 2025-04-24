<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $orders = Order::with([
            'orderItems.product:id,name,images',
            'user:id,name,email'
        ])->paginate($perPage);

        $ordersData = $orders->map(function ($order) {
            return array_merge($order->toArray(), [
                'user_name' => $order->user?->name,
                'user_email' => $order->user?->email,
                'order_items' => $order->orderItems->map(function ($item) {
                    $product = $item->product;
                    return array_merge($item->toArray(), [
                        'product_name' => $product ? $product->name : 'N/A',
                        'image' => $product && is_array(json_decode($product->images, true)) && count(json_decode($product->images, true)) > 0
                            ? asset('uploads/images/products/' . json_decode($product->images, true)[0])
                            : null,
                    ]);
                }),
            ]);
        });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'data' => $ordersData,
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'transaction_id' => 'required|string',
            'total_amount' => 'required|numeric',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_zip' => 'required|string',
            'shipping_country' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.color' => 'nullable|string|max:255',
            'items.*.size' => 'nullable|string|max:255',
        ]);

        $order = Order::create([
            'user_id' => $request->user_id,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'transaction_id' => $request->transaction_id,
            'total_amount' => $request->total_amount,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_zip' => $request->shipping_zip,
            'shipping_country' => $request->shipping_country,
        ]);

        foreach ($request->items as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'color' => $item['color'] ?? null,
                'size' => $item['size'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }
    
    public function getByUser($userId, Request $request)
    {
        $perPage = $request->get('per_page', 10);
    
        $orders = Order::where('user_id', $userId)
            ->with([
                'orderItems.product:id,name,images',
                'user:id,name,email'
            ])
            ->paginate($perPage);
    
        $ordersData = $orders->map(function ($order) {
            return array_merge($order->toArray(), [
                'user_name' => $order->user?->name,
                'user_email' => $order->user?->email,
                'order_items' => $order->orderItems->map(function ($item) {
                    $product = $item->product;
                    return array_merge($item->toArray(), [
                        'product_name' => $product ? $product->name : 'N/A',
                        'image' => $product && is_array(json_decode($product->images, true)) && count(json_decode($product->images, true)) > 0
                            ? asset('uploads/images/products/' . json_decode($product->images, true)[0])
                            : null,
                    ]);
                }),
            ]);
        });
    
        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'data' => $ordersData,
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }
    
    public function show($id)
    {
        $order = Order::with(['orderItems.product:id,name,images', 'user:id,name,email'])->findOrFail($id);

        $orderData = array_merge($order->toArray(), [
            'user_name' => $order->user?->name,
            'user_email' => $order->user?->email,
            'order_items' => $order->orderItems->map(function ($item) {
                $product = $item->product;
                return array_merge($item->toArray(), [
                    'product_name' => $product ? $product->name : 'N/A',
                    'image' => $product && is_array(json_decode($product->images, true)) && count(json_decode($product->images, true)) > 0
                        ? asset('uploads/images/products/' . json_decode($product->images, true)[0])
                        : null,
                ]);
            }),
        ]);
        unset($orderData['user']);

        return response()->json($orderData);
    }


    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'payment_method' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'transaction_id' => 'sometimes|string',
            'total_amount' => 'sometimes|numeric',
            'shipping_address' => 'sometimes|string',
            'shipping_city' => 'sometimes|string',
            'shipping_zip' => 'sometimes|string',
            'shipping_country' => 'sometimes|string',
        ]);
        $order->update($request->only([
            'payment_method',
            'payment_status',
            'transaction_id',
            'total_amount',
            'shipping_address',
            'shipping_city',
            'shipping_zip',
            'shipping_country',
        ]));

        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }
}