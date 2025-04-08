<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get all necessary dashboard data in a single overview endpoint.
     */
    public function overview(): JsonResponse
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        $newCustomers = User::where('created_at', '>=', now()->subDays(30))->count();
        $totalProductsSold = OrderItem::sum('quantity');

        $recentOrders = Order::with(['user:id,name,email', 'orderItems.product:id,name'])
            ->latest()
            ->take(5) // Reduced to 5 for the overview
            ->get(['id', 'user_id', 'total_amount', 'payment_status', 'created_at']);

        $salesOverview = Order::selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total_sales')
            ->where('created_at', '>=', now()->subDays(7)) // Reduced to last 7 days for the overview
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        $topSellingProducts = OrderItem::with('product:id,name')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(3) // Reduced to top 3 for the overview
            ->get();

        $newCustomersLastMonth = User::where('created_at', '>=', now()->subMonth())->count();
        $returningCustomersLastMonth = User::where('created_at', '<', now()->subMonth())
            ->whereHas('orders', function ($query) {
                $query->whereBetween('created_at', [now()->subMonth(), now()]);
            })
            ->count();

        $salesByDay = Order::selectRaw('DATE(created_at) as sale_day, SUM(total_amount) as daily_sales')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('sale_day')
            ->orderBy('sale_day')
            ->get();

        return response()->json([
            'general_overview' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'new_customers' => $newCustomers,
                'total_products_sold' => $totalProductsSold,
            ],
            'recent_orders' => $recentOrders,
            'sales_overview' => $salesOverview,
            'top_selling_products' => $topSellingProducts,
            'customer_insights' => [
                'new_customers_last_month' => $newCustomersLastMonth,
                'returning_customers_last_month' => $returningCustomersLastMonth,
            ],
            'sales_by_day' => $salesByDay,
        ]);
    }
}