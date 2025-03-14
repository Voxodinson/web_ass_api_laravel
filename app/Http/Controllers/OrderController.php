<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
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
            $product = Product::find($item['product_id']);
            $total = $product->price * $item['quantity'];

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

        $order->update(['total_amount' => $totalAmount]);

        return response()->json($order, 201);
    }

    public function show($id)
    {
        $order = Order::with(['orderItems.product:id,name,price,color,sizes'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product:id,name,price,color,sizes'])->paginate($request->get('per_page', 10));

        return response()->json($orders);
    }

    public function update(Request $request, $id)
    {
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

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->orderItems()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully'], 200);
    }
}
