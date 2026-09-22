<aside class="admin-sidebar">
    @php
        $contactNotifications = $adminContactNotifications ?? ['unreadCount' => 0, 'latest' => collect()];
    @endphp
    <div class="admin-sidebar__brand">
        <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-3 text-decoration-none">
            <div class="brand-icon">N</div>
            <span class="brand-name">NewHome CMS</span>
        </a>
    </div>
    <nav class="admin-sidebar__nav">

        {{-- Dashboard --}}
        <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span class="admin-nav-label">{{ __('Dashboard') }}</span>
        </a>

        {{-- Content --}}
        <div class="sidebar-section-label">{{ __('Content') }}</div>

        <a class="admin-nav-link {{ request()->routeIs('admin.pages.*') ? 'is-active' : '' }}" href="{{ route('admin.pages.index') }}">
            <i class="bi bi-file-earmark-fill"></i>
            <span class="admin-nav-label">{{ __('Pages') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.page-templates.*') ? 'is-active' : '' }}" href="{{ route('admin.page-templates.index') }}">
            <i class="bi bi-layers-half"></i>
            <span class="admin-nav-label">{{ __('Templates') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">
            <i class="bi bi-box-seam-fill"></i>
            <span class="admin-nav-label">{{ __('Products') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.pos-devices.*') ? 'is-active' : '' }}" href="{{ route('admin.pos-devices.index') }}">
            <i class="bi bi-hdd-network"></i>
            <span class="admin-nav-label">{{ __('POS devices') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.posts.*') ? 'is-active' : '' }}" href="{{ route('admin.posts.index') }}">
            <i class="bi bi-pen-fill"></i>
            <span class="admin-nav-label">{{ __('Posts') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.reels.*') ? 'is-active' : '' }}" href="{{ route('admin.reels.index') }}">
            <i class="bi bi-camera-reels-fill"></i>
            <span class="admin-nav-label">{{ __('Reels') }}</span>
        </a>

        {{-- Store --}}
        <div class="sidebar-section-label">{{ __('Store') }}</div>

        <a class="admin-nav-link {{ request()->routeIs('admin.sales.*') ? 'is-active' : '' }}" href="{{ route('admin.sales.index') }}">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span class="admin-nav-label">{{ __('Sales Stats') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}">
            <i class="bi bi-bag-check-fill"></i>
            <span class="admin-nav-label">{{ __('Orders') }}</span>
        </a>

        {{-- Site --}}
        <div class="sidebar-section-label">{{ __('Site') }}</div>

        <a class="admin-nav-link {{ request()->routeIs('admin.menus.*') ? 'is-active' : '' }}" href="{{ route('admin.menus.index') }}">
            <i class="bi bi-menu-app-fill"></i>
            <span class="admin-nav-label">{{ __('Navigation') }}</span>
        </a>
        <a class="admin-nav-link {{ (request()->routeIs('admin.contact-submissions.*') && request()->query('type', \App\Models\ContactSubmission::TYPE_MESSAGE) === \App\Models\ContactSubmission::TYPE_MESSAGE) ? 'is-active' : '' }}" href="{{ route('admin.contact-submissions.index', ['type' => \App\Models\ContactSubmission::TYPE_MESSAGE]) }}">
            <i class="bi bi-chat-dots-fill"></i>
            <span class="admin-nav-label">{{ __('Contact Messages') }}</span>
            @if(($contactNotifications['messagesUnread'] ?? 0) > 0)
                <span class="badge rounded-pill bg-danger ms-auto">{{ $contactNotifications['messagesUnread'] > 99 ? '99+' : $contactNotifications['messagesUnread'] }}</span>
            @endif
        </a>
        <a class="admin-nav-link {{ (request()->routeIs('admin.contact-submissions.*') && request()->query('type') === \App\Models\ContactSubmission::TYPE_CALL_REQUEST) ? 'is-active' : '' }}" href="{{ route('admin.contact-submissions.index', ['type' => \App\Models\ContactSubmission::TYPE_CALL_REQUEST]) }}">
            <i class="bi bi-telephone-inbound-fill"></i>
            <span class="admin-nav-label">{{ __('Call Requests') }}</span>
            @if(($contactNotifications['callRequestsUnread'] ?? 0) > 0)
                <span class="badge rounded-pill bg-danger ms-auto">{{ $contactNotifications['callRequestsUnread'] > 99 ? '99+' : $contactNotifications['callRequestsUnread'] }}</span>
            @endif
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.blocks.*') ? 'is-active' : '' }}" href="{{ route('admin.blocks.index') }}">
            <i class="bi bi-bounding-box"></i>
            <span class="admin-nav-label">{{ __('Blocks') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.languages.*') ? 'is-active' : '' }}" href="{{ route('admin.languages.index') }}">
            <i class="bi bi-translate"></i>
            <span class="admin-nav-label">{{ __('Languages') }}</span>
        </a>

        {{-- System --}}
        <div class="sidebar-divider my-3 border-top border-muted opacity-10"></div>

        <a class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people-fill"></i>
            <span class="admin-nav-label">{{ __('Users') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}">
            <i class="bi bi-shield-lock-fill"></i>
            <span class="admin-nav-label">{{ __('Roles & Permissions') }}</span>
        </a>

        <div class="sidebar-divider my-3 border-top border-muted opacity-10"></div>

        <a class="admin-nav-link {{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}" href="{{ route('admin.media.index') }}">
            <i class="bi bi-images"></i>
            <span class="admin-nav-label">{{ __('Media Library') }}</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">
            <i class="bi bi-gear-fill"></i>
            <span class="admin-nav-label">{{ __('General Settings') }}</span>
        </a>

    </nav>
</aside>
