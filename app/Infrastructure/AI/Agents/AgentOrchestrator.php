<?php

namespace App\Infrastructure\AI\Agents;

use App\Infrastructure\AI\Guardrails\GuardrailPipeline;
use App\Infrastructure\AI\Evaluation\ResponseEvaluator;
use App\Infrastructure\AI\Monitoring\AIMetrics;
use Illuminate\Support\Facades\Log;

class AgentOrchestrator
{
    /** @var array<string, AgentInterface> */
    private array $agents = [];

    public function __construct(
        private readonly GuardrailPipeline $guardrails,
        private readonly ResponseEvaluator $evaluator,
        private readonly AIMetrics $metrics,
    ) {}

    public function register(string $name, AgentInterface $agent): void
    {
        $this->agents[$name] = $agent;
    }

    public function dispatch(string $agentName, AgentContext $context): AgentResult
    {
        $startTime = microtime(true);

        if (!isset($this->agents[$agentName])) {
            throw new \InvalidArgumentException("Agent not registered: {$agentName}");
        }

        $agent = $this->agents[$agentName];

        try {
            // Pre-execution guardrails
            $this->guardrails->validateInput($context);

            // Execute agent with tool loop
            $result = $this->executeWithRetry($agent, $context);

            // Post-execution guardrails
            $this->guardrails->validateOutput($result);

            // Evaluate response quality
            $evaluation = $this->evaluator->evaluate($context, $result);
            $result = $result->withEvaluation($evaluation);

            // Record metrics
            $this->metrics->record($agentName, $context, $result, microtime(true) - $startTime);

            Log::channel('ai')->info("Agent {$agentName} completed", [
                'duration_ms' => round((microtime(true) - $startTime) * 1000),
                'tokens_used' => $result->tokensUsed,
                'quality_score' => $evaluation->score,
                'tools_called' => count($result->toolCalls),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->metrics->recordFailure($agentName, $e);

            Log::channel('ai')->error("Agent {$agentName} failed", [
                'error' => $e->getMessage(),
                'context' => $context->toArray(),
            ]);

            throw $e;
        }
    }

    private function executeWithRetry(AgentInterface $agent, AgentContext $context, int $maxRetries = 2): AgentResult
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return $agent->execute($context);
            } catch (RetryableException $e) {
                $lastException = $e;
                Log::channel('ai')->warning("Agent retry attempt {$attempt}", [
                    'error' => $e->getMessage(),
                ]);
                usleep(min(1000000, 100000 * (2 ** $attempt))); // Exponential backoff
            }
        }

        throw $lastException;
    }
}
