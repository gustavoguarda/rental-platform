<?php

namespace App\Jobs;

use App\Contracts\AIServiceInterface;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generates an AI-powered property description asynchronously.
 *
 * Dispatched when a property is created or when an operator
 * requests a new description. Runs on the default queue since
 * LLM calls can take 2-5 seconds.
 */
class GenerateAIDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly int $propertyId,
    ) {}

    public function handle(AIServiceInterface $aiService): void
    {
        $property = Property::findOrFail($this->propertyId);

        $description = $aiService->generatePropertyDescription($property);

        if ($description !== '') {
            $property->update(['ai_description' => $description]);
        }
    }
}
