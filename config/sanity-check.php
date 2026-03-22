<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | 1) Package toggles
    |--------------------------------------------------------------------------
    |
    | Master switch for the package. When false, HTTP routes and the Artisan
    | command are not registered, service container bindings are still available
    | for advanced/testing use cases, and migrations remain publishable.
    |
    */
    'enabled' => env('SANITY_CHECK_ENABLED', true),

    /*
    | Register the Blade dashboard and related POST endpoints under
    | `dashboard_path`. Disable in API-only apps or CI images.
    |
    */
    'enable_dashboard' => env('SANITY_CHECK_ENABLE_DASHBOARD', true),

    /*
    | Register `php artisan sanity-check:run`. Disable if you only want the UI
    | or are concerned about CLI access in shared hosting shells.
    |
    */
    'enable_command' => env('SANITY_CHECK_ENABLE_COMMAND', true),

    /*
    |--------------------------------------------------------------------------
    | 2) Dashboard configuration
    |--------------------------------------------------------------------------
    |
    | URI prefix for package routes (no leading slash). If empty, the provider
    | falls back to `sanity-check`.
    |
    */
    'dashboard_path' => env('SANITY_CHECK_PATH', 'admin/sanity-check'),

    /*
    | Laravel route name for the GET dashboard page. Update your links, policies,
    | and tests if you change this. Other package routes keep fixed names:
    | `sanity-check.run`, `sanity-check.show`, `sanity-check.export`, `sanity-check.export.run`,
    | `sanity-check.export.csv`, `sanity-check.export.csv.run`.
    |
    */
    'dashboard_route_name' => env('SANITY_CHECK_DASHBOARD_ROUTE', 'sanity-check.dashboard'),

    /*
    | Middleware applied only to package routes (in addition to the environment
    | guard middleware registered by the package). Typically include `web` for
    | sessions, CSRF on POST, and cookies.
    |
    | When `authorization_ability` is set, unauthenticated guests receive 403.
    | For session-based admin panels, add `auth` (or your auth middleware) so
    | guests are redirected to login instead, e.g. ['web', 'auth'].
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | 3) Route discovery rules
    |--------------------------------------------------------------------------
    |
    | When non-empty, a route name must start with one of these prefixes
    | (case-sensitive, Str::startsWith) to be eligible.
    |
    */
    'route_name_prefixes' => [],

    /*
    | When non-empty, the route URI (normalized with a leading slash) must start
    | with one of these prefixes.
    |
    */
    'route_uri_prefixes' => [],

    /*
    | If non-empty, only routes whose exact name appears in this list are kept.
    | Unnamed routes are excluded when this list is non-empty.
    |
    */
    'include_route_names' => [],

    /*
    | If non-empty, at least one Laravel Str::is glob must match the route name
    | or URI.
    |
    */
    'include_patterns' => [],

    /*
    | Exact route names to always drop after includes are evaluated.
    |
    */
    'exclude_route_names' => [],

    /*
    | Str::is patterns matched against the route name or URI to drop routes.
    |
    */
    'exclude_patterns' => [],

    /*
    | Uppercase HTTP verbs to consider. HEAD is skipped automatically when GET is
    | allowed and both exist on the same URI to avoid duplicate work.
    |
    */
    'allowed_methods' => ['GET', 'HEAD'],

    /*
    | Skip routes that use URL signature validation (`signed` middleware or
    | `ValidateSignature`), since generated sanity URLs will not include valid
    | signatures.
    |
    */
    'skip_signed_routes' => env('SANITY_CHECK_SKIP_SIGNED', true),

    /*
    | Skip routes that apply `throttle` rate limiting to avoid consuming quota
    | during scans.
    |
    */
    'skip_throttled_routes' => env('SANITY_CHECK_SKIP_THROTTLED', false),

    /*
    | Skip Closure actions (`Route::get(fn () => ...)`) which are harder to
    | introspect and sometimes used for one-off debugging endpoints.
    |
    */
    'skip_closure_routes' => env('SANITY_CHECK_SKIP_CLOSURES', false),

    /*
    | Skip routes whose controller class lives under `vendor/` (third-party
    | packages) to focus checks on first-party application code.
    |
    */
    'skip_vendor_routes' => env('SANITY_CHECK_SKIP_VENDOR', true),

    /*
    | Hard cap on scanned route/method candidates per run (after filters).
    |
    */
    'max_routes_per_run' => (int) env('SANITY_CHECK_MAX_ROUTES', 500),

    /*
    |--------------------------------------------------------------------------
    | 4) Route execution rules
    |--------------------------------------------------------------------------
    |
    | How each candidate request is performed:
    | - `internal` — Request::create + HTTP kernel (no outbound network, respects
    |   app middleware; session/auth simulation is limited).
    | - `http` — Illuminate HTTP client against `APP_URL` (real TCP; see auth note
    |   in docs; honors `follow_redirects` and strict timeouts better).
    |
    */
    'execution_mode' => env('SANITY_CHECK_EXECUTION_MODE', 'internal'),

    /*
    | Only used when `execution_mode` is `http`. When false, the client stops at
    | 3xx responses and reports the redirect status instead of following it.
    |
    */
    'follow_redirects' => env('SANITY_CHECK_FOLLOW_REDIRECTS', true),

    /*
    | Best-effort PHP time limit bump before each internal kernel dispatch. Does
    | not interrupt long-running controllers. The HTTP client mode uses this as
    | the per-request Guzzle timeout (seconds).
    |
    */
    'timeout_seconds' => (float) env('SANITY_CHECK_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | 5) Response classification rules
    |--------------------------------------------------------------------------
    |
    | How redirects are summarized:
    | - `reported` — count 3xx in the 3xx bucket.
    | - `success` — fold 3xx into the 2xx summary bucket (treat as OK noise).
    | - `ignored` — move 3xx into the ignored bucket with reason `redirect`.
    |
    */
    'treat_redirects_as' => env('SANITY_CHECK_TREAT_REDIRECTS', 'reported'),

    /*
    | Force these HTTP status codes into the ignored bucket (e.g. 419 CSRF or
    | custom app codes you do not want to fail the summary).
    |
    */
    'ignored_status_codes' => [],

    /*
    |--------------------------------------------------------------------------
    | 6) Auth / authorization
    |--------------------------------------------------------------------------
    |
    | Gate ability checked by package middleware on HTTP routes. Set
    | `SANITY_CHECK_AUTH_ABILITY` to an empty string to disable the check
    | (trusted environments only). When null is returned from env, the default
    | ability name below is used.
    |
    */
    'authorization_ability' => (static function (): ?string {
        $v = env('SANITY_CHECK_AUTH_ABILITY');

        if ($v === null) {
            return 'viewSanityCheck';
        }

        return $v === '' ? null : $v;
    })(),

    /*
    | Container-resolvable class implementing AdminUserResolverInterface. Return
    | an authenticated model from `resolve()` to attach a user to internal kernel
    | requests. HTTP execution mode does not forward session cookies today—use
    | tokens or custom headers in a future binding if you need that.
    |
    */
    'admin_user_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | 7) Parameter resolution
    |--------------------------------------------------------------------------
    |
    | Map `{parameter}` names to callables or ParameterResolverInterface classes.
    |
    */
    'parameter_resolvers' => [],

    /*
    | Fallback when no explicit resolver exists for a parameter:
    | - `strict` — require an explicit resolver entry (default).
    | - `placeholder` — use `default_parameter_placeholder` for missing keys.
    |
    */
    'default_parameter_strategy' => env('SANITY_CHECK_PARAM_STRATEGY', 'strict'),

    /*
    | Scalar substituted for missing parameters when strategy is `placeholder`.
    |
    */
    'default_parameter_placeholder' => env('SANITY_CHECK_PARAM_PLACEHOLDER', '1'),

    /*
    | When true, routes whose dynamic segments cannot be resolved are marked as
    | ignored (or failed when false) instead of throwing during URL generation.
    |
    */
    'ignore_unresolvable_routes' => env('SANITY_CHECK_IGNORE_UNRESOLVABLE', true),

    /*
    | When true, controller actions that type-hint an Eloquent model for a route
    | parameter without a matching `parameter_resolvers` entry are skipped during
    | discovery so they never generate bogus IDs.
    |
    */
    'ignore_missing_bound_models' => env('SANITY_CHECK_IGNORE_MISSING_MODELS', true),

    /*
    |--------------------------------------------------------------------------
    | 8) Persistence and retention
    |--------------------------------------------------------------------------
    |
    */
    'save_runs' => env('SANITY_CHECK_SAVE_RUNS', true),

    /*
    | Keep at most this many newest runs (0 = unlimited). Deletes older rows.
    |
    */
    'max_saved_runs' => (int) env('SANITY_CHECK_MAX_SAVED_RUNS', 0),

    /*
    | Delete runs older than this many days (0 = disabled).
    |
    */
    'retention_days' => (int) env('SANITY_CHECK_RETENTION_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | 9) Exports
    |--------------------------------------------------------------------------
    |
    */
    'enable_json_export' => env('SANITY_CHECK_EXPORT_JSON', true),

    /*
    | Stream a CSV download of the latest or requested run.
    |
    */
    'enable_csv_export' => env('SANITY_CHECK_EXPORT_CSV', false),

    /*
    |--------------------------------------------------------------------------
    | 10) Safety and environments
    |--------------------------------------------------------------------------
    |
    | When false and `APP_ENV=production`, package HTTP routes answer 404 and the
    | Artisan command refuses to run. Set true only after you accept production
    | traffic and load implications.
    |
    */
    'allow_in_production' => env('SANITY_CHECK_ALLOW_IN_PRODUCTION', false),

    /*
    | When non-empty, only these `APP_ENV` values may access routes or CLI. Empty
    | means all environments (subject to `allow_in_production`). Comma-separated
    | env var supported for convenience.
    |
    */
    'environment_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SANITY_CHECK_ENVIRONMENTS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | 11) UI options
    |--------------------------------------------------------------------------
    |
    | Applied to the `<body>` element as `sanity-theme--{value}` for styling hooks.
    |
    */
    'ui_theme' => env('SANITY_CHECK_UI_THEME', 'dark'),

    /*
    | Rows per visible classification table (each bucket paginates independently
    | using `p_2xx`, `p_3xx`, `p_4xx`, `p_5xx`, `p_ignored`).
    |
    */
    'results_per_page' => (int) env('SANITY_CHECK_RESULTS_PER_PAGE', 25),

    /*
    | Introductory copy shown above the actions.
    |
    */
    'about_panel_title' => 'About this check',

    /*
    | Introductory copy shown in the about panel.
    |
    */
    'objective_text' => 'This tool issues HTTP requests against registered application routes (subject to the filters below) and summarizes status codes. Use it to spot broken links, auth misconfiguration, and unexpected redirects after deploys.',

    /*
    | Toggle the saved history table and cap its rows.
    |
    */
    'show_history' => env('SANITY_CHECK_SHOW_HISTORY', true),

    'history_limit' => (int) env('SANITY_CHECK_HISTORY_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | 12) Extension points / class bindings
    |--------------------------------------------------------------------------
    |
    | Override package services with your own implementations. Keys must be
    | interface or class names resolvable by the container; values are concrete
    | class names to `app()->make()`.
    |
    | Examples:
    | DynamicWeb\SanityCheck\Contracts\RouteScannerInterface::class =>
    |     App\Sanity\CustomRouteScanner::class,
    | DynamicWeb\SanityCheck\Contracts\RouteTesterInterface::class =>
    |     App\Sanity\CustomRouteTester::class,
    | DynamicWeb\SanityCheck\Contracts\ResultRepositoryInterface::class =>
    |     App\Sanity\CustomResultRepository::class,
    |
    */
    'services' => [
        'bindings' => [],
    ],

];
