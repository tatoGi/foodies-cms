<header class="admin-header">
    @php
        $headerLocales = collect();
        $contactNotifications = $adminContactNotifications ?? ['unreadCount' => 0, 'latest' => collect()];

        if (\Illuminate\Support\Facades\Schema::hasTable('languages')) {
            $headerLocales = \App\Models\Language::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('english_name')
                ->get(['code', 'country_code', 'name']);
        }
    @endphp

    <div class="admin-header__left">
        <button class="btn btn-icon btn-light-soft" type="button" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <button class="btn btn-icon btn-light-soft d-md-none" type="button" id="headerSearchToggle" aria-controls="adminSearchForm" aria-expanded="false">
            <i class="bi bi-search"></i>
        </button>
    </div>
    <div class="admin-header__center">
        <form class="admin-search" action="#" method="GET" role="search" id="adminSearchForm">
            <i class="bi bi-search"></i>
            <input type="search" name="q" placeholder="{{ __('Search for pages, posts, media...') }}" aria-label="{{ __('Search') }}">
        </form>
    </div>
    <div class="admin-header__right">
        <div class="header-tools d-flex align-items-center gap-2">
            <button class="btn btn-icon btn-light-soft theme-toggle" type="button" id="themeToggle" title="Toggle Theme">
                <i class="bi bi-sun theme-icon--sun"></i>
                <i class="bi bi-moon-stars theme-icon--moon"></i>
            </button>

            <div class="dropdown">
                <button class="btn btn-icon btn-light-soft position-relative" type="button" data-bs-toggle="dropdown" aria-label="{{ __('Contact notifications') }}">
                    <i class="bi bi-bell"></i>
                    @if(($contactNotifications['unreadCount'] ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $contactNotifications['unreadCount'] > 99 ? '99+' : $contactNotifications['unreadCount'] }}
                        </span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end premium-dropdown p-0 overflow-hidden" style="min-width: 320px;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
                        <div>
                            <div class="small text-muted text-uppercase letter-spacing-1 fw-bold">{{ __('Contact Messages') }}</div>
                            <div class="small text-muted">
                                {{ trans_choice(':count unread message|:count unread messages', (int) ($contactNotifications['unreadCount'] ?? 0), ['count' => (int) ($contactNotifications['unreadCount'] ?? 0)]) }}
                            </div>
                        </div>
                        <a href="{{ route('admin.contact-submissions.index') }}" class="btn btn-sm btn-light border">{{ __('View all') }}</a>
                    </div>

                    @forelse(($contactNotifications['latest'] ?? collect()) as $submission)
                        <a href="{{ route('admin.contact-submissions.show', $submission) }}" class="dropdown-item px-3 py-3 border-bottom">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">
                                        {{ $submission->name }}
                                        @if(! $submission->is_read)
                                            <span class="badge-soft badge-warning ms-2">{{ __('New') }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted text-truncate">{{ $submission->email }}</div>
                                    <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($submission->message, 70) }}</div>
                                </div>
                                <div class="small text-muted text-nowrap">{{ optional($submission->created_at)->diffForHumans() }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="px-3 py-4 text-center text-muted small">{{ __('No contact messages yet.') }}</div>
                    @endforelse
                </div>
            </div>
            
            <div class="lang-selector d-flex gap-1 bg-light-soft p-1 rounded-3">
                @if($headerLocales->isNotEmpty())
                    @foreach($headerLocales->take(4) as $locale)
                        <a href="{{ route('lang.switch', $locale->code) }}"
                           class="btn btn-xs d-inline-flex align-items-center gap-1 {{ app()->getLocale() === $locale->code ? 'btn-primary' : 'btn-ghost' }}"
                           title="{{ $locale->name }}">
                            <span class="fi fi-{{ strtolower($locale->country_code) }}"></span>
                            <span>{{ strtoupper($locale->code) }}</span>
                        </a>
                    @endforeach
                @else
                    <a href="{{ route('lang.switch', 'en') }}" class="btn btn-xs {{ app()->getLocale() == 'en' ? 'btn-primary' : 'btn-ghost' }}">EN</a>
                    <a href="{{ route('lang.switch', 'ka') }}" class="btn btn-xs {{ app()->getLocale() == 'ka' ? 'btn-primary' : 'btn-ghost' }}">KA</a>
                @endif
            </div>

            <div class="dropdown">
                <button class="btn user-profile-btn" type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar-mini">{{ substr(auth('admin')->user()->name, 0, 1) }}</div>
                    <span class="ms-2 d-none d-md-inline">{{ auth('admin')->user()->name }}</span>
                    <i class="bi bi-chevron-down ms-1 small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-dropdown">
                    <li class="px-3 py-2 text-muted small fw-medium uppercase letter-spacing-1">Personal</li>
                    <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('My Profile') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
