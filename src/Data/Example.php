<?php
namespace NicoAndra\OpenApiGenerator\Data;

use Spatie\LaravelData\Data;

class Example extends Data {
  
    use Trait\AttributeValueFromReflection;
    
    public function __construct(
        public string $value
    ) {}

    public function __tostring(): string
    {
        return $this->value;
    }
}