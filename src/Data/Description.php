<?php
namespace NicoAndra\OpenApiGenerator\Data;

use Spatie\LaravelData\Data;

class Description extends Data {
    public function __construct(
        public string $rawDescription
    ) {}

    public function asString(): string
    {
        $pattern = '#^\s*/\*\*|^\s*[\*]{1,2}|^\s*[\*]*/#m';
        $description = preg_replace($pattern, '', $this->rawDescription);
    
        // Clean up whitespace and get the first part (before tags)
        $description = trim(preg_replace('/\s+@.*$/s', '', $description));

        $lines = collect(explode("\n", $description))
            ->map(fn($line) => trim($line))
            ->reject(fn($line) => empty($line))
            ->values()
            ->all();
        return implode("\n", $lines);
    }

    public static function fromDocComment(?string $docComment): self
    {   if(!$docComment) {
            return new self('');
        }
        return new self($docComment);
    }
}