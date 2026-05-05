<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes\IgnoreFromOpenApi;
use Spatie\LaravelData\Data;

class RequestDataWithIgnoredProperty extends Data
{
    public function __construct(
        public int $integer,
        public string $string,
        #[IgnoreFromOpenApi]
        public string $middlewareValue,
    ) {}
}
