<?php

use NicoAndra\OpenApiGenerator\Attributes\HttpResponseStatus;

it('instantiates the http status attribute', function () {
    $instance = new HttpResponseStatus(123);
    expect($instance->status)->toBe(123);
});
