<?php

namespace NicoAndra\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ExposedAs
{
    public function __construct(
        /** @var string */
        public string $value
    ) {}
}
