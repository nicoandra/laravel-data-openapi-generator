<?php

namespace NicoAndra\OpenApiGenerator\Test;

use Illuminate\Routing\Controller as LaravelController;
use NicoAndra\OpenApiGenerator\Attributes\IgnoreFromOpenApi;

#[IgnoreFromOpenApi]
class IgnoredController extends LaravelController
{
    public function basic(): ReturnData
    {
        return new ReturnData();
    }
}
