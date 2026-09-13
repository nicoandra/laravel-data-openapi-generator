<?php

use NicoAndra\OpenApiGenerator\Data\Cast\SignedStringCastTransformer;
use NicoAndra\OpenApiGenerator\SignedString\SignedStringCodec;
use NicoAndra\OpenApiGenerator\Test\SignedStringContainerData;
use NicoAndra\OpenApiGenerator\Test\SignedStringData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;

beforeEach(function () {
    config()->set('openapi-generator.signed_string', [
        'key_ring'      => ['test' => str_repeat('s', 32)],
        'active_key_id' => 'test',
    ]);
});

it('round trips a nested data object through the attribute', function () {
    $payload = new SignedStringData('Ada', new \NicoAndra\OpenApiGenerator\Test\SignedStringNestedData('profile', null));
    $token   = (new SignedStringCodec(['test' => str_repeat('s', 32)], 'test'))->encode($payload);

    $container = SignedStringContainerData::from(['payload' => $token]);

    expect($container->payload->toArray())->toBe($payload->toArray())
        ->and($container->transform()['payload'])->toBe($token);
});

it('performs deterministic repeated transformations and optional nested values', function () {
    $payload   = new SignedStringData('Ada', new \NicoAndra\OpenApiGenerator\Test\SignedStringNestedData('profile', null));
    $container = new SignedStringContainerData($payload);
    $token     = $container->transform()['payload'];
    $codec     = new SignedStringCodec(['test' => str_repeat('s', 32)], 'test');

    expect($container->payload)->toBe($payload)
        ->and($codec->decode($token))->toEqual($payload->toArray())
        ->and($token)->toBe($container->transform()['payload']);
});

it('preserves nullable values', function () {
    $container = SignedStringContainerData::from(['payload' => null]);

    expect($container->payload)->toBeNull()
        ->and($container->transform()['payload'])->toBeNull();
});

it('delegates hydration and transformation to Laravel Data and the codec', function () {
    $codec    = new SignedStringCodec(['test' => str_repeat('s', 32)], 'test');
    $value    = new SignedStringData('Ada');
    $token    = $codec->encode($value);
    $adapter  = new SignedStringCastTransformer($codec);
    $property = propertyFor(SignedStringContainerData::class);

    expect($adapter->transform($property, $value, new TransformationContext()))->toBe($token)
        ->and(SignedStringContainerData::from(['payload' => $token])->payload->toArray())->toBe($value->toArray());
});

it('rejects non-string cast input', function () {
    expect(fn () => SignedStringContainerData::from(['payload' => ['value' => 'Ada']]))
        ->toThrow(InvalidArgumentException::class, 'Signed string input must be a string or null.');
});

it('rejects arrays, objects, and scalar non-strings before hydration', function (mixed $input) {
    expect(fn () => SignedStringContainerData::from(['payload' => $input]))
        ->toThrow(InvalidArgumentException::class, 'Signed string input must be a string or null.');
})->with([
    'array'   => [['value' => 'Ada']],
    'object'  => [(object) ['value' => 'Ada']],
    'integer' => [42],
    'float'   => [42.5],
    'boolean' => [true],
]);

it('rejects non-Data values during transformation', function () {
    $adapter  = new SignedStringCastTransformer();
    $property = propertyFor(SignedStringContainerData::class);

    expect(fn () => $adapter->transform($property, ['value' => 'Ada'], new TransformationContext()))
        ->toThrow(InvalidArgumentException::class, 'Signed string value must be a Laravel Data object or null.');
});

it('verifies before hydrating the target data', function () {
    expect(fn () => SignedStringContainerData::from(['payload' => 'not-a-token']))
        ->toThrow(RuntimeException::class, 'Invalid signed string');
});

it('rejects an incompatible target', function () {
    expect(fn () => IncompatibleContainerData::from(['payload' => 'anything']))
        ->toThrow(InvalidArgumentException::class, 'Signed string target must be a class that extends Spatie\\LaravelData\\Data.');
});

function propertyFor(string $class): DataProperty
{
    return app(Spatie\LaravelData\Support\DataConfig::class)
        ->getDataClass($class)
        ->properties
        ->first();
}

class IncompatibleContainerData extends Data
{
    public function __construct(
        #[\Spatie\LaravelData\Attributes\WithCastAndTransformer(SignedStringCastTransformer::class)]
        public string $payload,
    ) {}
}
