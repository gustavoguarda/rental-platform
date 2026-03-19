<?php

namespace App\Infrastructure\AI\Agents;

final readonly class AgentContext
{
    public function __construct(
        public string $userMessage,
        public array $metadata = [],
        public ?int $operatorId = null,
        public ?int $propertyId = null,
        public array $conversationHistory = [],
        public int $maxIterations = 10,
    ) {}

    public function toArray(): array
    {
        return [
            'user_message' => $this->userMessage,
            'metadata' => $this->metadata,
            'operator_id' => $this->operatorId,
            'property_id' => $this->propertyId,
            'history_length' => count($this->conversationHistory),
            'max_iterations' => $this->maxIterations,
        ];
    }
}
