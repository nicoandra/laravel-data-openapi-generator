<?php

namespace NicoAndra\OpenApiGenerator\Test;

use NicoAndra\OpenApiGenerator\Data\Cast\SignedStringCastTransformer;
use Spatie\LaravelData\Attributes\WithCastAndTransformer;
use Spatie\LaravelData\Data;

class SignedStringContainerData extends Data
{
    public function __construct(
        #[WithCastAndTransformer(SignedStringCastTransformer::class)]
        public ?SignedStringData $payload,
    ) {}
}
