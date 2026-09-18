# Repository Notes

This file records implementation details and maintenance findings that are not covered by the public README. It is intended as a starting point for future contributors.

## Repository shape

- Package source lives under `src/` and is autoloaded as `NicoAndra\\OpenApiGenerator\\`.
- Test-only fixtures use the `NicoAndra\\OpenApiGenerator\\Test\\` namespace and live under `tests/Dummy/`.
- Laravel integration is registered by `src/OpenApiServiceProvider.php`; this package auto-registers its service provider through `composer.json`.
- The generation command is `src/Commands/GenerateOpenApiCommand.php` (`php artisan openapi:generate`).
- Generated documents are assembled by `src/Data/OpenApi.php`; route operations and schema inference are split across `src/Data/Operation.php`, `RequestBody.php`, `Parameter.php`, `Response.php`, `Schema.php`, and `Property.php`.
- Swagger UI and JSON delivery routes are defined in `src/routes/routes.php`.

## Generation behavior to keep in mind

- Route inclusion and exclusion are prefix checks. The implementation uses the route URI for `included_route_prefixes` and the route name for `ignored_route_names`; the configuration comments should not be treated as a different matching mechanism.
- Generation handles routes individually and can continue after an exception. A generated file may therefore be a partial specification if one route cannot be inspected; check command output when diagnosing missing paths.
- A request body is inferred from the first typed Laravel Data parameter on an operation.
- `GET` Data parameters become query parameters rather than a request body. Route-bound parameters are removed from the resulting query-parameter set.
- Array and `DataCollection` item types depend on sufficiently precise `@return` or `@var` annotations.
- `OpenApi::$schemas` is static and should be considered process-wide state. Repeated generation in a long-lived PHP process may retain schemas from an earlier generation unless the implementation is changed to reset them.
- The bearer security scheme is emitted in components by default, even when no route uses the corresponding configured middleware.

## Configuration and overlays

- The default OpenAPI version is `3.0.2`; the default output is `resources/api/openapi.json`.
- The default included URI prefix is `api`; `HEAD` and `OPTIONS` are ignored.
- `overlay_files` are additive. Generated values win when an overlay defines the same path, method, response status, schema, or security-scheme key. Overlays are therefore suitable for adding undocumented paths or response variants, not for overriding inferred output.
- `namespace_aliases` affect generated schema names and can prevent internal namespace details from being exposed in the public document.
- The default error schema is `NicoAndra\\OpenApiGenerator\\Data\\Error`.

## Development workflow

The repository supports both local Composer commands and Docker-backed Make targets:

```bash
composer install
composer test
composer format-dry
composer larastan
make test
```

`Makefile` targets run inside Docker, while the Composer scripts run against the current working tree. CI is defined in `.github/workflows/code-checks.yml` and currently runs PHP 8.4, dependency validation, formatting checks, and tests; the Larastan step is present in the project but commented out in CI.

Tests use Pest with PHPUnit configuration in `phpunit.xml`. Feature-level generation coverage is concentrated in `tests/Feature/OpenApiGeneratorTest.php`; lower-level behavior is organized below `tests/Unit/` by data object and attribute.

## Maintenance observations

- There is currently no repository-level OpenSpec/SDD artifact or contributor guide. For substantial changes, document the intended behavior and acceptance criteria before implementation.
- `src/Attributes/ExposedAs.php` is currently uncommitted and declares `Example`; it appears inconsistent with its filename and should be investigated before relying on it. This note is intentionally observational and does not modify that work-in-progress file.
- `src/Attributes/ReceivedAs.php` and `src/resources/jsonschema/` are also currently uncommitted additions. Treat them as in-progress work until their ownership and intended API are confirmed.
- Before changing generation behavior, add or update a focused Pest test and verify both generated paths and component schemas. Prefer a fixture under `tests/Dummy/` over coupling tests to an application outside this repository.

## First-pass scope

This document is a first-pass map, not a full API reference. It was created after inspecting the repository tree, README, Composer configuration, Makefile, package configuration, and the existing source/test layout. No source behavior was changed.
