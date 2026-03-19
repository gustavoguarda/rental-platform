<?php

namespace App\Infrastructure\AI\Agents;

use App\Models\Booking;
use App\Models\Property;
use OpenAI\Laravel\Facades\OpenAI;

class GuestCommunicationAgent implements AgentInterface
{
    public function name(): string
    {
        return 'guest-communication';
    }

    public function description(): string
    {
        return 'Handles guest inquiries and communication with context-aware responses';
    }

    public function tools(): array
    {
        return [
            new ToolDefinition(
                name: 'lookup_property',
                description: 'Get property details including amenities, location, and policies',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'Property ID'],
                    ],
                    'required' => ['property_id'],
                ],
                handler: fn (array $args) => Property::find($args['property_id'])?->toArray() ?? ['error' => 'Property not found'],
            ),
            new ToolDefinition(
                name: 'lookup_booking',
                description: 'Get booking details including dates, guest info, and status',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'booking_id' => ['type' => 'integer', 'description' => 'Booking ID'],
                    ],
                    'required' => ['booking_id'],
                ],
                handler: fn (array $args) => Booking::with('property')->find($args['booking_id'])?->toArray() ?? ['error' => 'Booking not found'],
            ),
            new ToolDefinition(
                name: 'check_availability',
                description: 'Check if a property is available for given dates',
                parameters: [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer'],
                        'check_in' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'check_out' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'guests' => ['type' => 'integer'],
                    ],
                    'required' => ['property_id', 'check_in', 'check_out', 'guests'],
                ],
                handler: function (array $args) {
                    $checker = app(\App\Contracts\AvailabilityCheckerInterface::class);
                    $stay = \App\DTOs\StayRequest::fromArray($args);
                    return $checker->check($stay)->toArray();
                },
            ),
        ];
    }

    public function systemPrompt(): string
    {
        return <<<PROMPT
You are a professional and friendly guest communication assistant for a vacation rental platform.

Guidelines:
- Be warm and helpful, but professional
- Always provide accurate information by using your tools to look up data
- Never fabricate property details, availability, or pricing
- If you don't have enough information, ask the guest for clarification
- Suggest relevant amenities and local attractions when appropriate
- Keep responses concise but complete
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

        // Agent tool-use loop
        for ($i = 0; $i < $context->maxIterations; $i++) {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'tools' => $toolDefinitions,
                'tool_choice' => 'auto',
                'temperature' => 0.7,
            ]);

            $totalTokens += $response->usage->totalTokens ?? 0;
            $choice = $response->choices[0];

            // If no tool calls, we have our final response
            if ($choice->finishReason === 'stop' || empty($choice->message->toolCalls)) {
                return new AgentResult(
                    response: $choice->message->content ?? '',
                    toolCalls: $toolCalls,
                    tokensUsed: $totalTokens,
                    metadata: ['iterations' => $i + 1],
                );
            }

            // Process tool calls
            $messages[] = $choice->message->toArray();

            foreach ($choice->message->toolCalls as $toolCall) {
                $functionName = $toolCall->function->name;
                $arguments = json_decode($toolCall->function->arguments, true);

                $toolCalls[] = ['name' => $functionName, 'arguments' => $arguments];

                if (isset($toolMap[$functionName])) {
                    $result = ($toolMap[$functionName])($arguments);
                } else {
                    $result = ['error' => "Unknown tool: {$functionName}"];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall->id,
                    'content' => json_encode($result),
                ];
            }
        }

        return new AgentResult(
            response: 'I apologize, but I was unable to complete this request. Please try again.',
            toolCalls: $toolCalls,
            tokensUsed: $totalTokens,
            metadata: ['iterations' => $context->maxIterations, 'hit_limit' => true],
        );
    }
}
