@extends('sanity-check::layouts.base')

@section('content')
    <section class="sanity-panel" aria-labelledby="sanity-about-heading">
        <h2 id="sanity-about-heading" class="sanity-panel__title">{{ $aboutTitle }}</h2>
        <p class="sanity-panel__prose">{{ $objective }}</p>
    </section>

    @include('sanity-check::dashboard.partials.toolbar', ['run' => $run ?? []])

    @if(!$run)
        <section class="sanity-panel" aria-labelledby="sanity-empty-heading">
            <h2 id="sanity-empty-heading" class="sanity-panel__title">{{ __('No runs yet') }}</h2>
            <p class="sanity-empty">{{ __('Run a check to scan routes and see results here. Saved history will appear below when persistence is on.') }}</p>
        </section>
    @else
        @include('sanity-check::dashboard.partials.run-body')
    @endif

    @include('sanity-check::dashboard.partials.history')

    @unless($saveRuns ?? true)
        <p class="sanity-footnote" role="note">
            {{ __('Persistence is disabled (:setting); results are cached briefly. Bookmark or export JSON to keep a record.', ['setting' => 'save_runs=false']) }}
        </p>
    @endunless
@endsection
