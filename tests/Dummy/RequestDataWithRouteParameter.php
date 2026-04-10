<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

class RequestDataWithRouteParameter extends Data
{
    public function __construct(
        public int $integer,
        #[Attributes\Example('the string example')]
        public string $string,
        #[FromRouteParameter('routeParameter')]
        public string $routeParameter,
    ) {}

    public static function create(): self
    {
        return new self(
            integer: 1,
            string: 'string',
            routeParameter: 'routeParameter',
        );
    }
}
