@extends('admin.layouts.app')

@section('title', __('Orders'))
@section('page_title', __('Orders'))

@php
    $statusClasses = [
        'pending_payment' => 'badge-soft badge-warning',
        'paid' => 'badge-soft badge-primary',
        'processing' => 'badge-soft badge-info',
        'completed' => 'badge-soft badge-success',
        'cancelled' => 'badge-soft badge-danger',
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="welcome-title mb-1">{{ __('Orders') }}</h2>
            <p class="text-muted mb-0 small">{{ __('Review customer orders, delivery details and purchased products.') }}</p>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow mb-4">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label for="search" class="form-label">{{ __('Search') }}</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control"
                        placeholder="{{ __('Search by customer, email, phone, address or order ID') }}"
                    >
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="status" class="form-label">{{ __('Status') }}</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>
                                {{ __(ucfirst($statusOption)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-8 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ __('Apply') }}</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-light border flex-grow-1">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow">
        <div class="panel-body p-0">
            @if($orders->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-bag-x fs-1 text-muted d-block mb-3"></i>
                    <h3 class="h5 mb-2">{{ __('No orders found.') }}</h3>
                    <p class="text-muted mb-0">{{ __('There are no orders matching the current filters.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light-soft">
                            <tr>
                                <th class="py-3 px-4 text-muted small fw-bold">#</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Customer') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Contact') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Delivery Address') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Items') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Total') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Status') }}</th>
                                <th class="py-3 px-4 text-muted small fw-bold">{{ __('Ordered At') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td class="py-3 px-4 text-muted small">#{{ $order->id }}</td>
                                    <td class="py-3">
                                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                                        <div class="text-muted small">{{ $order->customer_email }}</div>
                                    </td>
                                    <td class="py-3">
                                        <div>{{ $order->customer_phone }}</div>
                                    </td>
                                    <td class="py-3">
                                        <div class="small">{{ $order->delivery_address }}</div>
                                    </td>
                                    <td class="py-3">
                                        <details>
                                            <summary class="small fw-semibold cursor-pointer">
                                                {{ $order->items_count }} {{ __('items') }}
                                            </summary>
                                            <div class="mt-2 d-flex flex-column gap-2">
                                                @foreach($order->items as $item)
                                                    <div class="small">
                                                        <div class="fw-medium">{{ $item->product_name }}</div>
                                                        <div class="text-muted">
                                                            {{ $item->quantity }} x {{ number_format((float) $item->unit_price, 2) }} ₾
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </td>
                                    <td class="py-3 fw-bold text-success">{{ number_format((float) $order->total, 2) }} ₾</td>
                                    <td class="py-3">
                                        <div class="d-flex flex-column gap-2">
                                            <span class="{{ $statusClasses[$order->status] ?? 'badge-soft badge-secondary' }}">
                                                {{ __(str_replace('_', ' ', ucfirst($order->status))) }}
                                            </span>
                                            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="d-flex gap-2">
                                                    <select name="status" class="form-select form-select-sm">
                                                        @foreach(array_filter($statusOptions, fn ($statusOption) => $statusOption !== 'pending_payment') as $statusOption)
                                                            <option value="{{ $statusOption }}" @selected($order->status === $statusOption)>
                                                                {{ __(str_replace('_', ' ', ucfirst($statusOption))) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-light border">{{ __('Save') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-muted small">
                                        {{ optional($order->ordered_at)->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-top">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
