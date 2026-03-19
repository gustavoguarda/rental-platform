<?php

namespace App\Infrastructure\AI\Evaluation;

use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentResult;

interface EvaluationCriteria
{
    public function name(): string;

    /**
     * @return float Score between 0.0 and 1.0
     */
    public function score(AgentContext $context, AgentResult $result): float;
}
