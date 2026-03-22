<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Sanity check' }}</title>
    @include('sanity-check::partials.styles')
</head>
<body class="sanity-check sanity-theme--{{ $uiTheme ?? 'dark' }}">
<a class="skip-link" href="#sanity-main">{{ __('Skip to main content') }}</a>
<header class="sanity-header" role="banner">
    <div class="wrap sanity-header__inner">
        <div class="sanity-brand">
            <span class="sanity-brand__mark" aria-hidden="true"></span>
            <div>
                <p class="sanity-brand__eyebrow">{{ __('Route health') }}</p>
                <h1 class="sanity-brand__title">{{ $pageHeading ?? 'Sanity check' }}</h1>
            </div>
        </div>
    </div>
</header>
<main id="sanity-main" class="wrap sanity-main" tabindex="-1" role="main">
    @if (session('sanity_check_error'))
        <div class="sanity-alert sanity-alert--danger" role="alert">
            {{ session('sanity_check_error') }}
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
