<?php

namespace App\Infrastructure\AI\Monitoring;

use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIMetrics
{
    private const METRICS_PREFIX = 'ai_metrics:';
    private const RETENTION_HOURS = 168; // 7 days

    public function record(string $agentName, AgentContext $context, AgentResult $result, float $duration): void
    {
        $metric = [
            'agent' => $agentName,
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round($duration * 1000),
            'tokens_used' => $result->tokensUsed,
            'tool_calls' => count($result->toolCalls),
            'quality_score' => $result->evaluation?->score ?? null,
            'evaluation_passed' => $result->evaluation?->passed ?? null,
            'operator_id' => $context->operatorId,
        ];

        // Store in Redis for real-time dashboards
        $key = self::METRICS_PREFIX . $agentName . ':' . now()->format('Y-m-d-H');
        Cache::tags(['ai_metrics'])->put(
            $key . ':' . uniqid(),
            $metric,
            now()->addHours(self::RETENTION_HOURS),
        );

        // Increment counters
        $this->incrementCounter("requests:{$agentName}", self::RETENTION_HOURS * 3600);
        $this->incrementCounter("tokens:{$agentName}", self::RETENTION_HOURS * 3600, $result->tokensUsed);

        // Track latency percentiles
        $this->trackLatency($agentName, $duration);
    }

    public function recordFailure(string $agentName, \Throwable $error): void
    {
        $this->incrementCounter("failures:{$agentName}", self::RETENTION_HOURS * 3600);

        Log::channel('ai')->error("AI Agent failure recorded", [
            'agent' => $agentName,
            'error_class' => get_class($error),
            'error_message' => $error->getMessage(),
        ]);
    }

    public function getStats(string $agentName): array
    {
        return [
            'total_requests' => (int) Cache::get(self::METRICS_PREFIX . "requests:{$agentName}", 0),
            'total_tokens' => (int) Cache::get(self::METRICS_PREFIX . "tokens:{$agentName}", 0),
            'total_failures' => (int) Cache::get(self::METRICS_PREFIX . "failures:{$agentName}", 0),
            'avg_latency_ms' => $this->getAverageLatency($agentName),
        ];
    }

    private function incrementCounter(string $key, int $ttl, int $amount = 1): void
    {
        $fullKey = self::METRICS_PREFIX . $key;
        if (Cache::has($fullKey)) {
            Cache::increment($fullKey, $amount);
        } else {
            Cache::put($fullKey, $amount, $ttl);
        }
    }

    private function trackLatency(string $agentName, float $duration): void
    {
        $key = self::METRICS_PREFIX . "latency:{$agentName}";
        $latencies = Cache::get($key, []);
        $latencies[] = round($duration * 1000);

        // Keep last 1000 measurements
        if (count($latencies) > 1000) {
            $latencies = array_slice($latencies, -1000);
        }

        Cache::put($key, $latencies, now()->addHours(self::RETENTION_HOURS));
    }

    private function getAverageLatency(string $agentName): float
    {
        $key = self::METRICS_PREFIX . "latency:{$agentName}";
        $latencies = Cache::get($key, []);

        if (empty($latencies)) {
            return 0.0;
        }

        return round(array_sum($latencies) / count($latencies), 1);
    }
}
