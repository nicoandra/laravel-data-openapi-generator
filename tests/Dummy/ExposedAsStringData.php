<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Attributes\Description;
use NicoAndra\OpenApiGenerator\Attributes\Example;
use NicoAndra\OpenApiGenerator\Attributes\ExposedAs;
use NicoAndra\OpenApiGenerator\Attributes\Summary;
use Spatie\LaravelData\Data;

#[Summary('Exposed as astring')]
#[Description('Only shown as string, values are not visible')]
#[ExposedAs('string')]
class ExposedAsStringData extends Data
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
