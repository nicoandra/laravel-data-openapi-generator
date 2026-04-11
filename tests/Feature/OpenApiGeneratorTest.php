<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use NicoAndra\OpenApiGenerator\Test\Controller;

beforeAll(function () {
    /*
    $random = rand(1000, 9999);
    $directory = dirname(config('openapi-generator.path')) . DIRECTORY_SEPARATOR . $random;
    Config::set('openapi-generator.path', $directory . DIRECTORY_SEPARATOR . 'openapi.json');
    */

    if (File::exists(config('openapi-generator.path'))) {
        File::delete(config('openapi-generator.path'));
    }

    Config::set('openapi-generator.ignored_route_names', ['authIgnored']);

    Route::prefix('api')->group(function () {
        Route::post('/{parameter_1}/{parameter_2}/{parameter_3}', [Controller::class, 'allCombined'])
            ->name('allCombined');
        Route::post('/withRouteParameter/{routeParameter}/nic', [Controller::class, 'routeWithRouteParameter'])
            ->name('routeWithRouteParameter');
        Route::post('/contentType', [Controller::class, 'contentType'])
            ->name('contentType');
        Route::get('/auth', [Controller::class, 'basic'])
            ->can('permission1')
            ->middleware('can:permission2')
            ->middleware('auth:sanctum')
            ->name('auth');
        Route::get('/authIgnored', [Controller::class, 'basic'])
            ->can('permission1')
            ->middleware('can:permission2')
            ->middleware('auth:sanctum')
            ->name('authIgnored');
    });

    Route::prefix('excludedPrefix')->group(function () {
        Route::get('/authIgnored', [Controller::class, 'basic'])
            ->can('permission1')
            ->middleware('can:permission2')
            ->middleware('auth:sanctum');
    });
});

it('can generate json', function () {
    Config::set('openapi-generator.overlay_files', []);

    Artisan::call('openapi:generate');

    expect(File::exists(config('openapi-generator.path')))->toBe(true);
    expect(File::get(config('openapi-generator.path')))->toBeJson();

    $parsed = json_decode(File::get(config('openapi-generator.path')), true);
    expect($parsed)->toHaveKey('openapi');
    expect($parsed)->toHaveKey('info');
    expect($parsed)->toHaveKey('paths');
    expect($parsed['paths'])->toHaveKey('/api/auth');
    expect($parsed['paths'])->not->toHaveKey('/api/authIgnored');
    expect($parsed['paths'])->not->toHaveKey('/excludedPrefix/authIgnored');
});

it('can generate json with overlay spec files', function () {
    $overlayFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('openapi-overlay-', true) . '.json';

    File::put($overlayFile, json_encode([
        'paths' => [
            '/api/auth' => [
                'get' => [
                    'responses' => [
                        '200' => [
                            'description' => 'Manual OK',
                        ],
                        '418' => [
                            'description' => 'Manual teapot',
                        ],
                    ],
                ],
            ],
            '/api/legacy' => [
                'get' => [
                    'responses' => [
                        '200' => [
                            'description' => 'Manual legacy OK',
                        ],
                    ],
                ],
            ],
        ],
        'components' => [
            'schemas' => [
                'ManualLegacyPayload' => [
                    'type' => 'object',
                ],
            ],
            'securitySchemes' => [
                'apiKeyAuth' => [
                    'type' => 'apiKey',
                    'in'   => 'header',
                    'name' => 'X-API-Key',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    try {
        Config::set('openapi-generator.overlay_files', [$overlayFile]);

        Artisan::call('openapi:generate');

        $parsed = json_decode(File::get(config('openapi-generator.path')), true);

        expect($parsed['paths']['/api/auth']['get']['responses']['200']['description'])->toBe('This is the response description');
        expect($parsed['paths']['/api/auth']['get']['responses']['418']['description'])->toBe('Manual teapot');
        expect($parsed['paths']['/api/legacy']['get']['responses']['200']['description'])->toBe('Manual legacy OK');
        expect($parsed['components']['schemas']['ManualLegacyPayload']['type'])->toBe('object');
        expect($parsed['components']['securitySchemes']['apiKeyAuth']['name'])->toBe('X-API-Key');
    } finally {
        Config::set('openapi-generator.overlay_files', []);
        File::delete($overlayFile);
    }
});

afterAll(function () {
    if (File::exists(config('openapi-generator.path'))) {
        // File::delete(config('openapi-generator.path'));
    }
});
