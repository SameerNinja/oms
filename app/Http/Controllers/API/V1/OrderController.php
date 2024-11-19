<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class OrderController
{
    public function createOrder(Request $request)
    {
        dd($request->all());
    }

    public function retrieveOrder($orderId)
    {
        try {
            $order = Order::query()->with([
                'details',
                'discounts',
            ])->findOrFail($orderId);
            return response()->json($order);
        } catch (ModelNotFoundException $mex) {
            return response()->json(['message' => 'Order not found'], 400);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], $th->getCode());
        }
    }
}
