<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'operator_id',
        'name',
        'slug',
        'description',
        'ai_description',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'max_guests',
        'base_price_cents',
        'currency',
        'amenities',
        'status',
    ];

    protected $casts = [
        'amenities' => 'array',
        'base_price_cents' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'max_guests' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function blockouts(): HasMany
    {
        return $this->hasMany(Blockout::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active';
    }

    public function basePriceInDollars(): float
    {
        return $this->base_price_cents / 100;
    }
}
