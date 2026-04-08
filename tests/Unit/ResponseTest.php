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
                    'description' => $function,
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ReturnData',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['ReturnData' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnData']
    );
});

it('can create collection response', function () {
    foreach (['array', 'collection'] as $function) {
        $route  = new Route('get', '/', [Controller::class, $function]);
        $method = methodFromRoute($route);

        expect(Response::fromRoute($method)->toArray())
            ->toBe([200 => [
                'description' => $function,
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/ReturnData',
                            ],
                        ],
                    ],
                ],
            ]]);
    }

    expect(OpenApi::getTempSchemas())->toMatchArray(
        ['ReturnData' => 'NicoAndra\\OpenApiGenerator\\Test\\ReturnData']
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
