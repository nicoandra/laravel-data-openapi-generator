<?php

namespace NicoAndra\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class IgnoreFromOpenApi {}
