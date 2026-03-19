<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Jobs\GenerateAIDescription;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->when($request->get('city'), fn ($q, $city) => $q->where('city', $city))
            ->when($request->get('min_bedrooms'), fn ($q, $min) => $q->where('bedrooms', '>=', $min))
            ->when($request->get('max_guests'), fn ($q, $max) => $q->where('max_guests', '>=', $max))
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($properties);
    }

    public function show(Property $property): JsonResponse
    {
        $property->load(['pricingRules' => fn ($q) => $q->where('is_active', true)]);

        return response()->json($property);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = Property::create($request->validated());

        // Asynchronously generate AI description
        GenerateAIDescription::dispatch($property->id);

        return response()->json($property, 201);
    }

    public function update(StorePropertyRequest $request, Property $property): JsonResponse
    {
        $property->update($request->validated());

        return response()->json($property);
    }

    public function destroy(Property $property): JsonResponse
    {
        $property->delete();

        return response()->json(null, 204);
    }

    public function regenerateDescription(Property $property): JsonResponse
    {
        GenerateAIDescription::dispatch($property->id);

        return response()->json([
            'message' => 'AI description generation queued.',
        ], 202);
    }
}
