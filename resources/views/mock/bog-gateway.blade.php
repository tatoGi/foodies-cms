@extends('layouts.checkout')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">BOG Mock Gateway</h1>
            <p class="mb-1"><strong>Order ID:</strong> {{ $payment->bog_order_id }}</p>
            <p class="mb-1"><strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</p>
            <p class="mb-4 text-muted">Choose payment outcome to simulate BOG redirect + callback.</p>

            <div class="d-flex gap-2">
                <form action="{{ route('mock.bog.gateway.complete', ['orderId' => $payment->bog_order_id]) }}" method="post">
                    @csrf
                    <input type="hidden" name="result" value="success">
                    <button class="btn btn-success" type="submit">Simulate Success</button>
                </form>

                <form action="{{ route('mock.bog.gateway.complete', ['orderId' => $payment->bog_order_id]) }}" method="post">
                    @csrf
                    <input type="hidden" name="result" value="fail">
                    <button class="btn btn-danger" type="submit">Simulate Fail</button>
                </form>
            </div>
        </div>
    </div>
@endsection

