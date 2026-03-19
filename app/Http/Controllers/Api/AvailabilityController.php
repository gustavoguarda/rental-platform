<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AvailabilityCheckerInterface;
use App\Contracts\PricingEngineInterface;
use App\DTOs\StayRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityCheckerInterface $availability,
        private readonly PricingEngineInterface $pricing,
    ) {}

    /**
     * Check availability and get pricing for a stay.
     *
     * This is the primary endpoint consumed by the booking widget,
     * the channel manager, and the owner portal.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
        ]);

        $stayRequest = StayRequest::fromArray($request->all());

        $result = $this->availability->check($stayRequest);

        $response = ['availability' => $result->toArray()];

        // Only compute pricing if available (saves compute on unavailable requests)
        if ($result->isAvailable) {
            $quote = $this->pricing->calculateQuote($stayRequest);
            $response['pricing'] = $quote->toArray();
        }

        return response()->json($response);
    }
}
