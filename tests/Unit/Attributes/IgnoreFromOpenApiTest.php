<?php

use NicoAndra\OpenApiGenerator\Attributes\IgnoreFromOpenApi;

it('instantiates the ignore from openapi attribute', function () {
    expect(new IgnoreFromOpenApi())->toBeInstanceOf(IgnoreFromOpenApi::class);
});
