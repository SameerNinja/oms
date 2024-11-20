<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_api_url()
    {
        $this->withoutExceptionHandling();

        $this->createProduct();

        $response = $this->get('api/products');

        $response->assertStatus(200);
        $response->assertSee('Test Product');
        $response->assertDontSee('Test Product 2');
    }

    public function createProduct()
    {
        return Product::factory()->create([
            'name' => 'Test Product',
        ]);
    }
}
