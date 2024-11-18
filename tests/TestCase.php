<?php

namespace Tests;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, InteractsWithExceptionHandling;

    public function createUser()
    {
        return User::factory()->create([
           'name' => 'admin',
           'email' => 'admin@admin.com'
        ]);
    }

    public function createProduct()
    {
        return Product::factory()->create([
            'name' => 'Test Product',
            'category_id' => $this->createCategory(),
            'unit_id' => $this->createUnit()
        ]);
    }

    public function createCustomer()
    {
        return Customer::factory()->create([
            'name' => 'Customer 1'
        ]);
    }
}
