<?php

namespace App\Infrastructure\AI\Agents;

interface AgentInterface
{
    public function name(): string;

    public function description(): string;

    /** @return array<ToolDefinition> */
    public function tools(): array;

    public function systemPrompt(): string;

    public function execute(AgentContext $context): AgentResult;
}
