<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AIServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(
        private readonly AIServiceInterface $aiService,
    ) {}

    public function guestResponse(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'property_id' => 'required|integer|exists:properties,id',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
        ]);

        $property = Property::findOrFail($request->property_id);

        $response = $this->aiService->generateGuestResponse(
            $request->message,
            [
                'property_name' => $property->name,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
            ],
        );

        if ($response === '') {
            return response()->json([
                'error' => 'AI service is temporarily unavailable.',
            ], 503);
        }

        return response()->json(['response' => $response]);
    }

    public function pricingSuggestion(Property $property, Request $request): JsonResponse
    {
        $request->validate([
            'occupancy_rate' => 'required|numeric|min:0|max:100',
            'avg_market_rate' => 'required|numeric|min:0',
        ]);

        $suggestion = $this->aiService->suggestPricingAdjustment($property, [
            'occupancy_rate' => $request->occupancy_rate,
            'avg_market_rate' => $request->avg_market_rate,
        ]);

        if (empty($suggestion)) {
            return response()->json([
                'error' => 'AI service is temporarily unavailable.',
            ], 503);
        }

        return response()->json(['suggestion' => $suggestion]);
    }
}
