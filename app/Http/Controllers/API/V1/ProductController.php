<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController
{
    public function index(Request $request){

        $products = Product::all();

        return response()->json($products);
    }
}
