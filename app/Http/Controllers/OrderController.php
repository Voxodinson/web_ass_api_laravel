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
        $orders = Order::with('orderItems')->paginate($perPage);

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => $orders
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
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.color' => 'required|string',
            'items.*.size' => 'required|string',
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
                'color' => $item['color'],
                'size' => $item['size'],
            ]);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }

    public function show($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);

        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
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
