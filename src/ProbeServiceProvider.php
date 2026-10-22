<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Isaacjuwon\Probe\Auth\AuthApplierFactory;
use Isaacjuwon\Probe\Collectors\CollectorRegistry;
use Isaacjuwon\Probe\Collectors\DumpCollector;
use Isaacjuwon\Probe\Collectors\ExceptionCollector;
use Isaacjuwon\Probe\Collectors\LogCollector;
use Isaacjuwon\Probe\Collectors\QueryCollector;
use Isaacjuwon\Probe\Console\ExportCommand;
use Isaacjuwon\Probe\Console\InstallCommand;
use Isaacjuwon\Probe\Console\ScribeSyncCommand;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Contracts\RequestRepository;
use Isaacjuwon\Probe\Enrichment\EnrichmentPipeline;
use Isaacjuwon\Probe\Relay\HostGuard;
use Isaacjuwon\Probe\Relay\HttpTransport;
use Isaacjuwon\Probe\Relay\QueryCapturingTransport;
use Isaacjuwon\Probe\Relay\TimedTransport;
use Isaacjuwon\Probe\Storage\FileRequestRepository;
use Isaacjuwon\Probe\Support\SourceManager;

final class ProbeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/probe.php',
            key: 'probe',
        );

        $this->registerSecurity();
        $this->registerCollectors();
        $this->registerRelay();
        $this->registerStorage();
        $this->registerDiscovery();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(path: __DIR__.'/../routes/probe.php');

        $this->loadViewsFrom(
            path: __DIR__.'/../resources/views',
            namespace: 'probe',
        );

        $this->bootGate();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->commands(commands: [
                InstallCommand::class,
                ScribeSyncCommand::class,
                ExportCommand::class,
            ]);
        }
    }

    private function bootGate(): void
    {
        Gate::define(
            ability: 'viewProbe',
            callback: function (mixed $user = null): bool {
                /** @var list<string> $allowed */
                $allowed = config(
                    key: 'probe.allowed_environments',
                    default: ['local', 'testing'],
                );

                return in_array(
                    needle: $this->app->environment(),
                    haystack: $allowed,
                    strict: true,
                );
            },
        );
    }

    private function registerSecurity(): void
    {
        $this->app->singleton(
            abstract: HostGuard::class,
            concrete: function (Application $app): HostGuard {
                /** @var list<string> $allowedHosts */
                $allowedHosts = config(key: 'probe.allowed_hosts', default: []);
                /** @var string|null $appUrl */
                $appUrl = config(key: 'app.url');

                return new HostGuard(
                    allowedHosts: $allowedHosts,
                    appUrl: $appUrl,
                    currentHostResolver: static function () use ($app): ?string {
                        if ($app->bound(abstract: 'request')) {
                            /** @var Request $request */
                            $request = $app->make(abstract: 'request');

                            return $request->getHost();
                        }

                        return null;
                    },
                );
            },
        );
    }

    private function registerCollectors(): void
    {
        $this->app->singleton(
            abstract: CollectorRegistry::class,
            concrete: function (): CollectorRegistry {
                return new CollectorRegistry(collectors: [
                    new QueryCollector,
                    new DumpCollector,
                    new LogCollector,
                    new ExceptionCollector,
                ]);
            },
        );
    }

    private function registerRelay(): void
    {
        $this->app->singleton(abstract: AuthApplierFactory::class);

        $this->app->bind(
            abstract: RelayTransport::class,
            concrete: function (Application $app): RelayTransport {
                $guard = $app->make(abstract: HostGuard::class);
                $collectors = $app->make(abstract: CollectorRegistry::class);

                $httpTransport = new HttpTransport(hostGuard: $guard);
                $capturingTransport = new QueryCapturingTransport(
                    transport: $httpTransport,
                    registry: $collectors,
                );

                return new TimedTransport(transport: $capturingTransport);
            },
        );
    }

    private function registerStorage(): void
    {
        $this->app->bind(
            abstract: RequestRepository::class,
            concrete: function (): RequestRepository {
                /** @var string $disk */
                $disk = config(key: 'probe.storage.disk', default: 'local');
                /** @var string $path */
                $path = config(key: 'probe.storage.path', default: 'probe');

                return new FileRequestRepository(
                    disk: $disk,
                    basePath: $path,
                );
            },
        );
    }

    private function registerDiscovery(): void
    {
        $this->app->singleton(abstract: SourceManager::class);
        $this->app->singleton(abstract: EnrichmentPipeline::class);

        $this->app->bind(
            abstract: EndpointSource::class,
            concrete: function (Application $app): EndpointSource {
                /** @var SourceManager $manager */
                $manager = $app->make(abstract: SourceManager::class);

                return $manager->driver();
            },
        );
    }

    private function registerPublishing(): void
    {
        $this->publishes(
            paths: [
                __DIR__.'/../config/probe.php' => config_path(path: 'probe.php'),
            ],
            groups: 'probe-config',
        );

        $this->publishes(
            paths: [
                __DIR__.'/../resources/views' => resource_path(path: 'views/vendor/probe'),
            ],
            groups: 'probe-views',
        );

        $this->publishes(
            paths: [
                __DIR__.'/../public' => public_path(path: 'vendor/probe'),
            ],
            groups: ['probe-assets', 'laravel-assets'],
        );
    }
}
