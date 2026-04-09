<?php

namespace NicoAndra\OpenApiGenerator\Test;
use NicoAndra\OpenApiGenerator\Attributes\HttpResponseStatus;

use Spatie\LaravelData\Data;

#[HttpResponseStatus(420)]
class ReturnDataWithStatusAttribute extends Data
{
    public function __construct(
        public string $message = 'test',
    ) {}

    public static function create(mixed ...$parameters): self
    {
        return new self();
    }
}
