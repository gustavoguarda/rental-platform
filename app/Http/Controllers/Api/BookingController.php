<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AvailabilityCheckerInterface;
use App\Contracts\PricingEngineInterface;
use App\DTOs\StayRequest;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityCheckerInterface $availability,
        private readonly PricingEngineInterface $pricing,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with('property:id,name,city')
            ->when($request->get('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('check_in', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($bookings);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $stayRequest = StayRequest::fromArray($request->validated());

        // Check availability first
        $availabilityResult = $this->availability->check($stayRequest);

        if (! $availabilityResult->isAvailable) {
            return response()->json([
                'error' => 'Property is not available.',
                'details' => $availabilityResult->toArray(),
            ], 409);
        }

        // Calculate pricing
        $quote = $this->pricing->calculateQuote($stayRequest);

        // Create booking within a transaction to prevent double-booking
        $booking = DB::transaction(function () use ($request, $quote) {
            // Pessimistic lock: re-check availability inside transaction
            $conflictExists = Booking::where('property_id', $request->property_id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where('check_in', '<', $request->check_out)
                ->where('check_out', '>', $request->check_in)
                ->lockForUpdate()
                ->exists();

            if ($conflictExists) {
                abort(409, 'Property was booked by another guest. Please try different dates.');
            }

            return Booking::create([
                ...$request->validated(),
                'total_price_cents' => $quote->totalWithFeesCents(),
                'currency' => $quote->currency,
                'status' => 'pending',
            ]);
        });

        BookingCreated::dispatch($booking);

        return response()->json([
            'booking' => $booking,
            'pricing' => $quote->toArray(),
        ], 201);
    }

    public function confirm(Booking $booking): JsonResponse
    {
        if (! $booking->isPending()) {
            return response()->json([
                'error' => 'Only pending bookings can be confirmed.',
            ], 422);
        }

        $booking->update(['status' => 'confirmed']);

        BookingConfirmed::dispatch($booking->fresh());

        return response()->json($booking);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'Booking is already cancelled.'], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json($booking);
    }
}
