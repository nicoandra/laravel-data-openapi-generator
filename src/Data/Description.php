<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Spatie\LaravelData\Data;

class Description extends Data
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
