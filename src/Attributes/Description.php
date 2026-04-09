<?php

namespace NicoAndra\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Description
{
    public function __construct(
        /** @var string */
        public string $value
    ) {}
}
