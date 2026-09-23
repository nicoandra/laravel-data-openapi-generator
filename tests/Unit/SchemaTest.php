<?php

use NicoAndra\OpenApiGenerator\Data\OpenApi;
use NicoAndra\OpenApiGenerator\Data\Schema;
use NicoAndra\OpenApiGenerator\Test\ContentTypeData;
use NicoAndra\OpenApiGenerator\Test\Controller;
use NicoAndra\OpenApiGenerator\Test\ExposedAsPropertyData;
use NicoAndra\OpenApiGenerator\Test\ExposedAsStringData;
use NicoAndra\OpenApiGenerator\Test\IntEnum;
use NicoAndra\OpenApiGenerator\Test\RequestData;
use NicoAndra\OpenApiGenerator\Test\RequestDataWithIgnoredProperty;
use NicoAndra\OpenApiGenerator\Test\RequestDataWithRouteParameter;
use NicoAndra\OpenApiGenerator\Test\ReturnData;
use NicoAndra\OpenApiGenerator\Test\StringEnum;
use NicoAndra\OpenApiGenerator\Test\UnionTypeData;
use Spatie\LaravelData\DataCollection;

it('can create built-in schema', function () {
    foreach (['int' => 'integer', 'string' => 'string', 'float' => 'number', 'bool' => 'boolean'] as $type => $expected) {
        expect(Schema::fromDataReflection($type)->toArray())
            ->toBe([
                'type' => $expected,
            ]);
    }
});

it('can create array schema', function () {
    foreach (['collection', 'array'] as $function) {
        $reflection = new ReflectionMethod(Controller::class, $function);

        expect(Schema::fromDataReflection(DataCollection::class, $reflection)->toArray())
            ->toBe([
                'type'  => 'array',
                'items' => [
                    '$ref' => '#/components/schemas/PublicName.SubPackage.ReturnData',
                ],
            ]);
    }
});

it('identifies the member when a docblock is missing', function () {
    $reflection = new ReflectionMethod(Controller::class, 'arrayFail');

    expect(fn () => Schema::fromDataReflection('array', $reflection))
        ->toThrow(
            RuntimeException::class,
            sprintf(
                'Could not find required docblock of method/property %s::%s (%s:%d)',
                Controller::class,
                $reflection->getName(),
                $reflection->getFileName(),
                $reflection->getStartLine(),
            )
        );
});

it('identifies the member when a required tag is missing', function () {
    $reflection = new ReflectionMethod(SchemaDocblockFixture::class, 'withoutRequiredTag');

    expect(fn () => Schema::fromDataReflection('array', $reflection))
        ->toThrow(
            RuntimeException::class,
            sprintf(
                'Could not find required tag in docblock of method/property %s::%s (%s:%d)',
                SchemaDocblockFixture::class,
                $reflection->getName(),
                $reflection->getFileName(),
                $reflection->getStartLine(),
            )
        );
});

it('can create int enum schema', function () {
    expect(Schema::fromDataReflection(IntEnum::class)->toArray())
        ->toBe([
            'type' => 'integer',
            'enum' => [1],
        ]);
});

it('can create string enum schema', function () {
    expect(Schema::fromDataReflection(StringEnum::class)->toArray())
        ->toBe([
            'type' => 'string',
            'enum' => ['one'],
        ]);
});

it('exposes classes as strings in their schema', function () {
    expect(Schema::fromDataReflection(ExposedAsStringData::class)->toArray())
        ->toBe([
            'type' => 'string',
        ]);
});

it('exposes properties as strings in their containing schema', function () {
    expect(Schema::fromDataClass(ExposedAsPropertyData::class)->toArray())
        ->toBe([
            'type'       => 'object',
            'properties' => [
                'value' => ['type' => 'string'],
            ],
            'required' => ['value'],
        ]);
});

it('can create ref data schema', function () {
    foreach ([RequestData::class, ReturnData::class, ContentTypeData::class] as $class) {
        expect(Schema::fromDataReflection($class)->toArray())
            ->toBe([
                '$ref' => '#/components/schemas/PublicName.SubPackage.' . class_basename($class),
            ]);

        expect(OpenApi::getTempSchemas())->toMatchArray(
            ['PublicName.SubPackage.' . class_basename($class) => $class]
        );
    }
});

it('schemas with FromRouteParameter properties should ignore those properties', function () {
    $schema = Schema::fromDataClass(RequestDataWithRouteParameter::class);
    expect($schema)->toHaveProperty('type', 'object');
    expect($schema->toArray()['properties'])->toHaveLength(2);
    expect($schema->toArray()['required'])->toBe(['integer', 'string']);
});

it('schemas with ignored properties should exclude them from request properties and required fields', function () {
    $schema = Schema::fromDataClass(RequestDataWithIgnoredProperty::class)->toArray();

    expect($schema)->toBe([
        'type'       => 'object',
        'properties' => [
            'integer' => ['type' => 'integer'],
            'string'  => ['type' => 'string'],
        ],
        'required' => ['integer', 'string'],
    ]);
});

it('can create data schema', function () {
    $schema = Schema::fromDataClass(RequestData::class);
    expect($schema)->toHaveProperty('type', 'object');
    expect($schema->toArray()['properties'])->toHaveLength(15);
});

it('describes unsupported union type properties', function () {
    expect(fn () => Schema::fromDataClass(UnionTypeData::class))
        ->toThrow(
            RuntimeException::class,
            'Cannot create schema for ' . UnionTypeData::class . '::$value: encountered unsupported Spatie type Spatie\\LaravelData\\Support\\Types\\UnionType. UnionType support is not implemented yet.',
        );
});

class SchemaDocblockFixture
{
    /** This docblock intentionally has no required tag. */
    public function withoutRequiredTag(): array
    {
        return [];
    }
}
