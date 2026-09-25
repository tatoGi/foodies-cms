@extends('admin.layouts.app')

@section('title', __('Admin Dashboard'))
@section('page_title', __('Dashboard'))

@section('content')
    <div class="dashboard-welcome mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-4 mb-3">
                    <div class="welcome-avatar">
                        <span class="avatar-initial">{{ substr(auth('admin')->user()->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <h2 class="welcome-title mb-1">{{ __('Welcome back') }}, {{ auth('admin')->user()->name }} 👋</h2>
                        <p class="welcome-subtitle mb-0 text-muted">{{ __("Here's what's happening with your CMS today. Everything is running smoothly.") }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="server-status d-inline-flex align-items-center gap-3">
                    <div class="status-indicator">
                        <span class="dot pulse"></span>
                        <span class="status-text">{{ __('Live System') }}</span>
                    </div>
                    <div class="status-divider"></div>
                    <div class="update-time">
                        <i class="bi bi-arrow-repeat spin"></i>
                        <span>{{ __('Updated just now') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-primary-soft">
                        <i class="bi bi-files text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Total Pages') }}</span>
                        <h3 class="stat-value">{{ $totalPages }}</h3>
                        <div class="stat-status positive">
                            <i class="bi bi-graph-up"></i>
                            <span>{{ __('Active now') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-success-soft">
                        <i class="bi bi-box-seam text-success"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Products') }}</span>
                        <h3 class="stat-value">{{ number_format($totalProducts) }}</h3>
                        @if($totalProducts > 0)
                            <div class="stat-status positive">
                                <i class="bi bi-check-circle"></i>
                                <span>{{ __('Products') }}</span>
                            </div>
                        @else
                            <div class="stat-status neutral">
                                <i class="bi bi-plus-circle"></i>
                                <span>{{ __('No products found.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar {{ $totalProducts > 0 ? 'w-100' : 'w-0' }} bg-success"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-info-soft">
                        <i class="bi bi-pencil-square text-info"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Total Posts') }}</span>
                        <h3 class="stat-value">{{ $totalPosts }}</h3>
                        <div class="stat-status positive">
                            <i class="bi bi-lightning-fill"></i>
                            <span>{{ __('Recent content') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100 bg-info"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-warning-soft">
                        <i class="bi bi-image text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Library Media') }}</span>
                        <h3 class="stat-value">{{ number_format($totalMedia) }}</h3>
                        <div class="stat-status positive">
                            <i class="bi bi-arrow-up"></i>
                            <span>{{ __('Files in library') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100 bg-warning"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="dashboard-panel premium-shadow">
                <div class="panel-header d-flex align-items-center justify-content-between">
                    <div class="panel-header-title">
                        <i class="bi bi-activity me-2"></i>
                        <span>{{ __('Recent System Activity') }}</span>
                    </div>
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-light-active">{{ __('View All Logs') }}</a>
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
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="dashboard-panel premium-shadow mb-4">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-lightning-charge me-2 text-warning"></i>
                        <span>{{ __('Quick CMS Actions') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('admin.pages.create') }}" class="action-button active">
                            <div class="action-icon bg-primary text-white"><i class="bi bi-plus-lg"></i></div>
                            <div class="action-label">{{ __('Create New Page') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('admin.posts.create') }}" class="action-button">
                            <div class="action-icon bg-success-soft text-success"><i class="bi bi-newspaper"></i></div>
                            <div class="action-label">{{ __('Draft New Post') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('admin.products.create') }}" class="action-button">
                            <div class="action-icon bg-success-soft text-success"><i class="bi bi-box-seam"></i></div>
                            <div class="action-label">{{ __('Create Product') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('admin.page-templates.create') }}" class="action-button">
                            <div class="action-icon bg-info-soft text-info"><i class="bi bi-layout-text-window-reverse"></i></div>
                            <div class="action-label">{{ __('Create Page Template') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('admin.blocks.create') }}" class="action-button">
                            <div class="action-icon bg-info-soft text-info"><i class="bi bi-diagram-3"></i></div>
                            <div class="action-label">{{ __('Add Block Type') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                        <a href="{{ route('admin.media.index') }}" class="action-button">
                            <div class="action-icon bg-warning-soft text-warning"><i class="bi bi-cloud-arrow-up"></i></div>
                            <div class="action-label">{{ __('Media Library') }}</div>
                            <i class="bi bi-chevron-right ms-auto"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info Row -->
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="dashboard-panel premium-shadow border-0 bg-dark-card text-white overflow-hidden">
                <div class="panel-body py-4 relative">
                    <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-4">
                        <div class="system-status-info d-flex align-items-center gap-4">
                            <div class="status-icon-box">
                                <i class="bi bi-hdd-stack text-primary h3 mb-0"></i>
                            </div>
                            <div>
                                <h4 class="h6 mb-1 text-white-50">{{ __('Storage Usage') }}</h4>
                                <h3 class="h5 mb-0 fw-bold">{{ $storage['used'] }} GB {{ __('used of') }} {{ $storage['total'] }} GB {{ __('total') }}</h3>
                            </div>
                        </div>

                        <div class="usage-progress-container flex-grow-1 mx-lg-5 w-100">
                            <div class="progress-bar-container" style="height: 10px;">
                                <div class="progress-bar-fill" style="width: {{ $storage['percent'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 font-xs">
                                <span class="text-white-50">{{ $storage['percent'] }}% {{ __('Capacity Occupied') }}</span>
                                @if($storage['percent'] > 80)
                                    <span class="text-warning fw-medium"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('Critically Full soon') }}</span>
                                @else
                                    <span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i> {{ __('Plenty of space') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="system-details-horizontal d-flex align-items-center gap-4 border-lg-start border-white-10 ps-lg-4">
                            <div class="text-center text-lg-start">
                                <span class="text-white-50 d-block small">{{ __('Index Status') }}</span>
                                <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i> {{ __('Healthy') }}</span>
                            </div>
                            <div class="text-center text-lg-start">
                                <span class="text-white-50 d-block small">{{ __('Cache Status') }}</span>
                                <span class="text-white small fw-bold">{{ __('Optimized') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="decoration-glow"></div>
            </div>
        </div>
    </div>
@endsection
