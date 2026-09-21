@extends('layouts.checkout')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 text-success mb-3">Payment Successful</h1>
            <p class="mb-1"><strong>Order ID:</strong> {{ $payment->bog_order_id }}</p>
            <p class="mb-1"><strong>Status:</strong> {{ strtoupper((string) $payment->status) }}</p>
            <p class="mb-3"><strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</p>
            <p class="text-muted mb-4">Your payment has been completed.</p>

            <a class="btn btn-primary" href="{{ route('checkout.index') }}">Back to Checkout</a>
        </div>
    </div>
@endsection

