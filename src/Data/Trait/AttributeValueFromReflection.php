<?php

namespace NicoAndra\OpenApiGenerator\Data\Trait;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

trait AttributeValueFromReflection
{
    public static function fromReflectionAndAttribute(
        ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionProperty|ReflectionParameter|ReflectionNamedType $reflection,
        string $attributeClassName
    ): self {
        if ($reflection instanceof ReflectionNamedType) {
            if ($reflection->isBuiltin()) {
                return new self('');
            }
        }

        $attributes = $reflection->getAttributes($attributeClassName);
        if (count($attributes) > 0) {
            $attributeInstance = $attributes[0]->newInstance();

            return new self($attributeInstance->value);
        }

        return new self('');
    }
}
