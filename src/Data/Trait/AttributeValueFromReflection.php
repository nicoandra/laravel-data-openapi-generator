<?php

namespace NicoAndra\OpenApiGenerator\Data\Trait;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Spatie\LaravelData\Data;

trait AttributeValueFromReflection
{
    /**
     * @param ReflectionClass<Data>|ReflectionFunction|ReflectionMethod|ReflectionNamedType|ReflectionParameter|ReflectionProperty $reflection
     * @param string                                                                                                               $attributeClassName
     *
     * @return self
     */
    public static function fromReflectionAndAttribute(
        ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionProperty|ReflectionParameter|ReflectionNamedType $reflection,
        string $attributeClassName
    ): self {
        if ($reflection instanceof ReflectionNamedType) {
            if ($reflection->isBuiltin()) {
                return new self('');
            }
        }

        $attributes = method_exists($reflection, 'getAttributes') ? $reflection->getAttributes($attributeClassName) : [];
        if (count($attributes) > 0) {
            $attributeInstance = $attributes[0]->newInstance();
            if (! property_exists($attributeInstance, 'value')) {
                return new self('');
            }

            return new self($attributeInstance->value);
        }

        return new self('');
    }
}
