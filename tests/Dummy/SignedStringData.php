<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Spatie\LaravelData\Data;

class SignedStringData extends Data
{
    public function __construct(
        public string $value,
        public ?SignedStringNestedData $nested = null,
    ) {}
}

class SignedStringNestedData extends Data
{
    public function __construct(
        public string $label,
        public ?string $note = null,
    ) {}
}
