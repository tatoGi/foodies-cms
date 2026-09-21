@extends('layouts.checkout')

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Checkout</h1>

                    <div class="alert alert-info">
                        {{ __('Customer login and registration are handled by the NewHome frontend application. This CMS checkout form stays guest-only.') }}
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(count($items) === 0)
                        <div class="alert alert-warning mb-0">
                            {{ __('Cart is empty. Add products on the website before starting checkout.') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item['name'] }}</td>
                                        <td class="text-end">{{ $item['quantity'] }}</td>
                                        <td class="text-end">{{ number_format((float) $item['unit_price'], 2) }} {{ $currency }}</td>
                                        <td class="text-end">{{ number_format((float) $item['line_total'], 2) }} {{ $currency }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">{{ number_format((float) $total, 2) }} {{ $currency }}</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                    <form id="bog-start-form" action="{{ route('checkout.bog.start') }}" method="post" class="mt-3">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="customer_name">Customer Name</label>
                                <input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="customer_email">Customer Email</label>
                                <input class="form-control" id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="customer_phone">Phone</label>
                                <input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone ?? '') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="delivery_address">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3">{{ old('delivery_address', auth()->user()->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <input type="hidden" name="save_card" value="0">
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="save_card" name="save_card">
                            <label class="form-check-label" for="save_card">
                                Save card for future payments
                            </label>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit" @disabled(count($items) === 0)>Pay with BOG</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Saved Cards</h2>

                    <p class="text-muted mb-0">Saved cards are available only through the NewHome frontend user account flow.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function submitWithRedirect(formId) {
            const form = document.getElementById(formId);

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (!response.ok || !data.redirect_url) {
                    window.location.reload();
                    return;
                }

                window.location.href = data.redirect_url;
            });
        }

        submitWithRedirect('bog-start-form');
    </script>
@endsection
