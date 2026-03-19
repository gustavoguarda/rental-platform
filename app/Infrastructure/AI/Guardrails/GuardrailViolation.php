<?php

namespace App\Infrastructure\AI\Guardrails;

class GuardrailViolation extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $guardrailName,
        public readonly string $violationType = 'content_policy',
    ) {
        parent::__construct($message);
    }
}
