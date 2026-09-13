<?php

use NicoAndra\OpenApiGenerator\Data\Cast\SignedStringCastTransformer;
use NicoAndra\OpenApiGenerator\Data\OpenApi;
use NicoAndra\OpenApiGenerator\Data\Schema;
use NicoAndra\OpenApiGenerator\Test\ContentTypeData;
use NicoAndra\OpenApiGenerator\Test\Controller;
use NicoAndra\OpenApiGenerator\Test\IntEnum;
use NicoAndra\OpenApiGenerator\Test\RequestData;
use NicoAndra\OpenApiGenerator\Test\RequestDataWithIgnoredProperty;
use NicoAndra\OpenApiGenerator\Test\RequestDataWithRouteParameter;
use NicoAndra\OpenApiGenerator\Test\ReturnData;
use NicoAndra\OpenApiGenerator\Test\SignedStringContainerData;
use NicoAndra\OpenApiGenerator\Test\StringEnum;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
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

it('represents paired signed string casts as strings', function () {
    $schema = Schema::fromDataClass(SignedStringContainerData::class)->toArray();

    expect($schema['properties']['payload'])->toBe([
        'type'     => 'string',
        'nullable' => true,
    ]);
});

it('rejects a signed string cast without its transformer', function () {
    expect(fn () => Schema::fromDataClass(SignedStringCastOnlyData::class))
        ->toThrow(RuntimeException::class, 'Attribute SignedStringCastOnlyData::payload requires SignedStringCastTransformer');
});

it('can create data schema', function () {
    $schema = Schema::fromDataClass(RequestData::class);
    expect($schema)->toHaveProperty('type', 'object');
    expect($schema->toArray()['properties'])->toHaveLength(13);
});

class SignedStringCastOnlyData extends Data
{
    public function __construct(
        #[WithCast(SignedStringCastTransformer::class)]
        public ?\NicoAndra\OpenApiGenerator\Test\SignedStringData $payload,
    ) {}
}
