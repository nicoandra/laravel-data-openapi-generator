<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Spatie\LaravelData\Data;

class UnionTypeData extends Data
{
    public function __construct(
        public int|string $value,
    ) {}
}
