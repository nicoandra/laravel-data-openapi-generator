<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;
use NicoAndra\OpenApiGenerator\Attributes;


class RequestDataWithRouteParameter extends Data
{
    public function __construct(
        public int $integer,
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
