<?php

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use NicoAndra\OpenApiGenerator\Data\Operation;
use NicoAndra\OpenApiGenerator\Data\RequestBody;
use NicoAndra\OpenApiGenerator\Data\Response;
use NicoAndra\OpenApiGenerator\Test\Controller;

it('can create operation without parameters', function () {
    foreach (['basic', 'array', 'collection', 'requestBasic', 'requestNoData', 'contentType'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->parameters)
            ->toBeNull('');
    }
});

it('can create operation with single parameter', function () {
    foreach (['intParameter', 'stringParameter', 'modelParameter'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/{parameter}', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->parameters)
            ->toHaveLength(1);
    }
});
it('can create operation with multiple parameters', function () {
    $method = 'post';
    $route  = new Route($method, '/{parameter_1}/{parameter_2}/{parameter_3}', [Controller::class, 'allCombined']);
    $route->setContainer(app());

    $operation = Operation::fromRoute($route, $method);

    expect($operation->parameters)
        ->toHaveLength(3);
});

it('can create operation with description', function () {
    $method = 'post';
    $route  = new Route($method, '/{parameter_1}/{parameter_2}/{parameter_3}', [Controller::class, 'allCombined']);
    $route->setContainer(app());

    $operation = Operation::fromRoute($route, $method);

    expect($operation->description)
        ->toContain('Summary of allCombined');

    expect($operation->parameters)
        ->toHaveLength(3);
});

it('can create operation without request body', function () {
    foreach (['basic', 'array', 'collection', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->requestBody)
            ->toBeNull();
    }
});
it('can create operation with request body', function () {
    foreach (['requestBasic', 'allCombined', 'contentType'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->requestBody)
            ->toBeInstanceOf(RequestBody::class);
    }
});

it('includes summary in request', function () {
    foreach (['requestBasic'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->summary)
            ->toBe('This is a summary');

        expect($operation->requestBody)
            ->toBeInstanceOf(RequestBody::class);
    }
});

it('includes description in request', function () {
    foreach (['multiResponse'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->description)
            ->toBe('This is the multiResponse description');

        expect($operation->responses)
            ->toHaveLength(2);

        expect($operation->responses[200]->description)
            ->toBe('This is the response description');

        expect($operation->responses[420]->description)
            ->toBe('Jamaica no problem');
    }
});

it('can create operation with response', function () {
    foreach (['basic', 'array', 'collection', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData', 'requestBasic', 'allCombined', 'contentType'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->responses)
            ->toBeInstanceOf(Collection::class);

        foreach ($operation->responses->all() as $status_code => $response) {
            expect($status_code)
                ->toBe(HttpResponse::HTTP_OK);
            expect($response)
                ->toBeInstanceOf(Response::class);
        }
    }
});
it('can create operation without security', function () {
    foreach (['basic', 'array', 'collection', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData', 'requestBasic', 'allCombined', 'contentType'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->security)
            ->toBeNull();
    }
});
it('can create operation with security', function () {
    foreach (['basic', 'array', 'collection', 'intParameter', 'stringParameter', 'modelParameter', 'requestNoData', 'requestBasic', 'allCombined', 'contentType'] as $function) {
        $method = 'post';
        $route  = new Route($method, '/', [Controller::class, $function]);
        $route->middleware('auth:sanctum');
        $route->setContainer(app());

        $operation = Operation::fromRoute($route, $method);

        expect($operation->security)
            ->toHaveLength(1);
    }
});
it('can create operation without description', function () {
    $method = 'post';
    $route  = new Route($method, '/', [Controller::class, 'basic']);
    $route->setContainer(app());

    $operation = Operation::fromRoute($route, $method);

    expect($operation->description)
        ->toBe('');
});
it('can create operation with permissions description', function () {
    $method = 'post';
    $route  = new Route($method, '/', [Controller::class, 'basic']);
    $route->middleware('can:permission1');
    $route->middleware('auth:sanctum');
    $route->setContainer(app());

    expect(Operation::fromRoute($route, $method)->description)
        ->toBe('Permissions needed: permission1');

    $route->middleware('can:permission2');

    $operation = Operation::fromRoute($route, $method);
    expect($operation->description)
        ->toBe('Permissions needed: permission1, permission2');

    $status_codes = array_keys($operation->responses->all());

    expect($status_codes)
        ->toBe([
            HttpResponse::HTTP_OK,
            HttpResponse::HTTP_UNAUTHORIZED,
            HttpResponse::HTTP_FORBIDDEN,
        ]);

    foreach ($operation->responses->all() as $status_code => $response) {
        expect($response)
            ->toBeInstanceOf(Response::class);
    }
});

it('can create operation with route parameters', function () {
    $method = 'post';
    $route  = new Route($method, '/{routeParameter}', [Controller::class, 'routeWithRouteParameter']);
    $route->setContainer(app());

    $operation = Operation::fromRoute($route, $method);

    expect($operation->parameters)->toHaveLength(1);
    $parameter = $operation->parameters->first();
    expect($parameter->name)->toBe('routeParameter');
    expect($parameter->in)->toBe('path');
    expect($parameter->required)->toBeTrue();
});
