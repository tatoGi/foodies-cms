@extends('admin.layouts.app')

@section('title', __('POS devices'))
@section('page_title', __('POS devices'))

@section('content')
    <div class="mb-4">
        <h2 class="welcome-title mb-1">{{ __('POS devices') }}</h2>
        <p class="text-muted mb-0">სალარო ონლაინია, თუ ბოლო სიგნალი 2 წუთზე ახალია.</p>
    </div>

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="py-3 text-muted small fw-bold">სახელი</th>
                            <th class="py-3 text-muted small fw-bold">სტატუსი</th>
                            <th class="py-3 text-muted small fw-bold">ბოლო სიგნალი</th>
                            <th class="py-3 text-muted small fw-bold">ვერსია</th>
                            <th class="py-3 text-muted small fw-bold">სამზარეულო</th>
                            <th class="py-3 text-muted small fw-bold">რიგი</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            @php
                                $online = $device->is_active && $device->last_seen_at && $device->last_seen_at->greaterThan(now()->subMinutes(2));
                                $status = is_array($device->status) ? $device->status : [];
                            @endphp
                            <tr>
                                <td class="py-3 fw-bold">{{ $device->name }}</td>
                                <td class="py-3">
                                    @if(! $device->is_active)
                                        <span class="badge bg-secondary">გამორთული</span>
                                    @elseif($online)
                                        <span class="badge bg-success">ონლაინ</span>
                                    @else
                                        <span class="badge bg-danger">ოფლაინ</span>
                                    @endif
                                </td>
                                <td class="py-3">{{ $device->last_seen_at?->timezone('Asia/Tbilisi')->format('Y-m-d H:i:s') ?? '—' }}</td>
                                <td class="py-3"><code>{{ $device->app_version ?: '—' }}</code></td>
                                <td class="py-3">
                                    @if(array_key_exists('kitchen_enabled', $status))
                                        {{ $status['kitchen_enabled'] ? 'ჩართული' : 'გამორთული' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-3">{{ $status['queue_depth'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-muted">მოწყობილობა ჯერ არ არის დამატებული.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
