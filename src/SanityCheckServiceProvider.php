<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck;

use DynamicWeb\SanityCheck\Console\Commands\RunSanityCheckCommand;
use DynamicWeb\SanityCheck\Contracts\AdminUserResolverInterface;
use DynamicWeb\SanityCheck\Contracts\ResultRepositoryInterface;
use DynamicWeb\SanityCheck\Contracts\RouteScannerInterface;
use DynamicWeb\SanityCheck\Contracts\RouteTesterInterface;
use DynamicWeb\SanityCheck\Enums\RedirectTreatment;
use DynamicWeb\SanityCheck\Resolvers\ConfigMappedModelResolver;
use DynamicWeb\SanityCheck\Resolvers\DefaultParameterResolver;
use DynamicWeb\SanityCheck\Resolvers\NullAdminUserResolver;
use DynamicWeb\SanityCheck\Resolvers\RouteModelBindingParameterResolver;
use DynamicWeb\SanityCheck\Services\JsonExporter;
use DynamicWeb\SanityCheck\Services\ParameterResolutionManager;
use DynamicWeb\SanityCheck\Services\ResponseClassifier;
use DynamicWeb\SanityCheck\Services\ResultAggregator;
use DynamicWeb\SanityCheck\Services\ResultRepository;
use DynamicWeb\SanityCheck\Services\RouteFilter;
use DynamicWeb\SanityCheck\Services\RouteScanner;
use DynamicWeb\SanityCheck\Services\RouteTester;
use DynamicWeb\SanityCheck\Services\RunOrchestrator;
use DynamicWeb\SanityCheck\Services\SanityCheckRetentionService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class SanityCheckServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sanity-check.php', 'sanity-check');

        $this->registerBindings();
        $this->registerConfigServiceOverrides();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! (bool) config('sanity-check.enabled', true)) {
            return;
        }

        if ((bool) config('sanity-check.enable_dashboard', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'sanity-check');
        }

        if ($this->app->runningInConsole() && (bool) config('sanity-check.enable_command', true)) {
            $this->commands([RunSanityCheckCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../config/sanity-check.php' => config_path('sanity-check.php'),
        ], 'sanity-check-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'sanity-check-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sanity-check'),
        ], 'sanity-check-views');
    }

    private function registerBindings(): void
    {
        $app = $this->app;

        $app->singleton(RouteScannerInterface::class, function ($app): RouteScanner {
            return new RouteScanner(
                router: $app->make(Router::class),
                allowedMethods: array_values((array) config('sanity-check.allowed_methods', ['GET', 'HEAD'])),
            );
        });

        $app->singleton(RouteFilter::class, function ($app): RouteFilter {
            $resolverMap = (array) config('sanity-check.parameter_resolvers', []);

            return new RouteFilter(
                routeNamePrefixes: array_values((array) config('sanity-check.route_name_prefixes', [])),
                routeUriPrefixes: array_values((array) config('sanity-check.route_uri_prefixes', [])),
                includeRouteNames: array_values((array) config('sanity-check.include_route_names', [])),
                includePatterns: array_values((array) config('sanity-check.include_patterns', [])),
                excludeRouteNames: array_values((array) config('sanity-check.exclude_route_names', [])),
                excludePatterns: array_values((array) config('sanity-check.exclude_patterns', [])),
                skipSignedRoutes: (bool) config('sanity-check.skip_signed_routes', true),
                skipThrottledRoutes: (bool) config('sanity-check.skip_throttled_routes', false),
                skipClosureRoutes: (bool) config('sanity-check.skip_closure_routes', false),
                skipVendorRoutes: (bool) config('sanity-check.skip_vendor_routes', true),
                ignoreMissingBoundModels: (bool) config('sanity-check.ignore_missing_bound_models', true),
                parameterResolverKeys: array_keys($resolverMap),
                maxRoutesPerRun: (int) config('sanity-check.max_routes_per_run', 500),
            );
        });

        $app->singleton(ConfigMappedModelResolver::class, function ($app): ConfigMappedModelResolver {
            return new ConfigMappedModelResolver(
                $app,
                (array) config('sanity-check.parameter_resolvers', []),
            );
        });

        $app->singleton(RouteModelBindingParameterResolver::class, fn ($app) => new RouteModelBindingParameterResolver);

        $app->singleton(DefaultParameterResolver::class, function (): DefaultParameterResolver {
            return new DefaultParameterResolver(
                strategy: (string) config('sanity-check.default_parameter_strategy', 'strict'),
                placeholder: (string) config('sanity-check.default_parameter_placeholder', '1'),
            );
        });

        $app->singleton(ParameterResolutionManager::class, function ($app): ParameterResolutionManager {
            return new ParameterResolutionManager([
                $app->make(ConfigMappedModelResolver::class),
                $app->make(RouteModelBindingParameterResolver::class),
                $app->make(DefaultParameterResolver::class),
            ]);
        });

        $app->singleton(AdminUserResolverInterface::class, function ($app): AdminUserResolverInterface {
            $class = config('sanity-check.admin_user_resolver');
            if (is_string($class) && $class !== '') {
                return $app->make($class);
            }

            return new NullAdminUserResolver;
        });

        $app->singleton(ResponseClassifier::class, function (): ResponseClassifier {
            $raw = (string) config('sanity-check.treat_redirects_as', 'reported');
            $treatment = RedirectTreatment::tryFrom($raw) ?? RedirectTreatment::Reported;

            return new ResponseClassifier(
                redirectTreatment: $treatment,
                ignoredStatusCodes: array_map('intval', array_values((array) config('sanity-check.ignored_status_codes', []))),
            );
        });

        $app->singleton(RouteTesterInterface::class, function ($app): RouteTester {
            return new RouteTester(
                kernel: $app->make(Kernel::class),
                urlGenerator: $app->make(UrlGenerator::class),
                adminUserResolver: $app->make(AdminUserResolverInterface::class),
                timeoutSeconds: (float) config('sanity-check.timeout_seconds', 15),
                executionMode: (string) config('sanity-check.execution_mode', 'internal'),
                followRedirects: (bool) config('sanity-check.follow_redirects', true),
            );
        });

        $app->singleton(ResultAggregator::class, fn () => new ResultAggregator);

        $app->singleton(JsonExporter::class, fn () => new JsonExporter);

        $app->singleton(SanityCheckRetentionService::class, fn (): SanityCheckRetentionService => new SanityCheckRetentionService(
            retentionDays: (int) config('sanity-check.retention_days', 0),
            maxSavedRuns: (int) config('sanity-check.max_saved_runs', 0),
        ));

        $app->singleton(ResultRepositoryInterface::class, function ($app): ResultRepository {
            return new ResultRepository(
                cache: $app->make(CacheRepository::class),
                retention: $app->make(SanityCheckRetentionService::class),
            );
        });

        $app->singleton(RunOrchestrator::class, function ($app): RunOrchestrator {
            return new RunOrchestrator(
                scanner: $app->make(RouteScannerInterface::class),
                routeFilter: $app->make(RouteFilter::class),
                parameterResolution: $app->make(ParameterResolutionManager::class),
                routeTester: $app->make(RouteTesterInterface::class),
                classifier: $app->make(ResponseClassifier::class),
                aggregator: $app->make(ResultAggregator::class),
                resultRepository: $app->make(ResultRepositoryInterface::class),
                saveRuns: (bool) config('sanity-check.save_runs', true),
                ignoreUnresolvableRoutes: (bool) config('sanity-check.ignore_unresolvable_routes', true),
            );
        });
    }

    private function registerConfigServiceOverrides(): void
    {
        $bindings = (array) data_get(config('sanity-check.services'), 'bindings', []);

        foreach ($bindings as $abstract => $concrete) {
            if (! is_string($abstract) || $abstract === '' || ! is_string($concrete) || $concrete === '') {
                continue;
            }

            if (! interface_exists($abstract) && ! class_exists($abstract)) {
                throw new InvalidArgumentException(
                    "sanity-check.services.bindings: abstract [{$abstract}] is not a class or interface."
                );
            }

            if (! class_exists($concrete)) {
                throw new InvalidArgumentException(
                    "sanity-check.services.bindings: concrete [{$concrete}] for [{$abstract}] is not a class."
                );
            }

            $this->app->singleton($abstract, fn ($app) => $app->make($concrete));
        }
    }
}
