@extends('layouts.tabler')

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">
                            {{ __('Order Details') }}
                        </h3>
                    </div>

                    <div class="card-actions btn-actions">
                        <x-action.close route="{{ route('orders.index') }}"/>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row row-cards mb-3">
                        <div class="col">
                            <label for="order_date" class="form-label required">
                                {{ __('Order Date') }}
                            </label>
                            <input type="text"
                                   id="order_date"
                                   class="form-control"
                                   value="{{ $order->order_date->format('d-m-Y') }}"
                                   disabled
                            >
                        </div>

                        <div class="col">
                            <label for="invoice_no" class="form-label required">
                                {{ __('Invoice No.') }}
                            </label>
                            <input type="text"
                                   id="invoice_no"
                                   class="form-control"
                                   value="{{ $order->invoice_no }}"
                                   disabled
                            >
                        </div>

                        <div class="col">
                            <label for="customer" class="form-label required">
                                {{ __('Customer') }}
                            </label>
                            <input type="text"
                                   id="customer"
                                   class="form-control"
                                   value="{{ $order->customer->name }}"
                                   disabled
                            >
                        </div>

                        <div class="col">
                            <label for="payment_type" class="form-label required">
                                {{ __('Payment Type') }}
                            </label>

                            <input type="text" id="payment_type" class="form-control" value="{{ $order->payment_type }}" disabled>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="thead-light">
                            <tr>
                                <th scope="col" class="align-middle text-center">No.</th>
                                <th scope="col" class="align-middle text-center">Photo</th>
                                <th scope="col" class="align-middle text-center">Product Name</th>
                                <th scope="col" class="align-middle text-center">Product Code</th>
                                <th scope="col" class="align-middle text-center">Quantity</th>
                                <th scope="col" class="align-middle text-center">Price</th>
                                <th scope="col" class="align-middle text-center">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->details as $item)
                                    <tr>
                                        <td class="align-middle text-center">
                                            {{ $loop->iteration  }}
                                        </td>
                                        <td class="align-middle text-center">
                                            <div style="max-height: 80px; max-width: 80px;">
                                                <img class="img-fluid"  src="{{ $item->product->product_image ? asset('storage/products/'.$item->product->product_image) : asset('assets/img/products/default.webp') }}">
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ $item->product->name }}
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ $item->product->code }}
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ number_format($item->unitcost, 2) }}
                                        </td>
                                        <td class="align-middle text-center">
                                            {{ number_format($item->total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Display Discounts -->
                                <tr>
                                    <td colspan="6" class="text-end">Discounts</td>
                                    <td class="text-center">
                                        @foreach ($order->orderDetailDiscounts as $discount)
                                            <div>
                                                <strong>{{ $discount->discount_name }}:</strong>
                                                {{ number_format($discount->discount_value, 2) }} ({{ $discount->description }})
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end">Sub total</td>
                                    <td class="text-center">{{ number_format($order->sub_total, 2) }}</td>
                                </tr>
                                <!-- Calculate Total Discount -->
                                <tr>
                                    <td colspan="6" class="text-end">Total Discount</td>
                                    <td class="text-center">
                                        {{ number_format($order->orderDetailDiscounts->sum('discount_value'), 2) }}
                                    </td>
                                </tr>


                                <tr>
                                    <td colspan="6" class="text-end">Payed amount</td>
                                    <td class="text-center">{{ number_format($order->pay, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end">Due</td>
                                    <td class="text-center">{{ number_format($order->due, 2) }}</td>
                                </tr>
                                {{-- <tr>
                                    <td colspan="6" class="text-end">VAT</td>
                                    <td class="text-center">{{ number_format($order->vat, 2) }}</td>
                                </tr> --}}                                   

                                <tr>
                                    <td colspan="6" class="text-end">Total</td>
                                    <td class="text-center">{{ number_format($order->total, 2) }}</td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                </div>

                <div class="card-footer text-end">
                    @if ($order->order_status === \App\Enums\OrderStatus::PENDING)
                        <form action="{{ route('orders.update', $order) }}" method="POST">
                            @method('put')
                            @csrf

                            <button type="submit"
                                    class="btn btn-success"
                                    onclick="return confirm('Are you sure you want to complete this order?')"
                            >
                                {{ __('Complete Order') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
