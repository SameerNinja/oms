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

        $cart_items = Cart::instance($this->cart_instance)->content();

        $discount = $this->calculateDiscounts($total);

        return view('livewire.order-form', [
            'subtotal' => $total,
            'total' => $total - $discount + ($total * (1 + (is_numeric($this->taxes) ? $this->taxes : 0) / 100)),
            'discount' => $discount,
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

    public function calculateDiscounts($total): float
    {
        $discount = 0;

        // Seasonal Discount: 15% off if current date is between December 20 and December 31
        $orderDate = Carbon::parse($this->orderDate);

        // Seasonal Discount: 15% off if the order date is between December 20 and December 31
        if ($orderDate->between(Carbon::createFromDate($orderDate->year, 12, 20), Carbon::createFromDate($orderDate->year, 12, 31))) {
            $discount += $total * 0.15;  // 15% seasonal discount
        }

        // Volume-Based Discount: 10% off any item with more than 10 units
        $cart = Cart::instance($this->cart_instance)->content();
        foreach ($cart as $cartItem) {
            if ($cartItem->qty > 10) {
                $discount += $cartItem->price * $cartItem->qty * 0.10; // 10% discount per item with more than 10 units
            }
        }

        // Loyalty Discount: 5% off the total if the customer has placed more than 5 orders
        if (!empty($this->selectedPerson)) {
            $customer = Customer::query()->withCount('orders')->find($this->selectedPerson);
            if ($customer->orders_count > 5) {
                $discount += $total * 0.05;  // 5% loyalty discount
            }
        }


        return $discount;
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
