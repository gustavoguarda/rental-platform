<?php

namespace App\Infrastructure\AI\Agents;

use App\Infrastructure\AI\Evaluation\EvaluationResult;

final class AgentResult
{
    public function __construct(
        public readonly string $response,
        public readonly array $toolCalls = [],
        public readonly int $tokensUsed = 0,
        public readonly array $metadata = [],
        public ?EvaluationResult $evaluation = null,
    ) {}

    public function withEvaluation(EvaluationResult $evaluation): self
    {
        $new = clone $this;
        $new->evaluation = $evaluation;
        return $new;
    }

    public function toArray(): array
    {
        return [
            'response' => $this->response,
            'tool_calls' => $this->toolCalls,
            'tokens_used' => $this->tokensUsed,
            'metadata' => $this->metadata,
            'evaluation' => $this->evaluation?->toArray(),
        ];
    }
}
