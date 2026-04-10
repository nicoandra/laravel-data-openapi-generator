<?php

use Illuminate\Routing\Route;
use NicoAndra\OpenApiGenerator\Data\OpenApi;
use NicoAndra\OpenApiGenerator\Data\Response;
use NicoAndra\OpenApiGenerator\Test\Controller;

it('can create data response', function () {
    foreach (['basic', 'intParameter', 'stringParameter', 'modelParameter', 'requestBasic', 'allCombined'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(Response::fromRoute($method)->toArray())
            ->toBe([
                200 => [
                    'description' => 'This is the response description',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnData',
                            ],
                        ],
                    ],
                ],
            ], "Failed on function $function");
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['PublicName.SubPackage.ReturnData' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnData']
    );
});



it('can create data response with multiple response return types', function () {
    foreach (['multiResponse'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(Response::fromRoute($method)->toArray())
            ->toBe([
                200 => [
                    'description' => 'This is the response description',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnData',
                            ],
                        ],
                    ],
                ],
                420 => [
                    'description' => 'Jamaica no problem',
                    'content' =>  [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnDataWithStatusAttribute',
                            ],
                        ],
                    ],
                ],                
            ], "Failed on function $function");
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['PublicName.SubPackage.ReturnData' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnData']
    );
});


it('understands status response attribute', function () {
    foreach (['routeWithStatusAttribute'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(Response::fromRoute($method)->toArray())
            ->toBe([
                420 => [
                    'description' => 'Jamaica no problem',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnDataWithStatusAttribute',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['PublicName.SubPackage.ReturnDataWithStatusAttribute' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnDataWithStatusAttribute']
    );
});

it('can create collection response', function () {
    foreach (['array', 'collection'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(Response::fromRoute($method)->toArray())
            ->toBe([200 => [
                'description' => '',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnData',
                            ],
                        ],
                    ],
                ],
            ]]);
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['PublicName.SubPackage.ReturnData' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnData']
    );
});


it('cannot create incomplete collection response', function () {
    foreach (['arrayIncompletePath', 'arrayFail', 'collectionIncompletePath', 'collectionFail'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(fn () => Response::fromRoute($method)->toArray())
            ->toThrow(RuntimeException::class);
    }
})->skip('not sure these should fail');
