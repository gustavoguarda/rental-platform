<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|integer|exists:properties,id',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests_count' => 'required|integer|min:1',
            'source_channel' => 'nullable|string|in:direct,airbnb,vrbo,booking_com',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
