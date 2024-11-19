<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Customer;
use Gloudemans\Shoppingcart\Facades\Cart;

class InvoiceController extends Controller
{
    public function create(StoreInvoiceRequest $request, Customer $customer)
    {
        $customer = Customer::query()
            ->where('id', $request->get('customer_id'))
            ->first();

        $discountsData = json_decode($request->get('discountsData'), true);
        if (!empty($discountsData) && is_array($discountsData)) {
            $totalDiscountValue = array_sum(array_column($discountsData, 'discount_value'));
        } else {
            $totalDiscountValue = 0;
        }
        return view('invoices.index', [
            'customer' => $customer,
            'carts' => Cart::instance('order')->content(),
            'discountsData' => $discountsData,
            'totalDiscountValue' => $totalDiscountValue,
        ]);
    }
}
