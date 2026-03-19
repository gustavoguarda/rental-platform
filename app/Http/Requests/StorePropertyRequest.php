<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operator_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'bedrooms' => 'required|integer|min:0|max:50',
            'bathrooms' => 'required|integer|min:0|max:50',
            'max_guests' => 'required|integer|min:1|max:100',
            'base_price_cents' => 'required|integer|min:1',
            'currency' => 'nullable|string|size:3',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'status' => 'nullable|in:active,inactive,draft',
        ];
    }
}
