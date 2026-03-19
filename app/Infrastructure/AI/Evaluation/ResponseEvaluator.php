<?php

namespace App\Infrastructure\AI\Evaluation;

use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentResult;

class ResponseEvaluator
{
    /** @var EvaluationCriteria[] */
    private array $criteria = [];

    public function addCriteria(EvaluationCriteria $criteria): self
    {
        $this->criteria[] = $criteria;
        return $this;
    }

    public function evaluate(AgentContext $context, AgentResult $result): EvaluationResult
    {
        $scores = [];

        foreach ($this->criteria as $criterion) {
            $scores[$criterion->name()] = $criterion->score($context, $result);
        }

        $avgScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 1.0;

        return new EvaluationResult(
            score: round($avgScore, 2),
            criteriaScores: $scores,
            passed: $avgScore >= 0.6,
        );
    }
}
