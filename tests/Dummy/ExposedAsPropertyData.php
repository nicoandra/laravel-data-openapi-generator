<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes\ExposedAs;
use Spatie\LaravelData\Data;

class ExposedAsPropertyData extends Data
{
    public function __construct(
        #[ExposedAs('string')]
        public ReturnData $value,
    ) {}
}
