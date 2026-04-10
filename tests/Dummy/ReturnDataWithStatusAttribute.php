<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes;
use Spatie\LaravelData\Data;

#[Attributes\HttpResponseStatus(420)]
#[Attributes\Description('Jamaica no problem')]
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
