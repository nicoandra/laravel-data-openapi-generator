<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use NicoAndra\OpenApiGenerator\Test\Controller;
use Illuminate\Support\Facades\Config;

beforeAll(function () {
    
    $random = rand(1000, 9999);
    $directory = dirname(config('openapi-generator.path')) . DIRECTORY_SEPARATOR . $random;
    Config::set('openapi-generator.path', $directory . DIRECTORY_SEPARATOR . 'openapi.json');

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

afterAll(function () {
    if (File::exists(config('openapi-generator.path'))) {
        // File::delete(config('openapi-generator.path'));
    }
});
