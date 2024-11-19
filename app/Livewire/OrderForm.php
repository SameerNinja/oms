<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Component;

class OrderForm extends Component
{
    public $cart_instance;

    private $product;

    #[Validate('Required')]
    public int $taxes = 0;

    public array $invoiceProducts = [];

    #[Validate('required', message: 'Please select products')]
    public Collection $allProducts;

    public $orderDate;

    public $customers;

    public $selectedPerson = null;

    public function mount($cartInstance): void
    {
        $this->cart_instance = $cartInstance;

        $this->allProducts = Product::all();

        $this->orderDate = now()->format('Y-m-d');

        $this->customers = Customer::all(['id', 'name']);
    }

    public function render(): View
    {
        $total = $this->getTotal();

        $discounts = $this->calculateDiscounts($total);
        $cart_items = Cart::instance($this->cart_instance)->content();
        Log::info('Retrieved cart items', ['cart_itemcs' => $cart_items]);
        $discount = $discounts['total_discount'];

        return view('livewire.order-form', [
            'subtotal' => $total,
            'total' => $total - $discount,
            'discount' => $discount,
            'discounts' => $discounts,  // Pass the individual discounts
            'cart_items' => $cart_items,
        ]);
    }

    public function updatedOrderDate($value): void
    {
        $this->calculateDiscounts($this->getTotal());
    }

    public function updatedSelectedPerson($value): void
    {
        $this->calculateDiscounts($this->getTotal());
    }

    public function getTotal()
    {
        $total = 0;

        foreach ($this->invoiceProducts as $invoiceProduct) {
            if ($invoiceProduct['is_saved'] && $invoiceProduct['product_price'] && $invoiceProduct['quantity']) {
                $total += $invoiceProduct['product_price'] * $invoiceProduct['quantity'];
            }
        }

        return $total;
    }

    public function calculateDiscounts($total)
    {
        $discount = 0;
        $seasonalDiscount = 0;
        $volumeDiscount = 0;
        $loyaltyDiscount = 0;
        $discountsData = [];

        // Seasonal Discount: 15% off if current date is between December 20 and December 31
        $orderDate = Carbon::parse($this->orderDate);
        if ($orderDate->between(Carbon::createFromDate($orderDate->year, 12, 20), Carbon::createFromDate($orderDate->year, 12, 31))) {
            $seasonalDiscount = $total * 0.15;  // 15% seasonal discount
            $discount += $seasonalDiscount;
            $discountsData[] = [
                'discount_type' => 'percentage',
                'discount_name' => "seasonal ",
                'description' => '15% off during holiday season',
                'discount_value' => $seasonalDiscount,
            ];
        }

        // Volume-Based Discount: 10% off any item with more than 10 units
        $cart = Cart::instance($this->cart_instance)->content();
        foreach ($cart as $cartItem) {
            if ($cartItem->qty > 10) {
                $volumeDiscount += $cartItem->price * $cartItem->qty * 0.10; // 10% discount per item with more than 10 units
                $discount += $volumeDiscount;

                $discountsData[] = [
                    'discount_type' => 'percentage',
                    'discount_name' => "volume ",
                    'description' => '10% off for items with more than 10 units',
                    'discount_value' => $volumeDiscount,
                ];
            }
        }

        // Loyalty Discount: 5% off the total if the customer has placed more than 5 orders
        if (!empty($this->selectedPerson)) {
            $customer = Customer::query()->withCount('orders')->find($this->selectedPerson);
            if ($customer->orders_count > 5) {
                $loyaltyDiscount = $total * 0.05;  // 5% loyalty discount
                $discount += $loyaltyDiscount;

                // Apply loyalty discount proportionally to each cart item
                foreach ($cart as $cartItem) {
                    $itemShare = ($cartItem->price * $cartItem->qty) / $total;
                    $itemLoyaltyDiscount = $loyaltyDiscount * $itemShare;
                    $newPrice = ($cartItem->price * $cartItem->qty - $itemLoyaltyDiscount) / $cartItem->qty;

                    // Cart::instance($this->cart_instance)->update($cartItem->rowId, [
                    //     'price' => $newPrice,
                    //     'qty' => $cartItem->qty,
                    // ]);
                }

                $discountsData[] = [
                    'type' => 'percentage',
                    'discount_name' => "loyalty",
                    'description' => '5% off for loyal customers with more than 5 orders',
                    'value' => $loyaltyDiscount,
                ];
            }
        }
        // $cart = Cart::instance($this->cart_instance)->content();
        // Return total discount and the individual discounts for display
        return [
            'total_discount' => $discount,
            'seasonal_discount' => $seasonalDiscount,
            'volume_discount' => $volumeDiscount,
            'loyalty_discount' => $loyaltyDiscount,
            'discountsData' => $discountsData
        ];
    }

    public function addProduct(): void
    {
        foreach ($this->invoiceProducts as $key => $invoiceProduct) {
            if (! $invoiceProduct['is_saved']) {
                $this->addError('invoiceProducts.'.$key, 'This line must be saved before creating a new one.');

                return;
            }
        }

        $this->invoiceProducts[] = [
            'product_id' => '',
            'quantity' => 1,
            'is_saved' => false,
            'product_name' => '',
            'product_price' => 0,
        ];
    }

    public function editProduct($index): void
    {
        foreach ($this->invoiceProducts as $key => $invoiceProduct) {
            if (! $invoiceProduct['is_saved']) {
                $this->addError('invoiceProducts.'.$key, 'This line must be saved before editing another.');

                return;
            }
        }

        $this->invoiceProducts[$index]['is_saved'] = false;
    }

    public function saveProduct($index): void
    {
        $this->resetErrorBag();

        $product = $this->allProducts
            ->find($this->invoiceProducts[$index]['product_id']);

        $this->invoiceProducts[$index]['product_name'] = $product->name;
        $this->invoiceProducts[$index]['product_price'] = $product->buying_price;
        $this->invoiceProducts[$index]['is_saved'] = true;

        //
        $cart = Cart::instance($this->cart_instance);

        $exists = $cart->search(function ($cartItem) use ($product) {
            return $cartItem->id === $product['id'];
        });

        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product exists in the cart!');

            // not working correctly
            //unset($this->invoiceProducts[$index]);

            return;
        }

        $cart->add([
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['buying_price'],
            'qty' => $this->invoiceProducts[$index]['quantity'], //form field
            'weight' => 1,
            'options' => [
                'code' => $product['code'],
            ],
        ]);
    }

    public function removeProduct($index): void
    {
        unset($this->invoiceProducts[$index]);

        $this->invoiceProducts = array_values($this->invoiceProducts);

        //
        //Cart::instance($this->cart_instance)->remove($index);
    }
}
