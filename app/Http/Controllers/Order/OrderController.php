<?php

namespace App\Http\Controllers\Order;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('created_at', 'desc')->get();

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function create()
    {
        Cart::instance('order')->destroy();

        return view('orders.create', [
            'carts' => Cart::content(),
            'customers' => Customer::all(['id', 'name']),
            'products' => Product::all(),
        ]);
    }

    public function store(OrderStoreRequest $request)
    {
        $discountsData = json_decode($request->get('discountsData'), true);
        $totalDiscountValue = 0; // Default to 0 if $discountsData is empty or not an array
        if (!empty($discountsData) && is_array($discountsData)) {
            $totalDiscountValue = array_sum(array_column($discountsData, 'discount_value'));
         }
        $validateData = $request->all();
        $validateData['total'] = Cart::instance('order')->total() - $totalDiscountValue;
        if (!empty($totalDiscountValue)) {
            $validateData['due'] -= $totalDiscountValue;
        }

        $order = Order::create($validateData);
        // Create Order Details
        $contents = Cart::instance('order')->content();

        $oDetails = [];

        foreach ($contents as $content) {
            $oDetails['order_id'] = $order['id'];
            $oDetails['product_id'] = $content->id;
            $oDetails['quantity'] = $content->qty;
            $oDetails['unitcost'] = $content->price;
            $oDetails['total'] = $content->subtotal;
            $oDetails['created_at'] = Carbon::now();

            OrderDetails::insert($oDetails);
        }
        if (!empty($discountsData)) {
            $discounts = [];
            foreach ($discountsData as $discount) {
                $discounts[] = [
                    'order_id' => $order->id,
                    'discount_type' => $discount['discount_type'],
                    'discount_value' => $discount['discount_value'],
                    'discount_name' => $discount['discount_name'],
                    'description' => $discount['description'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            // Insert all discounts at once
            DB::table('order_detail_discounts')->insert($discounts);
        }
        // Delete Cart Sopping History
        Cart::destroy();

        return redirect()
            ->route('orders.index')
            ->with('success', 'Order has been created!');
    }

    public function show(Order $order)
    {
        $order->loadMissing(['customer','orderDetailDiscounts', 'details'])->get();

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    public function update(Order $order, Request $request)
    {
        // TODO refactoring

        // Reduce the stock
        $products = OrderDetails::where('order_id', $order)->get();

        foreach ($products as $product) {
            Product::where('id', $product->product_id)
                ->update(['quantity' => DB::raw('quantity-' . $product->quantity)]);
        }

        $order->update([
            'order_status' => OrderStatus::COMPLETE,
        ]);

        return redirect()
            ->route('orders.complete')
            ->with('success', 'Order has been completed!');
    }

    public function destroy(Order $order)
    {
        $order->delete();
    }

    public function downloadInvoice($order)
    {
        $order = Order::with(['customer', 'details'])
            ->where('id', $order)
            ->first();

        return view('orders.print-invoice', [
            'order' => $order,
        ]);
    }
}
