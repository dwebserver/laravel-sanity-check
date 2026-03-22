@if($showHistory ?? false)
    <section class="sanity-panel" aria-labelledby="sanity-history-heading">
        <h2 id="sanity-history-heading" class="sanity-panel__title">{{ __('Saved run history') }}</h2>
        @if($history->isEmpty())
            <p class="sanity-empty">{{ __('No saved runs yet. Runs appear here when persistence is enabled.') }}</p>
        @else
            <div class="sanity-table-wrap">
                <table class="sanity-table">
                    <caption class="visually-hidden">{{ __('Recent sanity check runs') }}</caption>
                    <thead>
                    <tr>
                        <th scope="col">{{ __('When') }}</th>
                        <th scope="col">{{ __('Run') }}</th>
                        <th scope="col">{{ __('Environment') }}</th>
                        <th scope="col">{{ __('Trigger') }}</th>
                        <th scope="col">{{ __('Totals') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($history as $h)
                        @php $c = $h->counts ?? []; @endphp
                        <tr>
                            <td>{{ $h->created_at }}</td>
                            <td>
                                <a class="sanity-link" href="{{ route('sanity-check.show', ['uuid' => $h->uuid]) }}">
                                    <code style="font-size:0.85em;">{{ \Illuminate\Support\Str::limit($h->uuid, 14, '…') }}</code>
                                </a>
                            </td>
                            <td>{{ $h->environment }}</td>
                            <td>{{ $h->trigger }}</td>
                            <td style="font-family:var(--sanity-mono);font-size:0.78rem;">
                                2xx {{ $c['2xx'] ?? 0 }} · 3xx {{ $c['3xx'] ?? 0 }} · 4xx {{ $c['4xx'] ?? 0 }} · 5xx {{ $c['5xx'] ?? 0 }} ·
                                {{ __('ig') }} {{ $c['ignored'] ?? 0 }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endif
