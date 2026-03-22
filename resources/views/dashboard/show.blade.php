@extends('sanity-check::layouts.base')

@section('content')
    <nav aria-label="{{ __('Breadcrumb') }}">
        <ol class="sanity-breadcrumb">
            <li><a class="sanity-link" href="{{ route($dashboardRouteName) }}">{{ __('Sanity check') }}</a></li>
            <li aria-current="page">{{ __('Run') }} <code>{{ $run['uuid'] }}</code></li>
        </ol>
    </nav>

    <section class="sanity-panel" aria-labelledby="sanity-about-heading">
        <h2 id="sanity-about-heading" class="sanity-panel__title">{{ $aboutTitle }}</h2>
        <p class="sanity-panel__prose">{{ $objective }}</p>
    </section>

    @include('sanity-check::dashboard.partials.toolbar')

    @include('sanity-check::dashboard.partials.run-body')

    @include('sanity-check::dashboard.partials.history')

    @unless($saveRuns ?? true)
        <p class="sanity-footnote" role="note">
            {{ __('Persistence is disabled (:setting); this view may only be available for a short time.', ['setting' => 'save_runs=false']) }}
        </p>
    @endunless
@endsection
