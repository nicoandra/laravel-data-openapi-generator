<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Spatie\LaravelData\Data;

class Error extends Data
{
    public function __construct(
        public string $message,
    ) {}
}
