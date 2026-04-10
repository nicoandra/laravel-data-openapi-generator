<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Spatie\LaravelData\Data;
use NicoAndra\OpenApiGenerator\Attributes\CustomContentType;
use NicoAndra\OpenApiGenerator\Attributes\Example;

#[CustomContentType(type: ['application/json', 'application/xml'])]
class ContentTypeData extends Data
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
