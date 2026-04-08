<?php

use NicoAndra\OpenApiGenerator\Data\Info;

it('can create info', function () {
    expect(Info::create()->toArray())
        ->toBe([
            'title'   => 'OpenAPI',
            'version' => '1.0.0',
        ]);
});
