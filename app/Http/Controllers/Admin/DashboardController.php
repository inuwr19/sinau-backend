<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $today = Carbon::today();

        // --- Statistik order hari ini (kena BranchScope juga) ---
        $ordersTodayQuery = Order::whereDate('created_at', $today);
        $completedTodayQuery = (clone $ordersTodayQuery)->where('status', 'completed');

        $todayOrdersCount = $ordersTodayQuery->count();
        $todayCompletedOrdersCount = $completedTodayQuery->count();
        $todayRevenue = (float) $completedTodayQuery->sum('total');

        // --- Item terjual hari ini ---
        // kolom jumlah = 'qty' sesuai model OrderItem
        // pakai join ke tabel orders agar bisa filter created_at + status
        $todayItemsSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->where('orders.status', 'completed')
            ->sum('order_items.qty');

        // --- Statistik total ---
        $totalMenuItems = MenuItem::count();
        $totalMembers = Member::count();
        $totalUsers = User::count();
        $totalBranches = Branch::count();

        // --- Order terbaru (5 terakhir) ---
        $recentOrders = Order::with('branch') // relasi branch() sudah ada di model Order
            ->orderByDesc('created_at')
            ->limit(5)
            ->get([
                'id',
                'order_number',
                'total',
                'status',
                'branch_id',
                'created_at',
            ]);

        return response()->json([
            'today' => [
                'orders_count' => $todayOrdersCount,
                'completed_orders_count' => $todayCompletedOrdersCount,
                'revenue' => $todayRevenue,
                'items_sold' => (int) $todayItemsSold,
                'avg_order_value' => $todayCompletedOrdersCount > 0
                    ? $todayRevenue / $todayCompletedOrdersCount
                    : 0,
            ],
            'totals' => [
                'menu_items' => $totalMenuItems,
                'members' => $totalMembers,
                'users' => $totalUsers,
                'branches' => $totalBranches,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }
}
