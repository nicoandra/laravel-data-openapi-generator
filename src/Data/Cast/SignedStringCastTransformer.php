<?php

namespace NicoAndra\OpenApiGenerator\Data\Cast;

use InvalidArgumentException;
use NicoAndra\OpenApiGenerator\SignedString\SignedStringCodec;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

/**
 * Casts signed strings to and from a nested Laravel Data object.
 */
class SignedStringCastTransformer implements Cast, Transformer
{
    public function __construct(private ?SignedStringCodec $codec = null)
    {
        $this->codec ??= new SignedStringCodec();
    }

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        $target = $this->targetClass($property);

        if (null === $value) {
            return null;
        }
        if (! is_string($value)) {
            throw new InvalidArgumentException('Signed string input must be a string or null.');
        }

        // decode verifies the signature and envelope before Data::from is called.
        return $target::from($this->codec->decode($value));
    }

    public function transform(DataProperty $property, mixed $value, TransformationContext $context): mixed
    {
        $this->targetClass($property);

        if (null === $value) {
            return null;
        }
        if (! $value instanceof Data) {
            throw new InvalidArgumentException('Signed string value must be a Laravel Data object or null.');
        }

        return $this->codec->encode($value);
    }

    /** @return class-string<Data> */
    private function targetClass(DataProperty $property): string
    {
        $target = $property->type->dataClass;

        if (null === $target || ! is_a($target, Data::class, true)) {
            throw new InvalidArgumentException(
                'Signed string target must be a class that extends Spatie\\LaravelData\\Data.'
            );
        }

        return $target;
    }
}
