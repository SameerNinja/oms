<?php

namespace App\Http\Controllers\Dashboards;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $orders = Order::count();
        $completedOrders = Order::where('order_status', OrderStatus::COMPLETE)
            ->count();

        $products = Product::count();

        $customers = Customer::count();

        return view('dashboard', [
            'products' => $products,
            'orders' => $orders,
            'completedOrders' => $completedOrders,
            'customers' => $customers,
        ]);
    }
}
