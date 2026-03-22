<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Access denied') }} · Sanity check</title>
    @include('sanity-check::partials.styles')
</head>
<body class="sanity-check sanity-theme--dark sanity-error">
<main class="wrap sanity-error__main" role="main">
    <h1>{{ __('Access denied') }}</h1>
    <p class="sanity-error__lead">{{ __('You do not have permission to use the sanity check dashboard.') }}</p>
    @if(!empty($ability))
        <p class="sanity-error__meta"><span class="visually-hidden">{{ __('Required ability') }}:</span> <code>{{ $ability }}</code></p>
    @endif
    <p><a class="sanity-link" href="{{ url('/') }}">{{ __('Return to the site') }}</a></p>
</main>
</body>
</html>
