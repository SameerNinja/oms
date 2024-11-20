<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test order creation.
     *
     * @return void
     */
    public function test_order_creation()
    {
        // Create a customer
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);

        // Create products for the order
        $product1 = Product::factory()->create([
            'name' => 'iPhone 14 Pro',
            'slug' => 'iphone-14-pro',
            'code' => '1',
            'buying_price' => 900,
            'quantity' => 10 // Initially stock 10 of the product
        ]);

        $product2 = Product::factory()->create([
            'name' => 'ASUS Laptop',
            'slug' => 'asus-laptop',
            'code' => '2',
            'buying_price' => 900,
            'quantity' => 10 // Initially stock 10 of the product
        ]);

        // Prepare data to send with the request
        $orderData = [
            'date' => '2024-12-18',
            'customer_id' => $customer->id,
            'products' => [
                [
                    'id' => $product1->id,
                    'qty' => 1
                ],
                [
                    'id' => $product2->id,
                    'qty' => 22
                ]
            ]
        ];

        // Make the POST request to create the order
        $response = $this->postJson('/api/order/create', $orderData);

        // Assert that the response has a 201 status code (Created)
        $response->assertStatus(201);

        // Assert that the response contains expected order fields
        $response->assertJson([
            'customer_id' => $customer->id,
            'order_date' => '2024-12-18T00:00:00.000000Z',
            'order_status' => 0, // Pending status
            'total_products' => 23,
            'sub_total' => 20700,
            'vat' => 0,
            'total' => 17685,
            'invoice_no' => $response->json('invoice_no'), // Invoice no is dynamic
            'payment_type' => 'HandCash',
            'pay' => 0,
            'due' => 17685,
        ]);

        // Assert that the response contains order details
        $response->assertJsonFragment([
            'product_id' => $product1->id,
            'quantity' => 1,
            'unitcost' => 900,
            'total' => 900,
        ]);

        $response->assertJsonFragment([
            'product_id' => $product2->id,
            'quantity' => 22,
            'unitcost' => 900,
            'total' => 19800,
        ]);

        // Assert that the discounts are present
        $response->assertJsonFragment([
            'discount_type' => 'percentage',
            'discount_value' => 1980.00,
            'discount_name' => 'volume',
            'description' => '10% off for items with more than 10 units',
        ]);

        $response->assertJsonFragment([
            'discount_type' => 'percentage',
            'discount_value' => 1035.00,
            'discount_name' => 'loyalty',
            'description' => '5% off for loyal customers with more than 5 orders',
        ]);
    }
}
