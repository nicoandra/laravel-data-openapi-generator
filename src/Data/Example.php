<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Spatie\LaravelData\Data;

/**
 * Shows an example of a payload.
 */
class Example extends Data
{
    use Trait\AttributeValueFromReflection;

    public function __construct(
        public string $value
    ) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
