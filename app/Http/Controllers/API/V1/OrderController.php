<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\API\DiscountRequest;
use App\Http\Requests\API\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetailDiscount;
use App\Models\OrderDetails;
use App\Models\Product;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class OrderController
{
    public function getOrders(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        $orders = Order::query();
        if ($status == 'completed') {
            $orders->where('order_status', 1);
        }

        if ($status == 'pending') {
            $orders->where('order_status', 0);
        }

        if ($status == 'canceled') {
            $orders->where('order_status', 2);
        }

        if ($status == 'due') {
            $orders->where('due', '>' , 0);
        }

        $orders = $orders->paginate($perPage);
        $orders->getCollection()->load(['details', 'discounts']);

        return response()->json($orders);
    }

    public function createOrder(StoreOrderRequest $request)
    {
        try {
            $paymentMethod = $request->input('payment_method', 'HandCash');
            $requestProducts = $request->input('products');
            $totalProducts = collect($requestProducts)->reduce(function(?int $total, $item){
                return $total + $item['qty'];
            });

            $products = Product::query()->whereIn('id', Arr::pluck($requestProducts, 'id'))->get();
            $subTotal = $products->reduce(function(?int $total, $item) use($requestProducts) {
                $requestQty = collect($requestProducts)->where('id', $item->id)->first()['qty'] ?? 0;
                return $total + ($item->buying_price * $requestQty);
            });

            $discountsData = $this->getDiscount($request, $subTotal, $requestProducts, $products);

            $total = $subTotal - ($discountsData['total_discount'] ?? 0);

            $invoiceNo = IdGenerator::generate([
                'table' => 'orders',
                'field' => 'invoice_no',
                'length' => 10,
                'prefix' => 'INV-',
            ]);

            $order = Order::create([
                'customer_id' => $request->input('customer_id'),
                'order_date' => $request->input('date', now()),
                'order_status' => OrderStatus::PENDING->value,
                'total_products' => $totalProducts,
                'sub_total' => $subTotal,
                'vat' => 0,
                'total' => $total,
                'invoice_no' => $invoiceNo,
                'payment_type' => $paymentMethod,
                'pay' => 0,
                'due' => $total,
            ]);

            $oDetails = [];

            foreach ($requestProducts as $content) {
                $product = $products->where('id', $content['id'])->first();
                $oDetails['order_id'] = $order['id'];
                $oDetails['product_id'] = $content['id'];
                $oDetails['quantity'] = $content['qty'];
                $oDetails['unitcost'] = $product->buying_price;
                $oDetails['total'] = $product->buying_price * $content['qty'];
                $oDetails['created_at'] = Carbon::now();

                OrderDetails::query()->insert($oDetails);
            }

            if (!empty($discountsData)) {
                $discounts = [];
                foreach ($discountsData['discountsData'] as $discount) {
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
                OrderDetailDiscount::query()->insert($discounts);
            }

            $order->loadMissing(['details', 'discounts']);

            return response()->json($order, 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function discount(DiscountRequest $request)
    {
        try {
            $requestProducts = $request->input('products');

            $products = Product::query()->whereIn('id', Arr::pluck($requestProducts, 'id'))->get();
            $total = $products->reduce(function(?int $total, $item) use($requestProducts) {
                $requestQty = collect($requestProducts)->where('id', $item->id)->first()['qty'] ?? 0;
                return $total + ($item->buying_price * $requestQty);
            });

            $discountDetail = $this->getDiscount($request, $total, $requestProducts, $products);
            return response()->json($discountDetail);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], $th->getCode());
        }
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

    public function payOrder($orderId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'pay_amount' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                        "message" => "Validation Error",
                        "errors" => $validator->errors()
                ], 422);
            }

            $order = Order::query()->findOrFail($orderId);
            if ($order->due <= 0) {
                return response()->json(['message' => "No due found"]);
            }

            $order->decrement('due', $request->pay_amount);

            $order->loadMissing(['details', 'discounts']);
            return response()->json($order);
        } catch (ModelNotFoundException $mex) {
            return response()->json(['message' => 'Order not found'], 400);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], $th->getCode());
        }
    }

    public function orderStatusUpdate($orderId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:completed,cancel'
            ]);

            if ($validator->fails()) {
                return response()->json([
                        "message" => "Validation Error",
                        "errors" => $validator->errors()
                ], 422);
            }

            $reqStatus = $request->input('status');
            $order = Order::query()->findOrFail($orderId);
            if ($reqStatus == "completed") {
                $status = 1;
            } elseif ($reqStatus == "cancel") {
                $status = 2;
            } else{
                $status = $order->order_status;
            }

            $order->update(['order_status' => $status]);

            $order->loadMissing(['details', 'discounts']);
            return response()->json($order);
        } catch (ModelNotFoundException $mex) {
            return response()->json(['message' => 'Order not found'], 400);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], $th->getCode());
        }
    }

    private function getDiscount($request, $total, $requestProducts, $products)
    {
        $discount = 0;
        $seasonalDiscount = 0;
        $volumeDiscount = 0;
        $loyaltyDiscount = 0;
        $discountsData = [];

        // Seasonal Discount: 15% off if current date is between December 20 and December 31
        $orderDate = Carbon::parse($request->input('date', now()));
        if ($orderDate->between(Carbon::createFromDate($orderDate->year, 12, 20), Carbon::createFromDate($orderDate->year, 12, 31))) {
            $seasonalDiscount = $total * 0.15;
            $discount += $seasonalDiscount;
            $discountsData[] = [
                'discount_type' => 'percentage',
                'discount_name' => "seasonal",
                'description' => '15% off during holiday season',
                'discount_value' => $seasonalDiscount,
            ];
        }

        // Volume Discount
        foreach ($requestProducts as $product) {
            if ($product['qty'] > 10) {
                $_product = $products->where('id', $product['id'])->first();
                $volumeDiscount += $_product->buying_price * $product['qty'] * 0.10;
                $discount += $volumeDiscount;

                $discountsData[] = [
                    'discount_type' => 'percentage',
                    'discount_name' => "volume",
                    'description' => '10% off for items with more than 10 units',
                    'discount_value' => $volumeDiscount,
                ];
            }
        }

        // Loyalty Discount
        if ($request->input('customer_id')) {
            $customer = Customer::query()->withCount('orders')->find($request->input('customer_id'));
            if ($customer->orders_count > 5) {
                $loyaltyDiscount = $total * 0.05;
                $discount += $loyaltyDiscount;

                $discountsData[] = [
                    'discount_type' => 'percentage',
                    'discount_name' => "loyalty",
                    'description' => '5% off for loyal customers with more than 5 orders',
                    'discount_value' => $loyaltyDiscount,
                ];
            }
        }

        return [
            'total_discount' => $discount,
            'seasonal_discount' => $seasonalDiscount,
            'volume_discount' => $volumeDiscount,
            'loyalty_discount' => $loyaltyDiscount,
            'discountsData' => $discountsData
        ];
    }
}
