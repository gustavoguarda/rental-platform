<?php

namespace App\Infrastructure\AI\Evaluation;

final readonly class EvaluationResult
{
    public function __construct(
        public float $score,
        public array $criteriaScores,
        public bool $passed,
    ) {}

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'criteria_scores' => $this->criteriaScores,
            'passed' => $this->passed,
        ];
    }
}
