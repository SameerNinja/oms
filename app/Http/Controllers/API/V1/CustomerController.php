<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $customers = Customer::paginate($perPage);

        return response()->json($customers);
    }
}
