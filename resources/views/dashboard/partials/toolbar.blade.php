{{-- Primary actions: run, exports, navigation --}}
<div class="sanity-toolbar">
    <div class="sanity-toolbar__actions">
        <form method="post" action="{{ route('sanity-check.run') }}">
            @csrf
            <button class="sanity-btn sanity-btn--primary" type="submit">{{ __('Run checks') }}</button>
        </form>
        @if($exportJsonEnabled ?? true)
            @if(($run['uuid'] ?? null))
                <a class="sanity-btn sanity-btn--ghost" href="{{ route('sanity-check.export.run', ['uuid' => $run['uuid']]) }}">{{ __('Export JSON') }}</a>
            @else
                <a class="sanity-btn sanity-btn--ghost" href="{{ route('sanity-check.export') }}">{{ __('Export JSON (latest)') }}</a>
            @endif
        @endif
        @if($exportCsvEnabled ?? false)
            @if(($run['uuid'] ?? null))
                <a class="sanity-btn sanity-btn--ghost" href="{{ route('sanity-check.export.csv.run', ['uuid' => $run['uuid']]) }}">{{ __('Export CSV') }}</a>
            @else
                <a class="sanity-btn sanity-btn--ghost" href="{{ route('sanity-check.export.csv') }}">{{ __('Export CSV (latest)') }}</a>
            @endif
        @endif
    </div>
    <nav class="sanity-toolbar__nav" aria-label="{{ __('Dashboard navigation') }}">
        @if($isRunDetail ?? false)
            <a class="sanity-link" href="{{ route($dashboardRouteName) }}">{{ __('Overview') }}</a>
        @endif
    </nav>
</div>
