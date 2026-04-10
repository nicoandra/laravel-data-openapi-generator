<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes\Description;
use NicoAndra\OpenApiGenerator\Attributes\Example;
use NicoAndra\OpenApiGenerator\Attributes\Summary;
use Spatie\LaravelData\Data;

#[Summary('This is the response summary')]
#[Description('This is the response description')]
class ReturnData extends Data
{
    public function __construct(
        #[Example('an example string')]
        public string $message = 'test',
    ) {}

    public static function create(mixed ...$parameters): self
    {
        return new self();
    }
}
