<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Illuminate\Support\Collection;
use NicoAndra\OpenApiGenerator\Attributes;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Data as LaravelData;

class Property extends Data
{
    public function __construct(
        protected string $name,
        public Schema $type,
        public bool $required = true,
        public bool $isFromRouteParameter = false,
        public ?string $example = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int,self>
     */
    public static function fromDataClass(string $class): Collection
    {
        if (! is_a($class, LaravelData::class, true)) {
            throw new RuntimeException('Class does not extend LaravelData');
        }

        $reflection = new ReflectionClass($class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        return self::collect(
            array_map(
                fn (ReflectionProperty $property) => self::fromProperty($property),
                $properties
            ),
            Collection::class
        );
    }

    public static function fromProperty(ReflectionProperty $reflection): self
    {
        $annotations          = $reflection->getAttributes();
        $isFromRouteParameter = false;
        foreach ($annotations as $annotation) {
            $annotationName = $annotation->getName();
            if (FromRouteParameter::class === $annotationName) {
                $isFromRouteParameter = true;

                break;
            }
        }

        $example = (string) Example::fromReflectionAndAttribute($reflection, Attributes\Example::class);

        return new self(
            name: $reflection->getName(),
            type: Schema::fromReflectionProperty($reflection),
            required: ! $reflection->getType()?->allowsNull() ?? false,
            isFromRouteParameter: $isFromRouteParameter,
            example: $example
        );
    }
}
