<?php

namespace NicoAndra\OpenApiGenerator;

use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

class OpenApiSpecMerger
{
    /**
     * @param array<string,mixed> $generated
     * @param array<int,string>   $overlayFiles
     *
     * @return array<string,mixed>
     */
    public function mergeOverlayFiles(array $generated, array $overlayFiles): array
    {
        foreach ($overlayFiles as $overlayFile) {
            $generated = $this->merge($generated, $this->readOverlayFile($overlayFile));
        }

        return $generated;
    }

    /**
     * @param array<string,mixed> $generated
     * @param array<string,mixed> $overlay
     *
     * @return array<string,mixed>
     */
    public function merge(array $generated, array $overlay): array
    {
        return $this->mergeComponents(
            $this->mergePaths($generated, $overlay),
            $overlay
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function readOverlayFile(string $overlayFile): array
    {
        if (! File::exists($overlayFile)) {
            throw new RuntimeException("OpenAPI overlay spec file does not exist: {$overlayFile}");
        }

        try {
            /** @var array<string,mixed> $overlay */
            $overlay = json_decode(File::get($overlayFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "OpenAPI overlay spec file is not valid JSON: {$overlayFile}",
                previous: $exception
            );
        }

        if (! is_array($overlay)) {
            throw new RuntimeException("OpenAPI overlay spec file must contain a JSON object: {$overlayFile}");
        }

        return $overlay;
    }

    /**
     * @param array<string,mixed> $generated
     * @param array<string,mixed> $overlay
     *
     * @return array<string,mixed>
     */
    protected function mergePaths(array $generated, array $overlay): array
    {
        if (! isset($overlay['paths']) || ! is_array($overlay['paths'])) {
            return $generated;
        }

        if (! isset($generated['paths']) || ! is_array($generated['paths'])) {
            $generated['paths'] = [];
        }

        foreach ($overlay['paths'] as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                continue;
            }

            if (! array_key_exists($path, $generated['paths']) || ! is_array($generated['paths'][$path])) {
                $generated['paths'][$path] = $pathItem;

                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (! is_string($method) || ! is_array($operation)) {
                    continue;
                }

                if (! array_key_exists($method, $generated['paths'][$path]) || ! is_array($generated['paths'][$path][$method])) {
                    $generated['paths'][$path][$method] = $operation;

                    continue;
                }

                $generated['paths'][$path][$method] = $this->mergeOperationResponses(
                    $generated['paths'][$path][$method],
                    $operation
                );
            }
        }

        return $generated;
    }

    /**
     * @param array<string,mixed> $generatedOperation
     * @param array<string,mixed> $overlayOperation
     *
     * @return array<string,mixed>
     */
    protected function mergeOperationResponses(array $generatedOperation, array $overlayOperation): array
    {
        if (! isset($overlayOperation['responses']) || ! is_array($overlayOperation['responses'])) {
            return $generatedOperation;
        }

        if (! isset($generatedOperation['responses']) || ! is_array($generatedOperation['responses'])) {
            $generatedOperation['responses'] = [];
        }

        foreach ($overlayOperation['responses'] as $statusCode => $response) {
            if (! array_key_exists($statusCode, $generatedOperation['responses'])) {
                $generatedOperation['responses'][$statusCode] = $response;
            }
        }

        return $generatedOperation;
    }

    /**
     * @param array<string,mixed> $generated
     * @param array<string,mixed> $overlay
     *
     * @return array<string,mixed>
     */
    protected function mergeComponents(array $generated, array $overlay): array
    {
        if (! isset($overlay['components']) || ! is_array($overlay['components'])) {
            return $generated;
        }

        foreach (['schemas', 'securitySchemes'] as $componentKey) {
            if (! isset($overlay['components'][$componentKey]) || ! is_array($overlay['components'][$componentKey])) {
                continue;
            }

            if (! isset($generated['components']) || ! is_array($generated['components'])) {
                $generated['components'] = [];
            }

            if (! isset($generated['components'][$componentKey]) || ! is_array($generated['components'][$componentKey])) {
                $generated['components'][$componentKey] = [];
            }

            foreach ($overlay['components'][$componentKey] as $name => $component) {
                if (! array_key_exists($name, $generated['components'][$componentKey])) {
                    $generated['components'][$componentKey][$name] = $component;
                }
            }
        }

        return $generated;
    }
}
