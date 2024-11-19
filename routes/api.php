<?php

use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('products', [ProductController::class, 'index'])->name('api.product.index');
Route::get('customer', [CustomerController::class, 'index'])->name('api.customer.index');
Route::post('order/create', [OrderController::class, 'createOrder'])->name('api.create.order');
Route::get('order/{orderId}', [OrderController::class, 'retrieveOrder'])->name('api.create.order');
