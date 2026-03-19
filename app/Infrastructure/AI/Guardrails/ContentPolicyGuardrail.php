<?php

namespace App\Infrastructure\AI\Guardrails;

class ContentPolicyGuardrail implements GuardrailInterface
{
    private array $blockedPatterns = [
        '/\b(password|credit.?card|ssn|social.?security)\b/i',
    ];

    private array $piiPatterns = [
        '/\b\d{3}-\d{2}-\d{4}\b/',          // SSN
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Credit card
    ];

    public function validate(string $content, array $metadata = []): void
    {
        foreach ($this->piiPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new GuardrailViolation(
                    'Content contains potential PII that should not be processed',
                    'content_policy',
                    'pii_detected',
                );
            }
        }
    }
}
