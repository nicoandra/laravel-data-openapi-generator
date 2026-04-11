# Laravel Data OpenAPI Generator

Generate an OpenAPI specification directly from Laravel routes and [Spatie Laravel Data](https://spatie.be/docs/laravel-data) classes.

The package treats your controllers and `Data` objects as the source of truth for request and response documentation, so your OpenAPI file stays close to the code that actually handles the API.

## What it does

- Generates OpenAPI paths from Laravel routes.
- Infers request bodies and response schemas from `Data` classes.
- Supports route parameters declared directly in controller methods.
- Supports route parameters injected through `#[FromRouteParameter(...)]`.
- Reads `#[Summary]`, `#[Description]`, and `#[Example]` attributes to enrich the generated spec.
- Supports custom response status codes through `#[HttpResponseStatus(...)]`.
- Supports multiple content types through `#[CustomContentType(...)]`.
- Adds security requirements and `401` / `403` responses from configured middleware.
- Allows namespace aliasing so generated schema names do not leak internal class structure.
- Exposes both a JSON endpoint and a Swagger UI page.

## Installation

Install the package:

```bash
composer require nicoandra/laravel-data-openapi-generator
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=openapi-generator-config
```

## Quick start

Use `Data` classes for your API inputs and outputs:

```php
<?php

namespace App\Data;

use NicoAndra\OpenApiGenerator\Attributes\Description;
use NicoAndra\OpenApiGenerator\Attributes\Example;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

class CreatePostData extends Data
{
    public function __construct(
        #[FromRouteParameter('author')]
        public int $authorId,
        #[Example('How to keep docs in sync with code')]
        public string $title,
        public string $body,
    ) {}
}

#[Description('Created post')]
class PostData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
    ) {}
}
```

Annotate the controller method:

```php
<?php

namespace App\Http\Controllers;

use App\Data\CreatePostData;
use App\Data\PostData;
use NicoAndra\OpenApiGenerator\Attributes\Description;
use NicoAndra\OpenApiGenerator\Attributes\Summary;

class PostController
{
    #[Summary('Create a post')]
    #[Description('Creates a post for an author and returns the stored resource')]
    public function store(CreatePostData $data): PostData
    {
        // ...
    }
}
```

Register a route:

```php
Route::post('/api/authors/{author}/posts', [PostController::class, 'store']);
```

Generate the OpenAPI file:

```bash
php artisan openapi:generate
```

By default the generated file is written to `resources/api/openapi.json`.

## Generated routes

The package registers two routes:

- `GET /api/openapi` renders Swagger UI.
- `GET /api/openapi.json` returns the generated OpenAPI JSON.

Generate the file first with:

```bash
php artisan openapi:generate
```

## Supported attributes

### `#[Summary(...)]`

Attach to a controller method to populate the OpenAPI operation summary.

### `#[Description(...)]`

Attach to a controller method to describe the operation.

Attach to a `Data` class to describe the response associated with that class.

### `#[Example(...)]`

Attach to a `Data` property or controller parameter to add example values to the generated schema or parameter.

### `#[HttpResponseStatus(...)]`

Attach to a response `Data` class to change the generated HTTP status code for that response.

```php
#[HttpResponseStatus(201)]
class CreatedPostData extends Data
{
    // ...
}
```

### `#[CustomContentType(...)]`

Attach to a `Data` class to emit request and response content under one or more custom content types.

```php
#[CustomContentType(type: ['application/json', 'application/xml'])]
class ExportData extends Data
{
    // ...
}
```

### `#[Tags(...)]`

Attach to a controller class or method to add OpenAPI tags to the generated operation.

## Configuration

The published config file lives at `config/openapi-generator.php`.

Important options:

- `openapi`: OpenAPI version to generate. Default: `3.0.2`
- `version`: API version shown in the document. Default: `1.0.0`
- `name`: API title shown in the document. Default: `OpenAPI`
- `path`: output path for the generated JSON file
- `included_route_prefixes`: only routes with these URI prefixes are documented
- `ignored_route_names`: route names to exclude
- `ignored_methods`: HTTP methods to skip, such as `HEAD` and `OPTIONS`
- `security_middlewares`: middleware-to-security-scheme mapping
- `overlay_files`: optional OpenAPI JSON files to add manual documentation gaps
- `namespace_aliases`: alias internal namespaces in generated schema names
- `error_scheme_class`: `Data` class used for generated error responses

Example namespace aliasing:

```php
'namespace_aliases' => [
    'App\\Domain\\Internal\\Api' => 'Public\\Api',
],
```

This keeps schema names stable and avoids exposing internal namespace structure in your OpenAPI document.

### Overlay spec files

Use `overlay_files` when you need manual additions that cannot be inferred from Laravel routes or `Data` classes, such as legacy endpoints, custom headers, or extra response variants.

```php
'overlay_files' => [
    resource_path('api/overlay.json'),
],
```

Overlay specs are additive. They can add:

- New `paths`.
- New methods under existing paths.
- New `responses` by status code under an existing `paths.{route}.{method}` operation.
- New `components.schemas` entries.
- New `components.securitySchemes` entries.

The generated spec is authoritative. If the generated file already has a path, method, response status code, schema, or security scheme with the same key, that generated value is kept and the overlay value is ignored for that key. For example, if both generated output and the overlay define `GET /api/users` response `200`, the generated `200` response wins; the overlay can still add `404` or other missing status codes.

## Security integration

Routes using configured auth middleware are emitted with OpenAPI security requirements.

If a route includes authorization middleware such as `can:update-posts`, the generated operation description also includes the required permissions and the package adds `403 Forbidden` responses alongside `401 Unauthorized` where appropriate.

## Notes and limitations

- Request and response inference is built around `Spatie\LaravelData\Data`.
- Array and `DataCollection` responses should have precise return docblocks so item types can be resolved.
- `GET` endpoints do not emit a request body. If a `Data` object is used there, its properties are converted into query parameters.

## Optional frontend integration

If you use `vite-plugin-pwa`, exclude `/api/` routes from the service worker:

```ts
VitePWA({
    workbox: {
        navigateFallbackDenylist: [
            new RegExp('/api/.+'),
        ],
    },
})
```

Minimal Vue page:

```vue
<route lang="json">
{
    "meta": {
        "public": true
    }
}
</route>

<template>
    <iframe
        :src="url"
        style="width: calc(100vw - 40px); height: calc(100vh - 80px); border: none;"
    />
</template>

<script lang="ts" setup>
const url = `${import.meta.env.VITE_APP_URL}/api/openapi`;
</script>
```

## Development

```bash
make install
make test
```

You can also start the container with `make dev` and run commands from there.

For test coverage, run `make test-coverage``
