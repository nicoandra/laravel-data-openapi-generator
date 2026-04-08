<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Spatie\LaravelData\Data;

use ReflectionClass;

use ReflectionNamedType;
use ReflectionType;

class Parameter extends Data
{
    public function __construct(
        public string $name,
        public string $in,
        public string $description,
        public bool $required,
        public Schema $schema,
    ) {}

    /**
     * @return Collection<int,static>
     */
    public static function fromRoute(Route $route, ReflectionFunction|ReflectionMethod $method): Collection
    {
        /** @var string[] */
        $parameters = $route->parameterNames();

        return Parameter::collect(array_map(
            fn (string $parameter) => Parameter::fromParameter($parameter, $method),
            $parameters,
        ), Collection::class);
    }

    /**
     * @return ?Collection<int,static>
     */
    public static function fromRequestBody(RequestBody $requestBody): ?Collection
    {
        /*
         * GET requests cannot have request bodies
         * but we can have request objects that read out parameters from the query parameters
         * So here we convert a request body into parameters
         */
        return $requestBody->content->schema?->resolveRef()?->getObjectProperties()?->map(fn (Property $property) => new self(
            name: $property->getName(),
            description: $property->getName(),
            required: $property->required,
            schema: $property->type,
            in: 'query',
        ));
    }

    public static function fromParameter(string $name, ReflectionFunction|ReflectionMethod $method): self
    {
        /** @var null|ReflectionParameter */
        $parameter = Arr::first(
            $method->getParameters(),
            fn (ReflectionParameter $parameter) => $parameter->getName() === $name,
        );

        if($parameter) {
            return new self(
                name: $parameter->getName(),
                in: 'path',
                description: $parameter->getName(),
                required: ! $parameter->isOptional(),
                schema: Schema::fromParameterReflection($parameter),
            );
        }


        foreach ($method->getParameters() as $parameter) {
            $parameterType = $parameter->getType();

            if (
                !$parameterType ||
                !is_a($parameterType->getName(), Data::class, true)
            ) continue;

            $dataClassProperties = Property::fromDataClass($parameterType->getName());

            /** @var null|Property */
            $property = Arr::first(
                $dataClassProperties,
                fn (Property $property) => $property->getName() === $name,
            );

            if ($property) {
                return new self(
                    name: $property->getName(),
                    in: 'path',
                    description: $property->getName(),
                    required: $property->required,
                    schema: $property->type,
                );
            }
        }

        throw new Exception("Parameter {$name} not found in method {$method->getName()}, neither as a property of the associated request class");
        
    }


}
