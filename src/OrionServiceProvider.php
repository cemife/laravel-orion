<?php

namespace Orion;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Orion\Commands\BuildSpecsCommand;
use Orion\Contracts\ComponentsResolver;
use Orion\Contracts\KeyResolver;
use Orion\Contracts\Paginator;
use Orion\Contracts\ParamsValidator;
use Orion\Contracts\QueryBuilder;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchBuilder;
use Orion\Contracts\StoredSearchRepository;
use Orion\Http\Controllers\Controller;
use Orion\Http\Controllers\RelationController;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Specs\ResourcesCacheStore;

class OrionServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('orion', Orion::class);
        $this->app->bind(QueryBuilder::class, Drivers\Standard\QueryBuilder::class);
        $this->app->bind(RelationsResolver::class, Drivers\Standard\RelationsResolver::class);
        $this->app->bind(ParamsValidator::class, Drivers\Standard\ParamsValidator::class);
        $this->app->bind(Paginator::class, Drivers\Standard\Paginator::class);
        $this->app->bind(SearchBuilder::class, Drivers\Standard\SearchBuilder::class);
        $this->app->bind(StoredSearchRepository::class, Drivers\Standard\StoredSearchRepositoryManager::class);
        $this->app->bind(ComponentsResolver::class, Drivers\Standard\ComponentsResolver::class);
        $this->app->bind(KeyResolver::class, Drivers\Standard\KeyResolver::class);

        $this->app->singleton(ResourcesCacheStore::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        app('router')->pushMiddlewareToGroup('api', EnforceExpectsJson::class);

        $this->publishes(
            [
                __DIR__ . '/../config/orion.php' => config_path('orion.php'),
            ],
            'orion-config'
        );

        $this->publishes(
            [
                __DIR__ . '/../database/migrations/create_orion_search_links_table.php.stub' => database_path(
                    'migrations/'.date('Y_m_d_His').'_create_orion_search_links_table.php'
                ),
            ],
            'orion-search-links-migration'
        );

        $this->mergeConfigFrom(__DIR__ . '/../config/orion.php', 'orion');

        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    BuildSpecsCommand::class,
                ]
            );
        }

        if (config('orion.route_discovery.enabled', false)) {
            $this->discoverAndRegisterControllers();
        }
    }

    protected function discoverAndRegisterControllers(): void
    {
        $paths = config('orion.route_discovery.paths', []);

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $namespace = $this->pathToNamespace($path);

            foreach (File::allFiles($path) as $file) {
                $class = $namespace . '\\' . str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $file->getRelativePathname()
                    );

                if (
                    class_exists($class) &&
                    (
                        is_subclass_of($class, Controller::class) ||
                        is_subclass_of($class, RelationController::class)
                    )
                ) {
                    $instance = app($class);

                    if (method_exists($instance, 'routeDiscoveryEnabled') && !$instance->routeDiscoveryEnabled()) {
                        continue;
                    }

                    $class::registerRoutes();
                }
            }
        }
    }

    protected function pathToNamespace(string $path): string
    {
        return Str::of($path)
            ->after(base_path('app'))
            ->replace('/', '\\')
            ->prepend('App')
            ->__toString();
    }
}
