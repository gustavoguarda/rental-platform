<?php

namespace App\Infrastructure\AI\Guardrails;

interface GuardrailInterface
{
    /**
     * @throws GuardrailViolation
     */
    public function validate(string $content, array $metadata = []): void;
}
