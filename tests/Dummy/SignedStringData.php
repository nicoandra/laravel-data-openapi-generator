<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Spatie\LaravelData\Data;

class SignedStringData extends Data
{
    public function __construct(
        public string $value,
    ) {}
}
