# OpenAPI Generator using Laravel Data

Generate OpenAPI specification from Laravel routes and Laravel Data objects.

Additional features:
* Leverage `Summary` and `Description` annotations to add extra documentation to your OpenAPI Spec.
* Allows using route parameters, interpreting the `FromRouteParameter` annotation used by [Laravel-Data](https://spatie.be/docs/laravel-data/v4/as-a-data-transfer-object/injecting-property-values#content-using-scalar-route-parameters)


## Install

`composer require nicoandra/laravel-data-openapi-generator`


## Usage

1. Make your application to expect and return `Data` classes as requests and responses.
2. Use the attributes `Summary` and `Description` to provide human descriptions to your routes:

```
#[Description('This describes the route')]
#[Summary('Summary')]
class MyRequest extends Data {
    [...]
}

class MyResponse extends Data {
    [...]
}

```



# Optional

## Version

Add a `app.version` config in `app.php` to set the version in the openapi specification:
```php
    'version' => env('APP_VERSION', '1.0.0'),
```

## Vite PWA config

If using `vite-plugin-pwa`, make sure to exclude '/api/' routes from the serviceworker using this config:

```ts
VitePWA({
    workbox: {
        navigateFallbackDenylist: [
            new RegExp('/api/.+'),
        ],
    },
})
```

## Vue page

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
        style="width: calc(100vw - 40px);height: calc(100vh - 80px); border: none;"
    />
</template>

<script lang="ts" setup>
const url = `${import.meta.env.VITE_APP_URL}/api/openapi`;
</script>
```

# Usage

## Config

`php artisan vendor:publish --tag=openapi-generator-config`

## Generate

`php artisan openapi:generate`

## View

Swagger available at `APP_URL/api/openapi`


# Development

1. Run `make install` to install all dependencies
2. Run `make test` to run tests