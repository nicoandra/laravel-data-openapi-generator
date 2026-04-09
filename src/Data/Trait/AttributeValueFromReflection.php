<?php

namespace NicoAndra\OpenApiGenerator\Data\Trait;

trait AttributeValueFromReflection {
    public static function fromReflectionAndAttribute(
        \ReflectionClass|\ReflectionMethod|\ReflectionFunction $reflection,
        string $attributeClassName
    ) : self {

        $attributes = $reflection->getAttributes($attributeClassName);
        if (count($attributes) > 0) {
            $attributeInstance = $attributes[0]->newInstance();
            return new self($attributeInstance->value);
        }

        return new self('');
    }
}