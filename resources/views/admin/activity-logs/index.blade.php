@extends('admin.layouts.app')

@section('title', __('System Activity Logs'))
@section('page_title', __('System Activity Logs'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('System Activity Logs') }}</h2>
            <p class="text-muted mb-0">{{ __('View all recent system activity in one place.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light-soft px-4">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
            </a>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow">
        <div class="panel-header d-flex align-items-center justify-content-between">
            <div class="panel-header-title">
                <i class="bi bi-list-ul me-2"></i>
                <span>{{ __('All Logs') }}</span>
            </div>
            <span class="badge bg-light-soft text-muted border">{{ __('Total') }}: {{ $activities->total() }}</span>
        </div>
        <div class="panel-body">
            <div class="activity-timeline">
                @forelse($activities as $activity)
                    <div class="activity-item">
                        <div class="activity-marker {{ $activity['icon_bg'] }}"></div>
                        <div class="activity-content">
                            <div class="activity-header">
                                <h4 class="activity-title">{{ $activity['title'] }}</h4>
                                <span class="activity-time">{{ $activity['time']->diffForHumans() }}</span>
                            </div>
                            <p class="activity-desc">{{ $activity['desc'] }}</p>
                            <div class="activity-tags">
                                <span class="badge-soft {{ $activity['badge_class'] }}">{{ $activity['badge'] }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">{{ __('No recent activity found.') }}</p>
                @endforelse
            </div>

            @if($activities->hasPages())
                <div class="mt-4">
                    {{ $activities->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endsection
