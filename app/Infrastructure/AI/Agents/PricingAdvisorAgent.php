<?php

namespace App\Infrastructure\AI\Agents;

use App\Models\Booking;
use App\Models\PricingRule;
use App\Models\Property;
use OpenAI\Laravel\Facades\OpenAI;

class PricingAdvisorAgent implements AgentInterface
{
    public function name(): string
    {
        return 'pricing-advisor';
    }

    public function description(): string
    {
        return 'Analyzes booking patterns and market data to suggest pricing adjustments';
    }

    public function tools(): array
    {
        return [
            new ToolDefinition(
                name: 'get_property_bookings',
                description: 'Get recent bookings for a property to analyze occupancy patterns',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                        'months_back' => ['type' => 'integer', 'description' => 'How many months of history'],
                    ],
                    'required' => ['property_id'],
                ],
                handler: function (array $args) {
                    $months = $args['months_back'] ?? 6;
                    return Booking::where('property_id', $args['property_id'])
                        ->where('check_in', '>=', now()->subMonths($months))
                        ->get(['check_in', 'check_out', 'total_price_cents', 'guests_count', 'status', 'source_channel'])
                        ->toArray();
                },
            ),
            new ToolDefinition(
                name: 'get_current_pricing_rules',
                description: 'Get active pricing rules for a property',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                    ],
                    'required' => ['property_id'],
                ],
                handler: fn (array $args) => PricingRule::where('property_id', $args['property_id'])
                    ->where('is_active', true)
                    ->get()
                    ->toArray(),
            ),
            new ToolDefinition(
                name: 'get_property_details',
                description: 'Get property details including base price and amenities',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                    ],
                    'required' => ['property_id'],
                ],
                handler: fn (array $args) => Property::find($args['property_id'])?->toArray() ?? ['error' => 'Not found'],
            ),
        ];
    }

    public function systemPrompt(): string
    {
        return <<<PROMPT
You are a revenue management advisor for vacation rental properties.

Your job is to analyze booking patterns, occupancy rates, and pricing rules to suggest
pricing optimizations that maximize revenue while maintaining competitive occupancy.

When making suggestions:
- Consider seasonal patterns and day-of-week trends
- Factor in the property's amenities, location, and capacity
- Compare current pricing rules with booking patterns
- Provide specific, actionable recommendations with expected impact
- Always explain the reasoning behind your suggestions

Respond with structured JSON when providing pricing suggestions:
{
    "current_analysis": "Brief analysis of current pricing",
    "occupancy_rate": "Estimated occupancy percentage",
    "recommendations": [
        {
            "type": "seasonal|weekend|discount|base_price",
            "description": "What to change",
            "expected_impact": "Expected revenue impact",
            "confidence": "high|medium|low"
        }
    ]
}
PROMPT;
    }

    public function execute(AgentContext $context): AgentResult
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ...$context->conversationHistory,
            ['role' => 'user', 'content' => $context->userMessage],
        ];

        $toolDefinitions = array_map(fn (ToolDefinition $t) => $t->toOpenAIFormat(), $this->tools());
        $toolMap = [];
        foreach ($this->tools() as $tool) {
            $toolMap[$tool->name] = $tool->handler;
        }

        $totalTokens = 0;
        $toolCalls = [];

        for ($i = 0; $i < $context->maxIterations; $i++) {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'tools' => $toolDefinitions,
                'tool_choice' => 'auto',
                'temperature' => 0.3,
            ]);

            $totalTokens += $response->usage->totalTokens ?? 0;
            $choice = $response->choices[0];

            if ($choice->finishReason === 'stop' || empty($choice->message->toolCalls)) {
                return new AgentResult(
                    response: $choice->message->content ?? '',
                    toolCalls: $toolCalls,
                    tokensUsed: $totalTokens,
                    metadata: ['iterations' => $i + 1],
                );
            }

            $messages[] = $choice->message->toArray();

            foreach ($choice->message->toolCalls as $toolCall) {
                $functionName = $toolCall->function->name;
                $arguments = json_decode($toolCall->function->arguments, true);
                $toolCalls[] = ['name' => $functionName, 'arguments' => $arguments];

                $result = isset($toolMap[$functionName])
                    ? ($toolMap[$functionName])($arguments)
                    : ['error' => "Unknown tool: {$functionName}"];

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall->id,
                    'content' => json_encode($result),
                ];
            }
        }

        return new AgentResult(
            response: '{"error": "Analysis could not be completed within the iteration limit"}',
            toolCalls: $toolCalls,
            tokensUsed: $totalTokens,
            metadata: ['hit_limit' => true],
        );
    }
}
