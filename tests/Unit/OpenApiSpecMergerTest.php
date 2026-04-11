<?php

use NicoAndra\OpenApiGenerator\OpenApiSpecMerger;

it('merges overlay spec additions without overriding generated content', function () {
    $generated = [
        'paths' => [
            '/api/users' => [
                'get' => [
                    'summary'   => 'Generated users list',
                    'responses' => [
                        '200' => ['description' => 'Generated OK'],
                    ],
                ],
            ],
        ],
        'components' => [
            'schemas' => [
                'User' => ['type' => 'object'],
            ],
            'securitySchemes' => [
                'bearerAuth' => [
                    'type'   => 'http',
                    'scheme' => 'bearer',
                ],
            ],
        ],
    ];

    $overlay = [
        'paths' => [
            '/api/users' => [
                'get' => [
                    'summary'   => 'Manual users list',
                    'responses' => [
                        '200' => ['description' => 'Manual OK'],
                        '404' => ['description' => 'Manual not found'],
                    ],
                ],
                'post' => [
                    'responses' => [
                        '201' => ['description' => 'Manual created'],
                    ],
                ],
            ],
            '/api/legacy' => [
                'get' => [
                    'responses' => [
                        '200' => ['description' => 'Manual legacy OK'],
                    ],
                ],
            ],
        ],
        'components' => [
            'schemas' => [
                'User'       => ['type' => 'string'],
                'LegacyUser' => ['type' => 'object'],
            ],
            'securitySchemes' => [
                'bearerAuth' => ['type' => 'apiKey'],
                'apiKeyAuth' => [
                    'type' => 'apiKey',
                    'in'   => 'header',
                    'name' => 'X-API-Key',
                ],
            ],
        ],
    ];

    $merged = (new OpenApiSpecMerger())->merge($generated, $overlay);

    expect($merged['paths']['/api/users']['get']['summary'])->toBe('Generated users list');
    expect($merged['paths']['/api/users']['get']['responses']['200']['description'])->toBe('Generated OK');
    expect($merged['paths']['/api/users']['get']['responses']['404']['description'])->toBe('Manual not found');
    expect($merged['paths']['/api/users']['post']['responses']['201']['description'])->toBe('Manual created');
    expect($merged['paths']['/api/legacy']['get']['responses']['200']['description'])->toBe('Manual legacy OK');

    expect($merged['components']['schemas']['User']['type'])->toBe('object');
    expect($merged['components']['schemas']['LegacyUser']['type'])->toBe('object');
    expect($merged['components']['securitySchemes']['bearerAuth']['type'])->toBe('http');
    expect($merged['components']['securitySchemes']['apiKeyAuth']['name'])->toBe('X-API-Key');
});
