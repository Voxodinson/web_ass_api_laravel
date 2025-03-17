<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;  // For transaction handling

class OrderController extends Controller
{
    /**
     * Store a new order.
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.color' => 'required|string',
            'items.*.size' => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_zip' => 'required|string|max:10',
            'shipping_country' => 'required|string',
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'total_amount' => 'nullable|numeric',
        ]);

        DB::beginTransaction();

        try {
            // Create the order
            $order = Order::create([
                'user_id' => $request->user_id,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'transaction_id' => $request->transaction_id,
                'total_amount' => $request->total_amount ?? 0,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_zip' => $request->shipping_zip,
                'shipping_country' => $request->shipping_country,
            ]);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                // Check if product exists
                $product = Product::find($item['product_id']);
                if (!$product) {
                    return response()->json(['message' => 'Product not found'], 404);
                }

                $total = $product->price * $item['quantity'];

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $total,
                    'color' => $item['color'],
                    'size' => $item['size'],
                    'name' => $product->name,
                ]);

                $totalAmount += $total;
            }

            // Update the total amount of the order
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();  // Rollback transaction in case of error
            return response()->json(['error' => 'Failed to create order', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show an order by ID.
     */
    public function show($id)
    {
        $order = Order::with('orderItems.product')->find($id);  // Eager load the products with the order items

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * List all orders.
     */
    public function index(Request $request)
    {
        $orders = Order::with('orderItems.product')->paginate($request->get('per_page', 10));  // Eager load the products

        return response()->json($orders);
    }

    /**
     * Update an order.
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $request->validate([
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_zip' => 'nullable|string|max:10',
            'shipping_country' => 'nullable|string',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update order
        $order->update($request->only([
            'payment_method',
            'payment_status',
            'transaction_id',
            'shipping_address',
            'shipping_city',
            'shipping_zip',
            'shipping_country',
        ]));

        return response()->json($order);
    }

    /**
     * Delete an order.
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Delete associated order items
        $order->orderItems()->delete();
        // Delete the order
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }
}
