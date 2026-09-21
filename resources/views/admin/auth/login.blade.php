<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('CMS Admin Login') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-page">
    <div class="ambient-shape ambient-shape--a" aria-hidden="true"></div>
    <div class="ambient-shape ambient-shape--b" aria-hidden="true"></div>

    <main class="auth-shell">
        <section class="auth-showcase" aria-hidden="true">
            <div class="showcase-badge">
                <span class="dot"></span>
                <span>{{ __('Secure Workspace') }}</span>
            </div>

            <h1>{{ __('One Place For Your Content Team') }}</h1>
            <p>{{ __('Manage pages, posts, media, and settings from a single control center.') }}</p>

            <div class="showcase-grid">
                <article class="showcase-card">
                    <h2>{{ __('Fast Publishing') }}</h2>
                    <p>{{ __('Create and update content with structured blocks and clean workflows.') }}</p>
                </article>
                <article class="showcase-card">
                    <h2>{{ __('Multi-Locale Ready') }}</h2>
                    <p>{{ __('Deliver localized content with locale-based templates and slugs.') }}</p>
                </article>
            </div>
        </section>

        <section class="auth-panel">
            <div class="panel-head">
                <div class="brand-mark" aria-hidden="true">CMS</div>
                <div>
                    <h2>{{ __('Welcome Back') }}</h2>
                    <p>{{ __('Sign in to continue') }}</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert-box" role="alert">
                    <strong>{{ __('Login failed.') }}</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form">
                @csrf

                <div class="field">
                    <label for="email">{{ __('Email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="name@company.com"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">{{ __('Password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="{{ __('Enter your password') }}"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="auth-row">
                    <label class="remember-box">
                        <input type="checkbox" name="remember" id="remember" value="1">
                        <span>{{ __('Remember me') }}</span>
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    {{ __('Sign In') }}
                </button>
            </form>

            <p class="panel-note">{{ __('Authorized users only.') }}</p>
        </section>
    </main>
</body>
</html>
