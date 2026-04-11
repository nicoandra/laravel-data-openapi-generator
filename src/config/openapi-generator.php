<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAPI version
    |--------------------------------------------------------------------------
    |
    | The version of the OpenAPI specification that you want to generate.
    |
    */
    'openapi' => '3.0.2',

    /*
    |--------------------------------------------------------------------------
    | App version
    |--------------------------------------------------------------------------
    |
    | The version of the OpenAPI specification that you want to generate.
    |
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | OpenAPI app name
    |--------------------------------------------------------------------------
    |
    | The name used in the OpenAPI specification.
    |
    */
    'name' => 'OpenAPI',

    /*
    |--------------------------------------------------------------------------
    | OpenAPI file location
    |--------------------------------------------------------------------------
    |
    | The location where the OpenAPI file should be generated.
    |
    */
    'path' => resource_path('api/openapi.json'),

    /*
    |--------------------------------------------------------------------------
    | Ignored route methods
    |--------------------------------------------------------------------------
    |
    | The methods that should be ignored when generating the OpenAPI file.
    |
    */
    'ignored_methods' => [
        'head',
        'options',
    ],

    /*
    |--------------------------------------------------------------------------
    | Included routes
    |--------------------------------------------------------------------------
    |
    | The routes that should be included when generating the OpenAPI file.
    | Uses the Route::getPrefix method to determine.
    |
    */
    'included_route_prefixes' => [
        'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded routes
    |--------------------------------------------------------------------------
    |
    | The routes that should be excluded when generating the OpenAPI file.
    | Uses a str_starts_with comparison with the Route::getName method to determine.
    |
    */
    'ignored_route_names' => [
        'api.openapi.',
        'api.not_found',
    ],

    'security_middlewares' => [
        \NicoAndra\OpenApiGenerator\Data\SecurityScheme::BEARER_SECURITY_SCHEME => ['auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Overlay spec files
    |--------------------------------------------------------------------------
    | Additional OpenAPI JSON files to overlay into the generated spec.
    |
    | Overlay specs can add paths, components.schemas, components.securitySchemes,
    | and add new response status codes to existing generated path methods.
    | The generated spec is authoritative: when an overlay defines a key that
    | already exists in generated output, the generated value is kept.
    */
    'overlay_files' => [
        // resource_path('api/overlay.json'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Namespace aliases
    |--------------------------------------------------------------------------
    | The namespace sections that should be aliased when generating the OpenAPI file.
    | This is used to shorten the namespace of the generated schemas.
    | Also used to avoid disclosing internal namespaces in the generated OpenAPI file.
    | The key is the namespace section to be replaced, and the value is the alias to replace it with.
    | When replacing, the application will ensure the alias and the replacement end with \ (backslash)
    | to avoid partial replacements
    |
    */
    'namespace_aliases' => [
        // 'App\MySecretCodeNamePackageName\Requests' => 'PublicName\Requests'
        'NicoAndra\\OpenApiGenerator\\Test' => 'PublicName\\SubPackage',
    ],
    /*
    |--------------------------------------------------------------------------
    | Error scheme class
    |--------------------------------------------------------------------------
    |
    | Data class used to create the error scheme.
    |
    */
    'error_scheme_class' => \NicoAndra\OpenApiGenerator\Data\Error::class,
];
