<?php

use NicoAndra\OpenApiGenerator\Data\Error;

it('creates error responses', function () {
    $error = new Error(message: 'My message');

    expect($error->message)->toBe('My message');
});
