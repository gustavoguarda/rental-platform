<?php

namespace App\Providers;

use App\Contracts\AIServiceInterface;
use App\Contracts\AvailabilityCheckerInterface;
use App\Contracts\PricingEngineInterface;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Infrastructure\AI\Agents\AgentOrchestrator;
use App\Infrastructure\AI\Agents\GuestCommunicationAgent;
use App\Infrastructure\AI\Agents\PricingAdvisorAgent;
use App\Infrastructure\AI\Evaluation\CompletenessEvaluator;
use App\Infrastructure\AI\Evaluation\ResponseEvaluator;
use App\Infrastructure\AI\Guardrails\ContentPolicyGuardrail;
use App\Infrastructure\AI\Guardrails\GuardrailPipeline;
use App\Infrastructure\AI\Guardrails\TokenLimitGuardrail;
use App\Infrastructure\AI\Monitoring\AIMetrics;
use App\Listeners\InvalidateAvailabilityCache;
use App\Listeners\SyncBookingToChannels;
use App\Services\AI\PropertyAIService;
use App\Services\Availability\AvailabilityChecker;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Core platform services — contract-driven for Strangler Fig migration
        $this->app->bind(PricingEngineInterface::class, PricingEngine::class);
        $this->app->bind(AvailabilityCheckerInterface::class, AvailabilityChecker::class);
        $this->app->bind(AIServiceInterface::class, PropertyAIService::class);

        // AI Agent infrastructure
        $this->app->singleton(AIMetrics::class);

        $this->app->singleton(GuardrailPipeline::class, function () {
            return (new GuardrailPipeline())
                ->addInputGuardrail(new ContentPolicyGuardrail())
                ->addInputGuardrail(new TokenLimitGuardrail(4000))
                ->addOutputGuardrail(new ContentPolicyGuardrail());
        });

        $this->app->singleton(ResponseEvaluator::class, function () {
            return (new ResponseEvaluator())
                ->addCriteria(new CompletenessEvaluator());
        });

        $this->app->singleton(AgentOrchestrator::class, function ($app) {
            $orchestrator = new AgentOrchestrator(
                guardrails: $app->make(GuardrailPipeline::class),
                evaluator: $app->make(ResponseEvaluator::class),
                metrics: $app->make(AIMetrics::class),
            );

            $orchestrator->register('guest-communication', new GuestCommunicationAgent());
            $orchestrator->register('pricing-advisor', new PricingAdvisorAgent());

            return $orchestrator;
        });
    }

    public function boot(): void
    {
        // Domain event subscriptions
        Event::listen(BookingCreated::class, InvalidateAvailabilityCache::class);
        Event::listen(BookingConfirmed::class, InvalidateAvailabilityCache::class);
        Event::listen(BookingConfirmed::class, SyncBookingToChannels::class);
    }
}
