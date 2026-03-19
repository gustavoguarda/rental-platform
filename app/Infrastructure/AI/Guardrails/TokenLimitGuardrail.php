<?php

namespace App\Infrastructure\AI\Guardrails;

class TokenLimitGuardrail implements GuardrailInterface
{
    public function __construct(
        private readonly int $maxInputTokens = 4000,
    ) {}

    public function validate(string $content, array $metadata = []): void
    {
        // Rough estimate: 1 token ≈ 4 characters
        $estimatedTokens = (int) ceil(strlen($content) / 4);

        if ($estimatedTokens > $this->maxInputTokens) {
            throw new GuardrailViolation(
                "Input exceeds maximum token limit ({$estimatedTokens} > {$this->maxInputTokens})",
                'token_limit',
                'input_too_long',
            );
        }
    }
}
