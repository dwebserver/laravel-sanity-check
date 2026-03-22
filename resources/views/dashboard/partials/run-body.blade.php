{{-- Expects: $run, $hasServerErrors, $filterUrls, $filterFormAction, $filterQuery, $filterBucket, $buckets, $counts --}}
@if($hasServerErrors)
    <div class="sanity-alert sanity-alert--danger" role="alert">
        <span class="sanity-alert__icon" aria-hidden="true">⚠</span>
        <p class="sanity-alert__body">
            <strong>{{ __('Server errors (5xx) detected') }}</strong>
            {{ __('One or more routes returned a server error. Review the 5xx table below and fix failing endpoints before release.') }}
        </p>
    </div>
@endif

<section class="sanity-panel" aria-labelledby="sanity-stats-heading">
    <h2 id="sanity-stats-heading" class="visually-hidden">{{ __('Summary counts') }}</h2>
    <ul class="sanity-stats">
        <li class="sanity-stat sanity-stat--ok">
            <p class="sanity-stat__label">2xx</p>
            <p class="sanity-stat__value">{{ $counts['2xx'] ?? 0 }}</p>
        </li>
        <li class="sanity-stat sanity-stat--r3">
            <p class="sanity-stat__label">3xx</p>
            <p class="sanity-stat__value">{{ $counts['3xx'] ?? 0 }}</p>
        </li>
        <li class="sanity-stat sanity-stat--r4">
            <p class="sanity-stat__label">4xx</p>
            <p class="sanity-stat__value">{{ $counts['4xx'] ?? 0 }}</p>
        </li>
        <li class="sanity-stat sanity-stat--r5">
            <p class="sanity-stat__label">5xx</p>
            <p class="sanity-stat__value">{{ $counts['5xx'] ?? 0 }}</p>
        </li>
        <li class="sanity-stat sanity-stat--ig">
            <p class="sanity-stat__label">{{ __('Ignored') }}</p>
            <p class="sanity-stat__value">{{ $counts['ignored'] ?? 0 }}</p>
        </li>
    </ul>
</section>

<section class="sanity-panel" aria-labelledby="sanity-run-meta-heading">
    <h2 id="sanity-run-meta-heading" class="sanity-panel__title">{{ __('Latest run') }}</h2>
    <dl class="sanity-meta">
        <div>
            <dt class="visually-hidden">{{ __('Run identifier') }}</dt>
            <dd><strong>{{ __('UUID') }}:</strong> <code>{{ $run['uuid'] }}</code></dd>
        </div>
        <div>
            <dt class="visually-hidden">{{ __('Context') }}</dt>
            <dd>
                <strong>{{ __('Environment') }}:</strong> <code>{{ $run['environment'] }}</code>
                · <strong>{{ __('Trigger') }}:</strong> <code>{{ $run['trigger'] }}</code>
                @if(!empty($run['source']))
                    · <strong>{{ __('Source') }}:</strong> <code>{{ $run['source'] }}</code>
                @endif
            </dd>
        </div>
        <div>
            <dt class="visually-hidden">{{ __('Timing') }}</dt>
            <dd>
                @if(($run['duration_ms'] ?? null) !== null)
                    <strong>{{ __('Duration') }}:</strong> {{ number_format((int) $run['duration_ms']) }} ms
                    ·
                @endif
                @if($run['started_at'] ?? null)
                    <strong>{{ __('Started') }}:</strong> {{ $run['started_at'] }}
                    @if($run['finished_at'] ?? null)
                        · <strong>{{ __('Finished') }}:</strong> {{ $run['finished_at'] }}
                    @endif
                @elseif($run['created_at'] ?? null)
                    <strong>{{ __('Recorded') }}:</strong> {{ $run['created_at'] }}
                @endif
            </dd>
        </div>
    </dl>
</section>

