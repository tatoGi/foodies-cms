@extends('admin.layouts.app')

@php
    use App\Models\ContactSubmission;
    $typeLabels = [
        ContactSubmission::TYPE_MESSAGE => __('Contact Messages'),
    ];
@endphp

@section('title', $typeLabels[$activeType] ?? __('Contact Messages'))
@section('page_title', $typeLabels[$activeType] ?? __('Contact Messages'))

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="welcome-title mb-1">{{ $typeLabels[$activeType] ?? __('Contact Messages') }}</h2>
            <p class="text-muted mb-0 small">{{ __('Review website contact form submissions from visitors.') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4">
        @foreach($typeLabels as $typeKey => $typeLabel)
            <li class="nav-item">
                <a
                    class="nav-link d-inline-flex align-items-center gap-2 {{ $activeType === $typeKey ? 'active' : '' }}"
                    href="{{ route('admin.contact-submissions.index', ['type' => $typeKey]) }}"
                >
                    <span>{{ $typeLabel }}</span>
                    @if(($typeCounts[$typeKey]['unread'] ?? 0) > 0)
                        <span class="badge rounded-pill bg-danger">{{ $typeCounts[$typeKey]['unread'] }}</span>
                    @endif
                    <span class="badge rounded-pill bg-light text-muted border">{{ $typeCounts[$typeKey]['total'] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-body">
                    <div class="small text-muted mb-2">{{ __('Unread') }}</div>
                    <div class="display-6 fw-bold mb-0">{{ number_format($unreadCount) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-body">
                    <div class="small text-muted mb-2">{{ __('Total') }}</div>
                    <div class="display-6 fw-bold mb-0">{{ number_format($totalCount) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow mb-4">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.contact-submissions.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ $activeType }}">
                <div class="col-12 col-lg-7">
                    <label for="search" class="form-label">{{ __('Search') }}</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control"
                        placeholder="{{ __('Search by name, email, phone, page or message') }}"
                    >
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label for="status" class="form-label">{{ __('Status') }}</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" @selected($filters['status'] === $statusOption)>
                                {{ __(ucfirst($statusOption)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-8 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ __('Apply') }}</button>
                    <a href="{{ route('admin.contact-submissions.index', ['type' => $activeType]) }}" class="btn btn-light border flex-grow-1">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow">
        <div class="panel-body p-0">
            @if($submissions->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-text fs-1 text-muted d-block mb-3"></i>
                    <h3 class="h5 mb-2">{{ __('No contact messages found.') }}</h3>
                    <p class="text-muted mb-0">{{ __('There are no messages matching the current filters.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light-soft">
                            <tr>
                                <th class="py-3 px-4 text-muted small fw-bold">#</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Sender') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Message') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Source') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Status') }}</th>
                                <th class="py-3 text-muted small fw-bold">{{ __('Received') }}</th>
                                <th class="py-3 px-4 text-muted small fw-bold text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $submission)
                                <tr class="{{ $submission->is_read ? '' : 'table-warning-subtle' }}">
                                    <td class="py-3 px-4 text-muted small">#{{ $submission->id }}</td>
                                    <td class="py-3">
                                        <div class="fw-semibold">{{ $submission->name }}</div>
                                        <div class="small text-muted">{{ $submission->email }}</div>
                                        @if($submission->phone)
                                            <div class="small text-muted">{{ $submission->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($submission->message ?? '', 110) }}</div>
                                    </td>
                                    <td class="py-3">
                                        <div class="small fw-medium">{{ $submission->page_slug ?: __('Contact page') }}</div>
                                        @if($submission->locale)
                                            <div class="small text-muted text-uppercase">{{ $submission->locale }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <span class="badge-soft {{ $submission->is_read ? 'badge-success' : 'badge-warning' }}">
                                            {{ $submission->is_read ? __('Read') : __('Unread') }}
                                        </span>
                                    </td>
                                    <td class="py-3 small text-muted">
                                        {{ optional($submission->created_at)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('admin.contact-submissions.show', $submission) }}" class="btn btn-sm btn-light border">
                                                {{ __('Open') }}
                                            </a>
                                            <form method="POST" action="{{ route($submission->is_read ? 'admin.contact-submissions.unread' : 'admin.contact-submissions.read', $submission) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-light border">
                                                    {{ $submission->is_read ? __('Mark unread') : __('Mark read') }}
                                                </button>
                                            </form>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.contact-submissions.destroy', $submission) }}"
                                                onsubmit="return confirm('{{ __('Are you sure you want to delete this submission?') }}');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-top">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
