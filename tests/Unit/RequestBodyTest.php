<?php

use Illuminate\Routing\Route;
use Spatie\LaravelData\Data;
use NicoAndra\OpenApiGenerator\Data\OpenApi;
use NicoAndra\OpenApiGenerator\Data\RequestBody;

it('can detect no request body', function () {
    foreach (['basic', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(RequestBody::getFirstOfClassType($method, Data::class))
            ->toBeNull();
    }
});

it('can detect request body', function () {
    foreach (['requestBasic', 'contentType', 'allCombined'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(RequestBody::getFirstOfClassType($method, Data::class))
            ->toBeInstanceOf(ReflectionNamedType::class);
    }
});

it('can create no request body', function () {
    foreach (['basic', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(RequestBody::fromRoute($method)?->toArray())
            ->toBeNull();
    }
});

it('can create data request body', function () {
    foreach (['requestBasic', 'allCombined'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(RequestBody::fromRoute($method)?->toArray())
            ->toBe([
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/PublicName.SubPackage.RequestData',
                        ],
                    ],
                ],
                'required' => true,
            ]);

        expect(OpenApi::getTempSchemas())->toMatchArray(
            ['PublicName.SubPackage.RequestData' => 'NicoAndra\\OpenApiGenerator\\Test\\RequestData']
        );
    }
});
