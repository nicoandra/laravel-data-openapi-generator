<?php

use NicoAndra\OpenApiGenerator\Attributes\Tags;

it('instantiates the http status attribute', function () {
    $instance = new Tags(['tag1', 'tag2']);
    expect($instance->tags)->toBe(['tag1', 'tag2']);
});