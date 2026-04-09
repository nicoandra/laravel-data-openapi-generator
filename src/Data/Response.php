<?php

namespace NicoAndra\OpenApiGenerator\Data;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;
use Spatie\LaravelData\Data;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use NicoAndra\OpenApiGenerator\Attributes;

class Response extends Data
{
    public function __construct(
        public string $description,
        public Content $content,
    ) {}

    /** @return Collection<int, static> */
    public static function fromRoute(ReflectionMethod|ReflectionFunction $method): Collection
    {
        $type  = $method->getReturnType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        return collect($types)->mapWithKeys(function (ReflectionType $type) use ($method) {
            if (! $type instanceof ReflectionNamedType) {
                throw new RuntimeException('Unsupported return type: ' . $type->getName());
            }

            $description = Description::fromReflectionAndAttribute(
                new ReflectionClass($type->getName()),
                Attributes\Description::class
            );
            return [
                self::statusCodeFromType($type) => new self(
                    description: $description,
                    content: Content::fromReflection($type, $method),
                ),
            ];
        });
    }

    public static function statusCodeFromType(ReflectionNamedType $type): int
    {
        if ($type->isBuiltin()) {
            return HttpResponse::HTTP_OK;
        }

        $class      = new ReflectionClass($type->getName());
        $attributes = $class->getAttributes(Attributes\HttpResponseStatus::class);

        return count($attributes) > 0 ? $attributes[0]->getArguments()[0] : HttpResponse::HTTP_OK;
    }

    public static function unauthorized(ReflectionMethod|ReflectionFunction $method): self
    {
        return new self(
            description: 'Unauthorized',
            content: Content::fromClass(config('openapi-generator.error_scheme_class'), $method),
        );
    }

    public static function forbidden(ReflectionMethod|ReflectionFunction $method): self
    {
        return new self(
            description: 'Forbidden',
            content: Content::fromClass(config('openapi-generator.error_scheme_class'), $method),
        );
    }
}
