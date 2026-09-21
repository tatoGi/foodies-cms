@extends('admin.layouts.app')

@section('title', __('Message Details'))
@section('page_title', __('Message Details'))

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="welcome-title mb-1">{{ $submission->name }}</h2>
            <p class="text-muted mb-0 small">{{ __('Contact message received on :date', ['date' => optional($submission->created_at)->format('d M Y, H:i')]) }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.contact-submissions.index', ['type' => $submission->type]) }}" class="btn btn-light border">{{ __('Back to messages') }}</a>
            <form method="POST" action="{{ route($submission->is_read ? 'admin.contact-submissions.unread' : 'admin.contact-submissions.read', $submission) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">
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
                <button type="submit" class="btn btn-outline-danger">{{ __('Delete') }}</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <span>{{ __('Sender Details') }}</span>
                    </div>
                </div>
                <div class="panel-body d-flex flex-column gap-3">
                    <div>
                        <div class="small text-muted">{{ __('Name') }}</div>
                        <div class="fw-semibold">{{ $submission->name }}</div>
                    </div>
                    <div>
                        <div class="small text-muted">{{ __('Email') }}</div>
                        <a href="mailto:{{ $submission->email }}">{{ $submission->email }}</a>
                    </div>
                    <div>
                        <div class="small text-muted">{{ __('Phone') }}</div>
                        @if($submission->phone)
                            <a href="tel:{{ $submission->phone }}">{{ $submission->phone }}</a>
                        @else
                            <span class="text-muted">{{ __('Not provided') }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="small text-muted">{{ __('Status') }}</div>
                        <span class="badge-soft {{ $submission->is_read ? 'badge-success' : 'badge-warning' }}">
                            {{ $submission->is_read ? __('Read') : __('Unread') }}
                        </span>
                    </div>
                    <div>
                        <div class="small text-muted">{{ __('Read At') }}</div>
                        <div>{{ optional($submission->read_at)->format('d M Y, H:i') ?: __('Not read yet') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="dashboard-panel premium-shadow mb-4">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-chat-left-text me-2"></i>
                        <span>{{ __('Message') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="lh-lg">{!! nl2br(e($submission->message)) !!}</div>
                </div>
            </div>

            <div class="dashboard-panel premium-shadow">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>{{ __('Request Context') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">{{ __('Page Slug') }}</div>
                            <div>{{ $submission->page_slug ?: __('Not provided') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">{{ __('Locale') }}</div>
                            <div class="text-uppercase">{{ $submission->locale ?: __('Not provided') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">{{ __('Form Name') }}</div>
                            <div>{{ $submission->form_name ?: __('Not provided') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">{{ __('IP Address') }}</div>
                            <div>{{ $submission->ip_address ?: __('Not provided') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">{{ __('Page URL') }}</div>
                            <div class="text-break">{{ $submission->page_url ?: __('Not provided') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">{{ __('User Agent') }}</div>
                            <div class="text-break small">{{ $submission->user_agent ?: __('Not provided') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
