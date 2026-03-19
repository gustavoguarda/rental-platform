<?php

namespace App\Infrastructure\AI\Evaluation;

use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentResult;

class CompletenessEvaluator implements EvaluationCriteria
{
    public function name(): string
    {
        return 'completeness';
    }

    public function score(AgentContext $context, AgentResult $result): float
    {
        $response = $result->response;

        // Empty response
        if (empty(trim($response))) {
            return 0.0;
        }

        // Very short responses are likely incomplete
        if (strlen($response) < 20) {
            return 0.3;
        }

        // Check if the response addresses key elements
        $score = 0.5;

        // Used tools to gather information (good sign)
        if (count($result->toolCalls) > 0) {
            $score += 0.2;
        }

        // Response contains structured data (JSON)
        if (json_decode($response) !== null) {
            $score += 0.15;
        }

        // Reasonable length
        if (strlen($response) > 100) {
            $score += 0.15;
        }

        return min(1.0, $score);
    }
}
