<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\AI\Agents\AgentContext;
use App\Infrastructure\AI\Agents\AgentOrchestrator;
use App\Infrastructure\AI\Monitoring\AIMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
        private readonly AIMetrics $metrics,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'agent' => 'required|string|in:guest-communication,pricing-advisor',
            'message' => 'required|string|max:4000',
            'property_id' => 'nullable|integer|exists:properties,id',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        $context = new AgentContext(
            userMessage: $request->input('message'),
            metadata: ['source' => 'api'],
            operatorId: $request->header('X-Operator-Id'),
            propertyId: $request->input('property_id'),
            conversationHistory: $request->input('history', []),
        );

        try {
            $result = $this->orchestrator->dispatch($request->input('agent'), $context);

            return response()->json([
                'response' => $result->response,
                'metadata' => [
                    'tokens_used' => $result->tokensUsed,
                    'tools_called' => count($result->toolCalls),
                    'quality_score' => $result->evaluation?->score,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Agent processing failed. Please try again.',
            ], 503);
        }
    }

    public function stats(string $agent): JsonResponse
    {
        return response()->json($this->metrics->getStats($agent));
    }
}