<section class="sanity-panel" aria-labelledby="sanity-filter-heading">
    <h2 id="sanity-filter-heading" class="sanity-panel__title">{{ __('Filter results') }}</h2>
    <form class="sanity-filter" method="get" action="{{ $filterFormAction }}" role="search">
        <div class="sanity-filter__search">
            <label for="sanity-search-q">{{ __('Search route or URI') }}</label>
            <input class="sanity-input" id="sanity-search-q" name="q" type="search"
                   value="{{ $filterQuery }}" autocomplete="off"
                   placeholder="{{ __('Route name, pattern, or resolved path…') }}">
        </div>
        <div>
            <button type="submit" class="sanity-btn sanity-btn--primary">{{ __('Apply') }}</button>
            @if($filterQuery !== '' || $filterBucket !== '')
                <a class="sanity-btn sanity-btn--ghost" href="{{ $filterUrls['all'] ?? '#' }}">{{ __('Clear filters') }}</a>
            @endif
        </div>
    </form>
    <div class="sanity-chips" role="group" aria-label="{{ __('Filter by classification') }}">
        <span class="sanity-chips__legend">{{ __('Class') }}</span>
        <a class="sanity-chip {{ $filterBucket === '' ? 'is-active' : '' }}" href="{{ $filterUrls['all'] }}">{{ __('All') }}</a>
        @foreach($buckets as $b)
            <a class="sanity-chip {{ $filterBucket === $b ? 'is-active' : '' }}" href="{{ $filterUrls[$b] ?? '#' }}">{{ $b }}</a>
        @endforeach
    </div>
    @if($filterQuery !== '' || $filterBucket !== '')
        <p class="sanity-status" role="status">
            @if($filterQuery !== '')
                {{ __(':n routes match the search (of :total in this run).', [
                    'n' => $run['filter_after_search_count'] ?? 0,
                    'total' => $run['filter_total_in_run'] ?? 0,
                ]) }}
            @endif
            @if($filterBucket !== '')
                @if($filterQuery !== '') {{ ' ' }} @endif
                {{ __('Showing :count routes in the :bucket group.', [
                    'count' => $run['filter_visible_rows_count'] ?? 0,
                    'bucket' => $filterBucket,
                ]) }}
            @endif
        </p>
    @endif
</section>

@php
    $bucketLabels = [
        '2xx' => __('Successful responses (2xx)'),
        '3xx' => __('Redirects (3xx)'),
        '4xx' => __('Client errors (4xx)'),
        '5xx' => __('Server errors (5xx)'),
        'ignored' => __('Ignored routes'),
    ];
@endphp

@foreach($buckets as $bucket)
    @php
        $p = $run['paginators'][$bucket] ?? null;
        $visible = $run['visible_buckets'] ?? $buckets;
    @endphp
    @if(!in_array($bucket, $visible, true))
        @continue
    @endif
    <section class="sanity-panel" id="bucket-{{ $bucket }}" aria-labelledby="heading-{{ $bucket }}">
        <h2 id="heading-{{ $bucket }}" class="sanity-panel__title">{{ $bucketLabels[$bucket] ?? $bucket }}</h2>
        @if($p === null || $p->total() === 0)
            <p class="sanity-empty">{{ __('No routes in this group for the current filters.') }}</p>
        @else
            <div class="sanity-table-wrap">
                <table class="sanity-table">
                    <caption>
                        {{ $p->total() }} {{ $p->total() === 1 ? __('row') : __('rows') }}
                    </caption>
                    <thead>
                    <tr>
                        <th scope="col">{{ __('Method') }}</th>
                        <th scope="col">{{ __('Route name') }}</th>
                        <th scope="col">{{ __('Resolved URI') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Notes') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($p as $row)
                        <tr>
                            <td><span class="sanity-pill">{{ $row['method'] }}</span></td>
                            <td>{{ $row['route_name'] ?: '—' }}</td>
                            <td style="font-family:var(--sanity-mono);font-size:0.8rem;">{{ $row['resolved_uri'] ?? $row['uri'] }}</td>
                            <td>{{ $row['status_code'] ?? '—' }}</td>
                            <td>
                                @if($bucket === 'ignored')
                                    <dl class="sanity-note">
                                        @if(!empty($row['ignored_reason']))
                                            <dt>{{ __('Reason') }}</dt>
                                            <dd>{{ $row['ignored_reason'] }}</dd>
                                        @endif
                                        @if(!empty($row['error_message']))
                                            <dt>{{ __('Detail') }}</dt>
                                            <dd>{{ $row['error_message'] }}</dd>
                                        @endif
                                        @if(empty($row['ignored_reason']) && empty($row['error_message']))
                                            <dd class="sanity-note--inline">—</dd>
                                        @endif
                                    </dl>
                                @else
                                    <p class="sanity-note--inline" style="margin:0;">
                                        {{ $row['ignored_reason'] ?? $row['error_message'] ?? '—' }}
                                    </p>
                                @endif
                                @if(($row['response_time_ms'] ?? null) !== null)
                                    <p class="sanity-note--inline" style="margin:6px 0 0;">{{ $row['response_time_ms'] }} ms</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($p->hasPages())
                <nav class="sanity-pager" aria-label="{{ __('Pagination for :group', ['group' => $bucket]) }}">
                    @if($p->onFirstPage())
                        <span aria-disabled="true">{{ __('Previous') }}</span>
                    @else
                        <a href="{{ $p->previousPageUrl() }}">{{ __('Previous') }}</a>
                    @endif
                    <span>{{ __('Page :current of :last', ['current' => $p->currentPage(), 'last' => $p->lastPage()]) }}</span>
                    @if($p->hasMorePages())
                        <a href="{{ $p->nextPageUrl() }}">{{ __('Next') }}</a>
                    @else
                        <span aria-disabled="true">{{ __('Next') }}</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
@endforeach
