<?php

namespace App\Infrastructure\AI\Agents;

final readonly class ToolDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
        public \Closure $handler,
    ) {}

    public function toOpenAIFormat(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $this->parameters,
            ],
        ];
    }
}
