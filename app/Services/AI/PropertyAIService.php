<?php

namespace App\Services\AI;

use App\Contracts\AIServiceInterface;
use App\Models\Property;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * LLM integration service for AI-powered features across the platform.
 *
 * Wraps the OpenAI SDK (compatible with any OpenAI-compatible API)
 * and provides domain-specific prompts for vacation rental use cases.
 *
 * Design decisions:
 * - Structured outputs via JSON mode for machine-consumable results
 * - Graceful degradation: AI failures never block core operations
 * - Token usage tracking for cost monitoring per operator
 */
class PropertyAIService implements AIServiceInterface
{
    public function generatePropertyDescription(Property $property): string
    {
        $prompt = $this->buildDescriptionPrompt($property);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional vacation rental copywriter. Write compelling, '
                            . 'SEO-friendly property descriptions that highlight unique features and local '
                            . 'attractions. Keep descriptions between 150-250 words.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Throwable $e) {
            Log::warning('AI description generation failed, returning empty', [
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    public function generateGuestResponse(string $guestMessage, array $context): string
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->buildGuestResponseSystemPrompt($context),
                    ],
                    ['role' => 'user', 'content' => $guestMessage],
                ],
                'temperature' => 0.6,
                'max_tokens' => 300,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Throwable $e) {
            Log::warning('AI guest response failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function suggestPricingAdjustment(Property $property, array $marketData): array
    {
        $prompt = sprintf(
            "Analyze this vacation rental property and suggest pricing adjustments.\n\n"
            . "Property: %s\nLocation: %s, %s\nBedrooms: %d\nCurrent base price: $%.2f/night\n"
            . "Occupancy rate (last 90 days): %.1f%%\n"
            . "Average market rate for similar properties: $%.2f/night\n\n"
            . "Return a JSON object with: suggested_price, confidence (0-1), reasoning",
            $property->name,
            $property->city,
            $property->state,
            $property->bedrooms,
            $property->basePriceInDollars(),
            $marketData['occupancy_rate'] ?? 0,
            $marketData['avg_market_rate'] ?? 0,
        );

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a vacation rental revenue management advisor. '
                            . 'Respond only with valid JSON.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
            ]);

            return json_decode($response->choices[0]->message->content, true) ?? [];
        } catch (\Throwable $e) {
            Log::warning('AI pricing suggestion failed', [
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function buildDescriptionPrompt(Property $property): string
    {
        $amenities = is_array($property->amenities)
            ? implode(', ', $property->amenities)
            : 'N/A';

        return sprintf(
            "Write a property listing description for:\n"
            . "Name: %s\nLocation: %s, %s, %s\n"
            . "Bedrooms: %d | Bathrooms: %d | Max Guests: %d\n"
            . "Amenities: %s\n"
            . "Additional context: %s",
            $property->name,
            $property->city,
            $property->state,
            $property->country,
            $property->bedrooms,
            $property->bathrooms,
            $property->max_guests,
            $amenities,
            $property->description ?? 'None provided',
        );
    }

    private function buildGuestResponseSystemPrompt(array $context): string
    {
        $propertyName = $context['property_name'] ?? 'the property';
        $checkIn = $context['check_in'] ?? 'N/A';
        $checkOut = $context['check_out'] ?? 'N/A';

        return "You are a helpful and friendly vacation rental host assistant for {$propertyName}. "
            . "The guest's stay is from {$checkIn} to {$checkOut}. "
            . 'Be warm, professional, and concise. If you don\'t know something specific, '
            . 'say you\'ll check and get back to them. Never make up information about the property.';
    }
}
