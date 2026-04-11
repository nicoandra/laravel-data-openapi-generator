<?php

namespace NicoAndra\OpenApiGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as FacadeRoute;
use NicoAndra\OpenApiGenerator\Data\OpenApi;
use NicoAndra\OpenApiGenerator\OpenApiSpecMerger;

class GenerateOpenApiCommand extends Command
{
    protected $signature   = 'openapi:generate';
    protected $description = 'Generates the OpenAPI documentation';

    public function handle(): int
    {
        $openapi = OpenApi::fromRoutes($this->getRoutes(), $this);
        $openapi = app(OpenApiSpecMerger::class)->mergeOverlayFiles(
            $openapi->toArray(),
            (array) config('openapi-generator.overlay_files', [])
        );

        $location  = config('openapi-generator.path');
        $directory = dirname($location);

        if (! File::isDirectory($directory)) {
            File::makeDirectory(
                path: dirname($location),
                recursive: true,
            );
        }

        File::put(
            $location,
            json_encode($openapi, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        $this->info("OpenAPI documentation generated at {$location}");

        return Command::SUCCESS;
    }

    /**
     * @return array<string,array<string,Route>>
     */
    protected function getRoutes(): array
    {
        $registeredRoutes      = FacadeRoute::getRoutes()->getRoutes();
        $includedRoutePrefixes = config('openapi-generator.included_route_prefixes', []);
        $ignoredRouteNames     = config('openapi-generator.ignored_route_names', []);

        /** @var array<string,array<string,Route>> */
        $routes = [];

        /** @var array<int,Route> */
        $initial_routes = array_values(array_filter(
            $registeredRoutes,
            function (Route $route) use ($includedRoutePrefixes, $ignoredRouteNames) {
                $uri  = $route->uri;
                $name = $route->getName() ?? '';
                if (! $this->strStartsWith($uri, $includedRoutePrefixes)) {
                    Log::info("Skipping route {$name} {$uri}, it does not start with any of the included prefixes");

                    return false;
                }
                if ($this->strStartsWith($name, $ignoredRouteNames)) {
                    Log::info("Skipping route {$name} {$uri}, its name starts with one of the ignored names");

                    return false;
                }

                return true;
            }
        ));

        foreach ($initial_routes as $route) {
            $uri = '/' . $route->uri;

            if (! key_exists($uri, $routes)) {
                $routes[$uri] = [];
            }

            /** @var string $method */
            foreach ($route->methods as $method) {
                $method = strtolower($method);
                if (in_array($method, config('openapi-generator.ignored_methods', []), true)) {
                    continue;
                }

                $this->info("Found route {$method} {$route->getName()} {$uri}");

                $routes[$uri][$method] = $route;
            }
        }

        return $routes;
    }

    /**
     * @param string|string[] $needles
     */
    protected function strStartsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' !== (string) $needle && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
