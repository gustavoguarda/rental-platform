<?php

namespace App\Infrastructure\AI\Guardrails;

use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentResult;

class GuardrailPipeline
{
    /** @var GuardrailInterface[] */
    private array $inputGuardrails = [];

    /** @var GuardrailInterface[] */
    private array $outputGuardrails = [];

    public function addInputGuardrail(GuardrailInterface $guardrail): self
    {
        $this->inputGuardrails[] = $guardrail;
        return $this;
    }

    public function addOutputGuardrail(GuardrailInterface $guardrail): self
    {
        $this->outputGuardrails[] = $guardrail;
        return $this;
    }

    public function validateInput(AgentContext $context): void
    {
        foreach ($this->inputGuardrails as $guardrail) {
            $guardrail->validate($context->userMessage, $context->metadata);
        }
    }

    public function validateOutput(AgentResult $result): void
    {
        foreach ($this->outputGuardrails as $guardrail) {
            $guardrail->validate($result->response, $result->metadata);
        }
    }
}
