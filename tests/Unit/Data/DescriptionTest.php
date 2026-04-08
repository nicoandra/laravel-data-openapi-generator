<?php

use Illuminate\Routing\Route;
use Spatie\LaravelData\Data;
use NicoAndra\OpenApiGenerator\Data\Content;
use NicoAndra\OpenApiGenerator\Data\RequestBody;

use NicoAndra\OpenApiGenerator\Data\Description;


it('creates descriptions from docblock syntax', function () {
    $docComment = <<<EOT
    /**
     * This is a description.
     *
     * It has multiple lines and some tags.
     *
     * @param string \$name The name of the user.
     * @return void
     */
    EOT;

    $description = Description::fromDocComment($docComment);

    expect($description->asString())->toBe("This is a description.\nIt has multiple lines and some tags.");
});