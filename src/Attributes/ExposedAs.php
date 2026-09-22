<?php

namespace NicoAndra\OpenApiGenerator\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ExposedAs
{
    public function __construct(
        /** @var string */
        private string $value
    ) {
        if ('string' !== $value) {
            throw new InvalidArgumentException('ExposedAs attribute only allows String');
        }
    }

    public function getExposedAs(): string
    {
        return $this->value;
    }
}
