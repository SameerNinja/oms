@extends('layouts.tabler')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center mb-3">
            <div class="col">
                <h2 class="page-title">
                    {{ __('Edit Product') }}
                </h2>
            </div>
        </div>

        @include('partials._breadcrumbs', ['model' => $product])
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">

            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('put')

                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">
                                    {{ __('Product Image') }}
                                </h3>

                                <img
                                    class="img-account-profile mb-2"
                                    src="{{ $product->product_image ? asset('storage/products/'.$product->product_image) : asset('assets/img/products/default.webp') }}"
                                    id="image-preview"
                                >

                                <div class="small font-italic text-muted mb-2">
                                    JPG or PNG no larger than 2 MB
                                </div>

                                <input
                                    type="file"
                                    accept="image/*"
                                    id="image"
                                    name="product_image"
                                    class="form-control @error('product_image') is-invalid @enderror"
                                    onchange="previewImage();"
                                >

                                @error('product_image')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">

                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">
                                    {{ __('Product Details') }}
                                </h3>

                                <div class="row row-cards">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">
                                                {{ __('Name') }}
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input type="text"
                                                   id="name"
                                                   name="name"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   placeholder="Product name"
                                                   value="{{ old('name', $product->name) }}"
                                            >

                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="buying_price">
                                                Buying price
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input type="text"
                                                   id="buying_price"
                                                   name="buying_price"
                                                   class="form-control @error('buying_price') is-invalid @enderror"
                                                   placeholder="0"
                                                   value="{{ old('buying_price', $product->buying_price) }}"
                                            >

                                            @error('buying_price')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">
                                                {{ __('Quantity') }}
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input type="number"
                                                   id="quantity"
                                                   name="quantity"
                                                   class="form-control @error('quantity') is-invalid @enderror"
                                                   min="0"
                                                   value="{{ old('quantity', $product->quantity) }}"
                                                   placeholder="0"
                                            >

                                            @error('quantity')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 mb-0">
                                            <label for="notes" class="form-label">
                                                {{ __('Notes') }}
                                            </label>

                                            <textarea name="notes"
                                                      id="notes"
                                                      rows="5"
                                                      class="form-control @error('notes') is-invalid @enderror"
                                                      placeholder="Product notes"
                                            >{{ old('notes', $product->notes) }}</textarea>

                                            @error('notes')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>`
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <x-button.save type="submit">
                                    {{ __('Update') }}
                                </x-button.save>

                                <x-button.back route="{{ route('products.index') }}">
                                    {{ __('Cancel') }}
                                </x-button.back>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@pushonce('page-scripts')
    <script src="{{ asset('assets/js/img-preview.js') }}"></script>
@endpushonce
