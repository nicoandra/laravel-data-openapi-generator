<?php

namespace NicoAndra\OpenApiGenerator\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Describes the schema type used to represent a value in generated OpenAPI documentation.
 *
 * Although the attribute declaration permits class, property, and parameter targets,
 * generated support currently covers Data classes and Data properties only; controller
 * parameter handling is not currently implemented. It accepts only the literal schema
 * type `string` and does not change runtime casting or transformation behavior.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ExposedAs
{
    /**
     * @param string $value The schema type exposed in the generated OpenAPI representation.
     *
     * @throws InvalidArgumentException When the value is not exactly `string`.
     */
    public function __construct(
        private string $value
    ) {
        if ('string' !== $value) {
            throw new InvalidArgumentException('ExposedAs attribute only allows String');
        }
    }

    /**
     * Get the schema type exposed in the generated OpenAPI representation.
     */
    public function getExposedAs(): string
    {
        return $this->value;
    }
}
