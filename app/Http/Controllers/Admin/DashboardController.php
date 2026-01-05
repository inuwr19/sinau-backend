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

        // Di sistem ini status order "selesai" = 'paid'
        $paidTodayQuery = (clone $ordersTodayQuery)->where('status', 'paid');

        $todayOrdersCount = $ordersTodayQuery->count();
        $todayPaidOrdersCount = $paidTodayQuery->count();
        $todayRevenue = (float) $paidTodayQuery->sum('total');

        // --- Item terjual hari ini ---
        $todayItemsSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $today)
            ->where('orders.status', 'paid')
            ->sum('order_items.qty');

        // --- Statistik total ---
        $totalMenuItems = MenuItem::count();
        $totalMembers = Member::count();
        $totalUsers = User::count();
        $totalBranches = Branch::count();

        // --- Order terbaru (5 terakhir) ---
        $recentOrders = Order::with('branch')
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
                // tetap pakai nama completed_orders_count supaya kompatibel dengan frontend
                'completed_orders_count' => $todayPaidOrdersCount,
                'revenue' => $todayRevenue,
                'items_sold' => (int) $todayItemsSold,
                'avg_order_value' => $todayPaidOrdersCount > 0
                    ? $todayRevenue / $todayPaidOrdersCount
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
